<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\CotizacionServicio;
use App\Models\Role;
use App\Models\ServicioTuristico;
use App\Models\User;
use App\Models\VentaPaquete;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

class OpcionesPrecioTest extends TestCase
{
    use DatabaseTransactions;

    private function servicio(): ServicioTuristico
    {
        Http::preventStrayRequests();
        $this->actingAs(User::factory()->create(['lCambiarPassword' => false]));
        $this->postJson(route('servicios.paquetes.saveData'), [
            'cNombre' => 'Hotel opciones '.uniqid(), 'dPrecioCompra' => 700, 'dPrecioVenta' => 1000,
            'lActivo' => 1, 'lUsaVigencia' => 0, 'lOpcionesPrecio' => 1, 'lPrecioMenorActivo' => 1, 'dPrecioMenor' => 150,
            'opciones_precio' => [
                ['id' => (string) Str::uuid(), 'nombre' => 'Doble', 'descripcion' => 'Tres noches con desayuno', 'cama' => 'Dos camas matrimoniales',
                    'unidad' => 'HABITACION', 'adultos' => 2, 'menores' => 2, 'dPrecioCompra' => 700, 'dPrecioVenta' => 1000, 'activo' => 1],
                ['id' => (string) Str::uuid(), 'nombre' => 'Suite por persona', 'descripcion' => '', 'cama' => 'King size',
                    'lPrecioMenorActivo' => true, 'dPrecioMenor' => 150, 'unidad' => 'PERSONA', 'adultos' => 2, 'menores' => 1, 'dPrecioCompra' => 1200, 'dPrecioVenta' => 2000, 'activo' => 1],
            ],
        ])->assertOk()->assertJsonPath('lSuccess', true);

        return ServicioTuristico::latest('iID')->firstOrFail();
    }

    private function venta(ServicioTuristico $servicio): array
    {
        $cliente = Cliente::create(['cNombre' => 'Cliente tarifas', 'cPrimerApellido' => 'Prueba', 'lActivo' => true]);

        return ['iIDCliente' => $cliente->iID, 'accion' => 'apartar', 'items' => [
            ['iIDServicio' => $servicio->iID, 'opcion_id' => $servicio->opciones_precio[0]['id'], 'iCantidad' => 2],
        ]];
    }

    public function test_opciones_se_guardan_y_catalogo_no_revela_costos(): void
    {
        $servicio = $this->servicio();
        $this->assertEquals(1000, $servicio->dPrecioVenta);
        $this->getJson(route('servicios.paquetes.getData', $servicio->iID))->assertOk()
            ->assertJsonPath('cData.lOpcionesPrecio', true)->assertJsonCount(2, 'cData.opciones_precio')
            ->assertJsonCount(2, 'cData.tarifas_detalle')->assertJsonPath('cData.tarifas_detalle.1.precio', 2000);
        $this->get(route('catalogo.publico.show', $servicio->iID))->assertOk()
            ->assertSee('Desde')->assertSee('Doble')->assertSee('Suite por persona')->assertSee('2,000.00')
            ->assertSee('Dos camas matrimoniales')->assertDontSee('700.00')->assertDontSee('1,200.00');
        $html = view('agencia.ventas.opciones-paquetes', ['paquetes' => collect([$servicio])])->render();
        $this->assertStringNotContainsString('dPrecioCompra', $html);
        $this->assertStringContainsString('data-opciones=', $html);
    }

    public function test_habitaciones_se_cobran_por_estancia_y_pdf_conserva_opcion(): void
    {
        $servicio = $this->servicio();
        $servicio->update(['lTienePromocion' => true, 'lPromocionActiva' => true, 'cTipoDescuento' => 'PORCENTAJE', 'dValorDescuento' => 10,
            'dFechaInicioPromocion' => now()->subDay(), 'dFechaFinPromocion' => now()->addDay()]);
        $this->post(route('agencia.ventas.store'), $this->venta($servicio))->assertSessionHasNoErrors();
        $venta = VentaPaquete::latest('iID')->firstOrFail();
        $this->assertEquals(1800, $venta->dTotal);
        $this->assertEquals('HABITACION', $venta->detalles->first()->opcion_precio['unidad']);
        $opciones = $servicio->opciones_precio;
        $opciones[0]['nombre'] = 'Nombre modificado';
        $opciones[0]['dPrecioVenta'] = 9999;
        $servicio->update(['opciones_precio' => $opciones]);
        $this->get(route('agencia.ventas.show', ['venta' => $venta, 'seccion' => 'detalle']))->assertOk()->assertSee('Doble')->assertSee('habitaciones')->assertDontSee('Nombre modificado');
        $html = view('agencia.ventas.comprobante-pdf', ['venta' => $venta->load(['cliente', 'detalles', 'usuario', 'pagos']), 'logo' => null, 'compraSaldada' => false])->render();
        foreach (['Doble', 'habitaciones', '900.00', '1,800.00'] as $texto) {
            $this->assertStringContainsString($texto, $html);
        }
        $this->get(route('agencia.ventas.comprobante', $venta))->assertOk();
    }

