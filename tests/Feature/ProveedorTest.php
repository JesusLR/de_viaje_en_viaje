<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Proveedor;
use App\Models\CategoriaTuristica;
use App\Models\DestinoTuristico;
use App\Models\ServicioTuristico;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ProveedorTest extends TestCase
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

    /** Test 1: Crear y editar Proveedor (Nombre y Descripción) */
    public function test_crear_y_editar_proveedor(): void
    {
        $response = $this->actingAs($this->user)->postJson(route('catalogos.proveedores.saveData'), [
            'cNombre'      => 'Hotel Meliá Cancún',
            'cDescripcion' => 'Proveedor de hotelería todo incluido',
        ]);

        $response->assertStatus(200)->assertJson(['lSuccess' => true]);
        $this->assertDatabaseHas('proveedores', ['cNombre' => 'Hotel Meliá Cancún']);

        $prov = Proveedor::where('cNombre', 'Hotel Meliá Cancún')->first();

        $editResponse = $this->actingAs($this->user)->postJson(route('catalogos.proveedores.saveData'), [
            'iID'          => $prov->iID,
            'cNombre'      => 'Hotel Meliá Cancún VIP',
            'cDescripcion' => 'Descripción actualizada de hotel resort',
        ]);

        $editResponse->assertStatus(200)->assertJson(['lSuccess' => true]);
        $this->assertDatabaseHas('proveedores', ['cNombre' => 'Hotel Meliá Cancún VIP']);
    }

    /** Test 2: Asignar Proveedor a un Servicio Turístico */
    public function test_asignar_proveedor_a_servicio_turistico(): void
    {
        $prov = Proveedor::create([
            'cNombre'      => 'Aeroméxico Test',
            'cDescripcion' => 'Aerolínea nacional e internacional',
            'iIDUsuario'   => $this->user->id,
        ]);

        $cat = CategoriaTuristica::firstOrCreate(['cNombre' => 'Vuelos Test'], ['iIDUsuario' => $this->user->id]);
        $dest = DestinoTuristico::firstOrCreate(['cNombre' => 'CDMX Test', 'cPais' => 'México'], ['iIDUsuario' => $this->user->id]);

        $response = $this->actingAs($this->user)->postJson(route('servicios.paquetes.saveData'), [
            'cNombre'              => 'Paquete Vuelo CDMX',
            'iIDCategoria'         => $cat->iID,
            'iIDDestino'           => $dest->iID,
            'iIDProveedor'         => $prov->iID,
            'dPrecioCompra'        => 3000.00,
            'dPrecioVenta'         => 4500.00,
            'dFechaInicioVigencia' => now()->toDateString(),
            'dFechaFinVigencia'    => now()->addDays(30)->toDateString(),
        ]);

        $response->assertStatus(200)->assertJson(['lSuccess' => true]);

        $serv = ServicioTuristico::where('cNombre', 'Paquete Vuelo CDMX')->first();
        $this->assertNotNull($serv);
        $this->assertEquals($prov->iID, $serv->iIDProveedor);
        $this->assertEquals('Aeroméxico Test', $serv->proveedor->cNombre);
    }
}
