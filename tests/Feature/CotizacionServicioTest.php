<?php

namespace Tests\Feature;

use App\Models\{CategoriaTuristica, Cliente, Cotizacion, CotizacionServicio, DestinoTuristico, PagoVenta, Role, ServicioTuristico, User, VentaPaquete};
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class CotizacionServicioTest extends TestCase
{
    use DatabaseTransactions;

    private function servicio(): ServicioTuristico
    {
        $this->actingAs(User::factory()->create(['lCambiarPassword' => false]));
        $categoria = CategoriaTuristica::create(['cNombre' => uniqid('Cotizacion')]);
        $destino = DestinoTuristico::create(['cNombre' => uniqid('Destino')]);
        return ServicioTuristico::create(['cCodigo' => uniqid('COT'), 'cNombre' => 'Riviera Maya prueba',
            'iIDCategoria' => $categoria->iID, 'iIDDestino' => $destino->iID,
            'lActivo' => true, 'lUsaVigencia' => false, 'cMoneda' => 'MXN', 'dPrecioVenta' => 1000,
            'lPrecioMenorActivo' => true, 'dPrecioMenor' => 200,
            'dFechaInicioViaje' => today()->addWeek(), 'dFechaFinViaje' => today()->addDays(10)]);
    }

    private function datos(ServicioTuristico $servicio): array
    {
        return ['tipo_cliente' => 'generico', 'cliente_nombre' => 'Familia de prueba', 'cNombre' => 'Vacaciones familiares',
            'items' => [['iIDServicio' => $servicio->iID, 'iCantidad' => 2, 'iCantidadMenores' => 1]]];
    }

    public function test_quote_is_saved_without_sale_or_payment_and_keeps_prices_for_pdf(): void
    {
        $servicio = $this->servicio();
        $ventas = VentaPaquete::count(); $pagos = PagoVenta::count();
        $this->get(route('atencion.cotizaciones.create'))->assertOk()->assertSee('Nombre genérico')->assertSee('cliente_nombre', false);
        $this->post(route('atencion.cotizaciones.store'), $this->datos($servicio) + ['total' => 1])->assertSessionHasNoErrors();
        $cotizacion = CotizacionServicio::latest('id')->firstOrFail();
        $this->assertEquals('2200.00', $cotizacion->total);
        $this->assertNull($cotizacion->cliente_id);
        $this->assertEquals($ventas, VentaPaquete::count()); $this->assertEquals($pagos, PagoVenta::count());
        $servicio->update(['dPrecioVenta' => 9999, 'cNombre' => 'Nombre nuevo']);
        $this->get(route('atencion.cotizaciones.show', $cotizacion))->assertOk()->assertSee('2,200.00')->assertSee('Riviera Maya prueba')->assertDontSee('Nombre nuevo');
        $pdf = $this->get(route('atencion.cotizaciones.pdf', $cotizacion))->assertOk()->assertDownload('cotizacion-'.$cotizacion->folio.'.pdf');
        $this->assertStringStartsWith('%PDF-', $pdf->getContent());
        $this->get(route('atencion.cotizaciones.index', ['buscar' => 'Familia de prueba']))->assertOk()->assertSee($cotizacion->folio);
    }

    public function test_registered_client_and_service_validation(): void
    {
        $servicio = $this->servicio();
        $cliente = Cliente::create(['cNombre' => 'Ana', 'cPrimerApellido' => 'Prueba', 'lActivo' => true]);
        $data = $this->datos($servicio);
        $data['tipo_cliente'] = 'registrado'; $data['iIDCliente'] = $cliente->iID;
        $this->post(route('atencion.cotizaciones.store'), $data)->assertSessionHasNoErrors();
        $cotizacion = CotizacionServicio::latest('id')->firstOrFail();
        $this->assertEquals($cliente->iID, $cotizacion->cliente_id);
        $this->assertSame($cliente->nombre_completo, $cotizacion->cliente_nombre);
        $servicio->update(['lActivo' => false]);
        $this->postJson(route('atencion.cotizaciones.store'), $data)->assertUnprocessable();
        $this->get(route('atencion.cotizaciones.create'))->assertOk()->assertDontSee($servicio->cCodigo);
        $servicio->update(['lActivo' => true, 'lPrecioMenorActivo' => false]);
        $this->postJson(route('atencion.cotizaciones.store'), $data)->assertUnprocessable();
        $servicio->update(['lPrecioMenorActivo' => true, 'dPrecioMenor' => 0]);
        $this->post(route('atencion.cotizaciones.store'), $data)->assertSessionHasNoErrors();
        $this->assertEquals('2000.00', CotizacionServicio::latest('id')->first()->total);
    }

    public function test_separate_permissions_protect_quotes_create_and_pdf(): void
    {
        $servicio = $this->servicio();
        $this->post(route('atencion.cotizaciones.store'), $this->datos($servicio))->assertSessionHasNoErrors();
        $cotizacion = CotizacionServicio::latest('id')->firstOrFail();
        $user = auth()->user();
        $role = Role::create(['name' => 'Solo web', 'slug' => uniqid('web'), 'permissions' => ['cotizaciones.ver']]);
        $user->roles()->sync([$role->id]); $user->unsetRelation('roles');
        $this->get(route('atencion.cotizaciones.index'))->assertForbidden();
        $role->update(['permissions' => ['cotizaciones_servicios.ver']]); $user->unsetRelation('roles');
        $this->get(route('atencion.cotizaciones.show', $cotizacion))->assertOk()->assertDontSee('Descargar PDF');
        $this->get(route('atencion.cotizaciones.create'))->assertForbidden();
        $this->postJson(route('atencion.cotizaciones.store'), $this->datos($servicio))->assertForbidden();
        $this->get(route('atencion.cotizaciones.pdf', $cotizacion))->assertForbidden();
    }

    public function test_dashboard_counts_only_new_web_requests_and_respects_permissions(): void
    {
        $this->servicio();
        $before = Cotizacion::where('estado', 'NUEVA')->count();
        foreach (['NUEVA', 'NUEVA', 'EN_ATENCION', 'ATENDIDO', 'CANCELADO'] as $estado) {
            Cotizacion::create(['nombre' => 'Web prueba', 'whatsapp' => '9991234567', 'fechas' => 'Diciembre', 'dias' => 5, 'noches' => 4,
                'personas' => 2, 'adultos' => 2, 'menores' => 0, 'edades_menores' => [], 'tipo_hotel' => 'Todo incluido',
                'ambiente' => 'Ambos', 'presupuesto' => 10000, 'moneda' => 'MXN', 'estado' => $estado]);
        }
        $this->get(route('dashboard'))->assertOk()->assertViewHas('cotizacionesWebPendientes', $before + 2)->assertSee('Cotizaciones web pendientes de atención');
        $user = auth()->user(); $user->roles()->detach(); $user->unsetRelation('roles');
        $this->get(route('dashboard'))->assertOk()->assertViewHas('cotizacionesWebPendientes', 0)->assertDontSee('Cotizaciones web pendientes de atención');
    }
}