    public function test_cotizacion_permite_diferentes_opciones_del_mismo_servicio_y_tarifa_menor(): void
    {
        $servicio = $this->servicio();
        $items = $this->venta($servicio)['items'];
        $items[] = ['iIDServicio' => $servicio->iID, 'opcion_id' => $servicio->opciones_precio[1]['id'], 'iCantidad' => 2, 'iCantidadMenores' => 1];
        $this->post(route('atencion.cotizaciones.store'), ['tipo_cliente' => 'generico', 'cliente_nombre' => 'Familia prueba', 'items' => $items])->assertSessionHasNoErrors();
        $cotizacion = CotizacionServicio::latest('id')->firstOrFail();
        $this->assertEquals(6150, $cotizacion->total);
        $this->get(route('atencion.cotizaciones.show', $cotizacion))->assertOk()->assertSee('habitaciones')->assertSee('Suite por persona');
        $html = view('atencion.cotizaciones.pdf', ['cotizacion' => $cotizacion->load('usuario'), 'marca' => 'Agencia', 'logo' => null])->render();
        foreach (['Doble', 'habitaciones', 'Suite por persona', '6,150.00'] as $texto) {
            $this->assertStringContainsString($texto, $html);
        }
        $this->get(route('atencion.cotizaciones.pdf', $cotizacion))->assertOk();
    }

    public function test_rechaza_opciones_inactivas_ajenas_o_sin_seleccionar_y_menores_que_excedan_capacidad(): void
    {
        $servicio = $this->servicio();
        $data = $this->venta($servicio);
        foreach ([null, (string) Str::uuid()] as $id) {
            $data['items'][0]['opcion_id'] = $id;
            $this->postJson(route('agencia.ventas.store'), $data)->assertUnprocessable();
        }
        $data['items'][0]['opcion_id'] = $servicio->opciones_precio[0]['id'];
        $data['items'][0]['iCantidadMenores'] = 5;
        $this->postJson(route('agencia.ventas.store'), $data)->assertUnprocessable();
        $opciones = $servicio->opciones_precio;
        $opciones[0]['activo'] = false;
        $servicio->update(['opciones_precio' => $opciones]);
        unset($data['items'][0]['iCantidadMenores']);
        $this->postJson(route('agencia.ventas.store'), $data)->assertUnprocessable();
        $this->postJson(route('servicios.paquetes.saveData'), ['cNombre' => 'Sin tarifas', 'lOpcionesPrecio' => 1, 'dPrecioCompra' => 0, 'dPrecioVenta' => 0, 'lUsaVigencia' => 0])->assertUnprocessable();
    }

    public function test_permisos_protegen_precios_y_costos_de_cada_opcion(): void
    {
        $servicio = $this->servicio();
        $rol = Role::create(['name' => 'Editar servicio', 'slug' => uniqid('tarifas'), 'permissions' => ['servicios.ver', 'servicios.editar']]);
        $usuario = auth()->user();
        $usuario->roles()->sync([$rol->id]);
        $usuario->unsetRelation('roles');
        $this->getJson(route('servicios.paquetes.getData', $servicio->iID))->assertOk()
            ->assertJsonMissingPath('cData.opciones_precio.0.dPrecioCompra');
        $datos = ['iID' => $servicio->iID, 'cNombre' => $servicio->cNombre, 'dPrecioVenta' => 1000, 'lUsaVigencia' => 0, 'lPrecioMenorActivo' => 1, 'dPrecioMenor' => 150];
        $sinCostos = array_map(fn ($opcion) => array_diff_key($opcion, ['dPrecioCompra' => true]), $servicio->opciones_precio);
        $this->postJson(route('servicios.paquetes.saveData'), $datos + ['opciones_precio' => $sinCostos])->assertOk()->assertJsonPath('lSuccess', true);
        $opciones = $servicio->opciones_precio;
        $opciones[1]['dPrecioVenta'] = 1;
        $this->postJson(route('servicios.paquetes.saveData'), $datos + ['opciones_precio' => $opciones])->assertForbidden();
        $opciones = $servicio->opciones_precio;
        $opciones[0]['dPrecioCompra'] = 1;
        $this->postJson(route('servicios.paquetes.saveData'), $datos + ['opciones_precio' => $opciones])->assertForbidden();
        $this->postJson(route('servicios.paquetes.saveData'), $datos)->assertOk()->assertJsonPath('lSuccess', true);
        $this->assertSame($servicio->opciones_precio, $servicio->fresh()->opciones_precio);
    }

