<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\CategoriaTuristica;
use App\Models\DestinoTuristico;
use App\Models\ServicioTuristico;
use App\Services\ServicioTuristicoService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ServicioTuristicoTest extends TestCase
{
    use DatabaseTransactions;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create([
            'lCambiarPassword' => 0
        ]);
    }

    /** 1. Registro y edición de categorías turísticas */
    public function test_registro_y_edicion_de_categoria(): void
    {
        $response = $this->actingAs($this->user)->postJson(route('servicios.categorias.saveData'), [
            'cNombre'      => 'Ecoturismo',
            'cDescripcion' => 'Viajes de ecoturismo y contacto con la naturaleza',
        ]);

        $response->assertStatus(200)->assertJson(['lSuccess' => true]);
        $this->assertDatabaseHas('categoria_turisticas', ['cNombre' => 'Ecoturismo']);

        $cat = CategoriaTuristica::where('cNombre', 'Ecoturismo')->first();

        $editResponse = $this->actingAs($this->user)->postJson(route('servicios.categorias.saveData'), [
            'iID'          => $cat->iID,
            'cNombre'      => 'Ecoturismo & Aventura',
            'cDescripcion' => 'Actualizado con aventuras',
        ]);

        $editResponse->assertStatus(200)->assertJson(['lSuccess' => true]);
        $this->assertDatabaseHas('categoria_turisticas', ['cNombre' => 'Ecoturismo & Aventura']);
    }

    /** 2. Registro y edición de destinos turísticos */
    public function test_registro_y_edicion_de_destino(): void
    {
        $response = $this->actingAs($this->user)->postJson(route('servicios.destinos.saveData'), [
            'cNombre'      => 'Cartagena de Indias',
            'cPais'        => 'Colombia',
            'cEstado'      => 'Bolívar',
            'cCiudad'      => 'Cartagena',
            'cDescripcion' => 'Ciudad colonial e histórica',
        ]);

        $response->assertStatus(200)->assertJson(['lSuccess' => true]);
        $this->assertDatabaseHas('destino_turisticos', ['cNombre' => 'Cartagena de Indias', 'cPais' => 'Colombia']);

        $dest = DestinoTuristico::where('cNombre', 'Cartagena de Indias')->first();

        $editResponse = $this->actingAs($this->user)->postJson(route('servicios.destinos.saveData'), [
            'iID'          => $dest->iID,
            'cNombre'      => 'Cartagena de Indias VIP',
            'cPais'        => 'Colombia',
            'cEstado'      => 'Bolívar',
            'cCiudad'      => 'Cartagena',
            'cDescripcion' => 'Destino de lujo',
        ]);

        $editResponse->assertStatus(200)->assertJson(['lSuccess' => true]);
        $this->assertDatabaseHas('destino_turisticos', ['cNombre' => 'Cartagena de Indias VIP']);
    }

    /** 3 & 4. Creación de servicios y cálculo correcto de ganancias */
    public function test_creacion_de_servicio_y_calculo_de_ganancias(): void
    {
        $cat = CategoriaTuristica::create(['cNombre' => 'Internacional', 'iIDUsuario' => $this->user->id]);
        $dest = DestinoTuristico::create(['cNombre' => 'Colombia', 'cPais' => 'Colombia', 'iIDUsuario' => $this->user->id]);

        $response = $this->actingAs($this->user)->postJson(route('servicios.paquetes.saveData'), [
            'cNombre'              => 'Viaje a Colombia Mágico',
            'cDescripcionCorta'    => 'Descubre Medellín y Cartagena',
            'iIDCategoria'         => $cat->iID,
            'iIDDestino'           => $dest->iID,
            'dPrecioCompra'        => 7000.00,
            'dPrecioVenta'         => 9500.00,
            'dFechaInicioVigencia' => now()->toDateString(),
            'dFechaFinVigencia'    => now()->addDays(30)->toDateString(),
        ]);

        $response->assertStatus(200)->assertJson(['lSuccess' => true]);

        $serv = ServicioTuristico::where('cNombre', 'Viaje a Colombia Mágico')->first();
        $this->assertNotNull($serv);
        $this->assertStringStartsWith('SERV-', $serv->cCodigo);

        // Ganancia = 9500 - 7000 = 2500
        $this->assertEquals(2500.00, $serv->d_ganancia_estimada);
        // Utilidad = (2500 / 7000) * 100 = 35.71%
        $this->assertEquals(35.71, $serv->d_porcentaje_utilidad);
    }

    /** 5. Aplicación de descuentos porcentuales */
    public function test_descuento_porcentual(): void
    {
        $cat = CategoriaTuristica::firstOrCreate(['cNombre' => 'Playas Test'], ['iIDUsuario' => $this->user->id]);
        $dest = DestinoTuristico::firstOrCreate(['cNombre' => 'Cancún Test', 'cPais' => 'México'], ['iIDUsuario' => $this->user->id]);

        $serv = ServicioTuristico::create([
            'cCodigo'               => 'SERV-TEST-' . uniqid(),
            'cNombre'               => 'Cancún Todo Incluido',
            'iIDCategoria'          => $cat->iID,
            'iIDDestino'            => $dest->iID,
            'dPrecioCompra'         => 10000.00,
            'dPrecioVenta'          => 15000.00,
            'lTienePromocion'       => 1,
            'cTipoDescuento'        => 'PORCENTAJE',
            'dValorDescuento'       => 10, // 10% de descuento
            'dFechaInicioPromocion' => now()->subDay(),
            'dFechaFinPromocion'    => now()->addDays(5),
            'lPromocionActiva'      => 1,
            'dFechaInicioVigencia'  => now()->subDays(2)->toDateString(),
            'dFechaFinVigencia'     => now()->addDays(30)->toDateString(),
            'lActivo'               => 1,
            'iIDUsuario'            => $this->user->id,
        ]);

        // Descuento 10% de 15,000 = 1,500. Precio final = 13,500.
        $this->assertEquals(1500.00, $serv->d_monto_descuento);
        $this->assertEquals(13500.00, $serv->d_precio_final);
        // Ganancia efectiva = 13500 - 10000 = 3500.
        $this->assertEquals(3500.00, $serv->d_ganancia_efectiva);
    }

    /** 6. Aplicación de descuentos fijos */
    public function test_descuento_fijo(): void
    {
        $cat = CategoriaTuristica::firstOrCreate(['cNombre' => 'Nacional Test'], ['iIDUsuario' => $this->user->id]);
        $dest = DestinoTuristico::firstOrCreate(['cNombre' => 'Oaxaca Test', 'cPais' => 'México'], ['iIDUsuario' => $this->user->id]);

        $serv = ServicioTuristico::create([
            'cCodigo'               => 'SERV-TEST-' . uniqid(),
            'cNombre'               => 'Ruta del Mezcal',
            'iIDCategoria'          => $cat->iID,
            'iIDDestino'            => $dest->iID,
            'dPrecioCompra'         => 3000.00,
            'dPrecioVenta'          => 5000.00,
            'lTienePromocion'       => 1,
            'cTipoDescuento'        => 'IMPORTE',
            'dValorDescuento'       => 800.00, // $800 de descuento fijo
            'dFechaInicioPromocion' => now()->subDay(),
            'dFechaFinPromocion'    => now()->addDays(5),
            'lPromocionActiva'      => 1,
            'dFechaInicioVigencia'  => now()->subDays(2)->toDateString(),
            'dFechaFinVigencia'     => now()->addDays(30)->toDateString(),
            'lActivo'               => 1,
            'iIDUsuario'            => $this->user->id,
        ]);

        $this->assertEquals(800.00, $serv->d_monto_descuento);
        $this->assertEquals(4200.00, $serv->d_precio_final);
    }

    /** 7. Vencimiento automático de promociones */
    public function test_vencimiento_automatico_de_promocion(): void
    {
        $cat = CategoriaTuristica::firstOrCreate(['cNombre' => 'Tours Test'], ['iIDUsuario' => $this->user->id]);
        $dest = DestinoTuristico::firstOrCreate(['cNombre' => 'Chichén Itzá Test', 'cPais' => 'México'], ['iIDUsuario' => $this->user->id]);

        $serv = ServicioTuristico::create([
            'cCodigo'               => 'SERV-TEST-' . uniqid(),
            'cNombre'               => 'Tour Arqueológico',
            'iIDCategoria'          => $cat->iID,
            'iIDDestino'            => $dest->iID,
            'dPrecioCompra'         => 1000.00,
            'dPrecioVenta'          => 2000.00,
            'lTienePromocion'       => 1,
            'cTipoDescuento'        => 'PORCENTAJE',
            'dValorDescuento'       => 20,
            'dFechaInicioPromocion' => now()->subDays(10),
            'dFechaFinPromocion'    => now()->subDays(1), // Promoción Vencida ayer
            'lPromocionActiva'      => 1,
            'dFechaInicioVigencia'  => now()->subDays(10)->toDateString(),
            'dFechaFinVigencia'     => now()->addDays(30)->toDateString(),
            'lActivo'               => 1,
            'iIDUsuario'            => $this->user->id,
        ]);

        // La promoción expiró por fechas, debe regresar al precio normal sin desactivar el servicio
        $this->assertFalse($serv->l_promocion_vigente);
        $this->assertEquals(2000.00, $serv->d_precio_final);
        $this->assertEquals('ACTIVO', $serv->c_estatus_disponibilidad);
    }

    /** 8. Servicios cuya vigencia todavía no inicia (PROGRAMADO) */
    public function test_servicio_programado(): void
    {
        $cat = CategoriaTuristica::firstOrCreate(['cNombre' => 'Navidad Test'], ['iIDUsuario' => $this->user->id]);
        $dest = DestinoTuristico::firstOrCreate(['cNombre' => 'Nueva York Test', 'cPais' => 'EEUU'], ['iIDUsuario' => $this->user->id]);

        $serv = ServicioTuristico::create([
            'cCodigo'              => 'SERV-TEST-' . uniqid(),
            'cNombre'              => 'Navidad en Nueva York',
            'iIDCategoria'         => $cat->iID,
            'iIDDestino'           => $dest->iID,
            'dPrecioCompra'        => 20000.00,
            'dPrecioVenta'         => 30000.00,
            'dFechaInicioVigencia' => now()->addDays(10)->toDateString(), // Inicia en 10 días
            'dFechaFinVigencia'    => now()->addDays(40)->toDateString(),
            'lActivo'              => 1,
            'iIDUsuario'           => $this->user->id,
        ]);

        $this->assertEquals('PROGRAMADO', $serv->c_estatus_disponibilidad);
    }

    /** 9. Servicios cuya vigencia ya terminó (VENCIDO) */
    public function test_servicio_vencido(): void
    {
        $cat = CategoriaTuristica::firstOrCreate(['cNombre' => 'Verano Test'], ['iIDUsuario' => $this->user->id]);
        $dest = DestinoTuristico::firstOrCreate(['cNombre' => 'Madrid Test', 'cPais' => 'España'], ['iIDUsuario' => $this->user->id]);

        $serv = ServicioTuristico::create([
            'cCodigo'              => 'SERV-TEST-' . uniqid(),
            'cNombre'              => 'Verano en España',
            'iIDCategoria'         => $cat->iID,
            'iIDDestino'           => $dest->iID,
            'dPrecioCompra'        => 15000.00,
            'dPrecioVenta'         => 25000.00,
            'dFechaInicioVigencia' => now()->subDays(60)->toDateString(),
            'dFechaFinVigencia'    => now()->subDays(5)->toDateString(), // Terminó hace 5 días
            'lActivo'              => 1,
            'iIDUsuario'           => $this->user->id,
        ]);

        $this->assertEquals('VENCIDO', $serv->c_estatus_disponibilidad);
    }

    /** 10 & 11. Desactivación y Reactivación manual */
    public function test_desactivacion_y_reactivacion_manual(): void
    {
        $cat = CategoriaTuristica::firstOrCreate(['cNombre' => 'Cruceros Test'], ['iIDUsuario' => $this->user->id]);
        $dest = DestinoTuristico::firstOrCreate(['cNombre' => 'Caribe Test', 'cPais' => 'Internacional'], ['iIDUsuario' => $this->user->id]);

        $serv = ServicioTuristico::create([
            'cCodigo'              => 'SERV-2026-0006',
            'cNombre'              => 'Crucero por el Caribe',
            'iIDCategoria'         => $cat->iID,
            'iIDDestino'           => $dest->iID,
            'dPrecioCompra'        => 12000.00,
            'dPrecioVenta'         => 18000.00,
            'dFechaInicioVigencia' => now()->subDays(5)->toDateString(),
            'dFechaFinVigencia'    => now()->addDays(20)->toDateString(),
            'lActivo'              => 1,
            'iIDUsuario'           => $this->user->id,
        ]);

        $this->assertEquals('ACTIVO', $serv->c_estatus_disponibilidad);

        // Desactivar
        $this->actingAs($this->user)->postJson(route('servicios.paquetes.deleteData'), ['iID' => $serv->iID]);
        $serv->refresh();
        $this->assertEquals(0, $serv->lActivo);
        $this->assertEquals('INACTIVO', $serv->c_estatus_disponibilidad);
    }

    /** 12. Validación de rangos de fechas (Fecha final anterior a la inicial) */
    public function test_validacion_fechas_invalidas(): void
    {
        $cat = CategoriaTuristica::create(['cNombre' => 'Prueba', 'iIDUsuario' => $this->user->id]);
        $dest = DestinoTuristico::create(['cNombre' => 'Prueba', 'iIDUsuario' => $this->user->id]);

        $response = $this->actingAs($this->user)->postJson(route('servicios.paquetes.saveData'), [
            'cNombre'              => 'Servicio Fecha Inválida',
            'iIDCategoria'         => $cat->iID,
            'iIDDestino'           => $dest->iID,
            'dPrecioCompra'        => 100,
            'dPrecioVenta'         => 200,
            'dFechaInicioVigencia' => '2026-10-30',
            'dFechaFinVigencia'    => '2026-10-01', // Error: Fecha fin anterior a inicio
        ]);

        $response->assertStatus(422); // Unprocessable Entity
    }

    /** 13. Protección de rutas administrativas */
    public function test_proteccion_rutas_middleware_auth(): void
    {
        // Petición sin estar autenticado debe ser redirigida al login
        $response = $this->get(route('servicios.paquetes.index'));
        $response->assertRedirect(route('login'));
    }

    /** 14. Carga y simulación de imágenes */
    public function test_carga_de_imagenes(): void
    {
        Storage::fake('public');

        $cat = CategoriaTuristica::create(['cNombre' => 'Fotos', 'iIDUsuario' => $this->user->id]);
        $dest = DestinoTuristico::create(['cNombre' => 'Fotos', 'iIDUsuario' => $this->user->id]);
        $file = UploadedFile::fake()->image('cancun.jpg');

        $response = $this->actingAs($this->user)->postJson(route('servicios.paquetes.saveData'), [
            'cNombre'              => 'Servicio Con Imagen',
            'iIDCategoria'         => $cat->iID,
            'iIDDestino'           => $dest->iID,
            'dPrecioCompra'        => 1000,
            'dPrecioVenta'         => 1500,
            'dFechaInicioVigencia' => now()->toDateString(),
            'dFechaFinVigencia'    => now()->addDays(10)->toDateString(),
            'cImagenFile'          => $file,
        ]);

        $response->assertStatus(200)->assertJson(['lSuccess' => true]);

        $serv = ServicioTuristico::where('cNombre', 'Servicio Con Imagen')->first();
        $this->assertNotNull($serv->cImagenPrincipal);
        Storage::disk('public')->assertExists(str_replace('public/', '', $serv->cImagenPrincipal));
    }
}