    public function test_agregar_opcion_al_apartado_y_venta_directa_con_promocion(): void
    {
        $servicio = $this->servicio();
        $datos = $this->venta($servicio);
        $this->post(route('agencia.ventas.store'), $datos)->assertSessionHasNoErrors();
        $apartado = VentaPaquete::latest('iID')->firstOrFail();
        $this->post(route('agencia.ventas.agregarPaquete', $apartado), ['iIDServicio' => $servicio->iID,
            'opcion_id' => $servicio->opciones_precio[1]['id'], 'iCantidad' => 1, 'iCantidadMenores' => 1])->assertSessionHasNoErrors();
        $this->assertEquals(4150, $apartado->fresh()->dTotal);
        $this->assertEquals(2, $apartado->detalles()->count());
        $datos['accion'] = 'vender';
        $datos['cMetodo'] = 'Efectivo';
        $datos['cTipoPromocionVenta'] = 'PORCENTAJE';
        $datos['dValorPromocionVenta'] = 10;
        $this->post(route('agencia.ventas.store'), $datos)->assertSessionHasNoErrors();
        $venta = VentaPaquete::latest('iID')->firstOrFail();
        $this->assertEquals(1800, $venta->dTotal);
        $this->assertEquals(1800, $venta->pagos()->sum('dImporte'));
        $this->get(route('agencia.ventas.comprobanteCompra', $venta))->assertOk();
        $this->get(route('estadisticas.index', ['servicio' => $servicio->iID]))->assertOk()
            ->assertViewHas('datos', fn ($d) => $d['ventas']['adultos'] === 0 && $d['ventas']['habitaciones'] === 2);
    }

    public function test_crear_opciones_sin_uuid_y_actualizar_sin_cambiar_su_identidad(): void
    {
        $servicio = $this->servicio();
        $opciones = $servicio->opciones_precio;
        foreach ($opciones as &$opcion) {
            $opcion['id'] = null;
        }
        unset($opcion);
        $datos = ['iID' => $servicio->iID, 'cNombre' => $servicio->cNombre, 'dPrecioVenta' => 1000, 'dPrecioCompra' => 700, 'lUsaVigencia' => 0, 'lOpcionesPrecio' => 1, 'opciones_precio' => $opciones];
        $this->postJson(route('servicios.paquetes.saveData'), $datos)->assertOk()->assertJsonPath('lSuccess', true);
        $guardadas = $servicio->fresh()->opciones_precio;
        $this->assertNotEmpty($guardadas[0]['id']);
        $this->assertNotEquals($guardadas[0]['id'], $guardadas[1]['id']);
        $datos['opciones_precio'] = $guardadas;
        $this->postJson(route('servicios.paquetes.saveData'), $datos)->assertOk();
        $this->assertSame($guardadas, $servicio->fresh()->opciones_precio);
    }

    public function test_precio_menor_es_independiente_por_opcion_y_permite_cero(): void
    {
        $servicio = $this->servicio();
        foreach ([275, 0] as $precio) {
            $opciones = $servicio->opciones_precio;
            $opciones[0]['lPrecioMenorActivo'] = true;
            $opciones[0]['dPrecioMenor'] = $precio;
            $this->postJson(route('servicios.paquetes.saveData'), ['iID' => $servicio->iID,
                'cNombre' => $servicio->cNombre, 'dPrecioVenta' => 1000, 'dPrecioCompra' => 700,
                'lUsaVigencia' => 0, 'lOpcionesPrecio' => 1, 'opciones_precio' => $opciones])->assertOk()->assertJsonPath('lSuccess', true);
            $servicio->refresh();
            $datos = $this->venta($servicio);
            $datos['items'][0]['iCantidadMenores'] = 2;
            $this->post(route('agencia.ventas.store'), $datos)->assertSessionHasNoErrors();
            $venta = VentaPaquete::latest('iID')->firstOrFail();
            $this->assertEquals(2000 + 2 * $precio, $venta->dTotal);
            $detalle = $venta->detalles->first();
            $this->assertEquals($precio, $detalle->dPrecioMenor);
            $this->assertEquals(2, $detalle->iCantidadMenores);
            $this->assertTrue($detalle->opcion_precio['lPrecioMenorActivo']);
            $this->get(route('catalogo.publico.show', $servicio->iID))->assertOk()->assertSee('Menor: '.number_format($precio, 2));
            $this->post(route('atencion.cotizaciones.store'), ['tipo_cliente' => 'generico', 'cliente_nombre' => 'Familia menor', 'items' => $datos['items']])->assertSessionHasNoErrors();
            $cotizacion = CotizacionServicio::latest('id')->firstOrFail();
            $this->assertEquals($venta->dTotal, $cotizacion->total);
            $this->get(route('atencion.cotizaciones.pdf', $cotizacion))->assertOk();
            $this->get(route('agencia.ventas.comprobante', $venta))->assertOk();
        }
    }

    public function test_opcion_sin_tarifa_menor_no_hereda_precio_global_y_el_snapshot_no_cambia(): void
    {
        $servicio = $this->servicio();
        $opciones = $servicio->opciones_precio;
        $opciones[1]['lPrecioMenorActivo'] = false;
        $servicio->update(['opciones_precio' => $opciones]);
        $datos = $this->venta($servicio);
        $datos['items'][0]['opcion_id'] = $opciones[1]['id'];
        $datos['items'][0]['iCantidadMenores'] = 1;
        $this->postJson(route('agencia.ventas.store'), $datos)->assertUnprocessable();
        $opciones[1]['lPrecioMenorActivo'] = true;
        $opciones[1]['dPrecioMenor'] = 325;
        $servicio->update(['opciones_precio' => $opciones]);
        $this->post(route('agencia.ventas.store'), $datos)->assertSessionHasNoErrors();
        $venta = VentaPaquete::latest('iID')->firstOrFail();
        $opciones[1]['dPrecioMenor'] = 999;
        $servicio->update(['opciones_precio' => $opciones]);
        $this->assertEquals(325, $venta->detalles->first()->dPrecioMenor);
        $this->assertEquals(325, $venta->detalles->first()->opcion_precio['dPrecioMenor']);
    }

    public function test_registra_cantidad_real_de_menores_incluidos_sin_cobrarlos_otra_vez(): void
    {
        $servicio = $this->servicio();
        $datos = $this->venta($servicio);
        $datos['items'][0]['iCantidadMenores'] = 3;
        $this->post(route('agencia.ventas.store'), $datos)->assertSessionHasNoErrors();
        $venta = VentaPaquete::latest('iID')->firstOrFail();
        $this->assertEquals(2000, $venta->dTotal);
        $this->assertEquals(3, $venta->detalles->first()->iCantidadMenores);
        $this->assertEquals(0, $venta->detalles->first()->dPrecioMenor);
        $this->assertTrue($venta->detalles->first()->opcion_precio['menoresIncluidos']);
        $html = view('agencia.ventas.comprobante-pdf', ['venta' => $venta->load(['cliente', 'detalles', 'usuario', 'pagos']), 'logo' => null, 'compraSaldada' => false])->render();
        $this->assertStringContainsString('3 menores', $html);
        $this->assertStringContainsString('Menores incluidos', $html);
        $this->post(route('atencion.cotizaciones.store'), ['tipo_cliente' => 'generico', 'cliente_nombre' => 'Familia incluida', 'items' => $datos['items']])->assertSessionHasNoErrors();
        $cotizacion = CotizacionServicio::latest('id')->firstOrFail();
        $this->assertEquals(2000, $cotizacion->total);
        $this->assertEquals(3, $cotizacion->partidas[0]['menores']);
        $this->assertEquals(0, $cotizacion->partidas[0]['precio_menor']);
    }

    public function test_tarifa_persona_con_menores_no_requiere_capacidad_de_habitacion(): void
    {
        $this->servicio();
        foreach ([250, 0, null] as $precioMenor) {
            $opcion = ['nombre' => 'Persona con niños', 'unidad' => 'PERSONA', 'dPrecioVenta' => 1500,
                'lPrecioMenorActivo' => 1, 'dPrecioMenor' => $precioMenor, 'activo' => 1];
            $respuesta = $this->postJson(route('servicios.paquetes.saveData'), ['cNombre' => 'Tarifa persona '.uniqid(),
                'lOpcionesPrecio' => 1, 'lUsaVigencia' => 0, 'dPrecioCompra' => 0, 'dPrecioVenta' => 1500,
                'opciones_precio' => [$opcion]])->assertOk()->assertJsonPath('lSuccess', true);
            $servicio = ServicioTuristico::findOrFail($respuesta->json('cData.iID'));
            $this->assertEquals($precioMenor ?? 0, $servicio->opciones_precio[0]['dPrecioMenor']);
            $this->assertTrue($servicio->opciones_precio[0]['lPrecioMenorActivo']);
            $this->get(route('catalogo.publico.show', $servicio->iID))->assertOk()->assertSee('Opciones de precio')
                ->assertSee('catalogo-tarifa-importes')->assertSee('Menor: '.number_format($precioMenor ?? 0, 2));
        }
    }
}
