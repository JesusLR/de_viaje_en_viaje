<?php

namespace Tests\Feature;

use App\Models\{Cliente, MovimientoFinanciero, ServicioTuristico, User, VentaPaquete};
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use DatabaseTransactions;

    public function test_dashboard_cuenta_apartados_y_servicios_y_suma_finanzas_sin_anulados(): void
    {
        $this->actingAs(User::factory()->create(['lCambiarPassword' => false]));
        $antes = $this->get(route('dashboard'))->assertOk();
        $abiertosAntes = $antes->viewData('apartadosAbiertos');
        $serviciosAntes = $antes->viewData('serviciosMes');
        $servicio = ServicioTuristico::create(['cCodigo' => uniqid('DASH'), 'cNombre' => 'Servicio dashboard', 'dPrecioVenta' => 1000, 'lUsaVigencia' => false, 'lActivo' => true]);
        $cliente = Cliente::create(['cNombre' => 'Cliente', 'cPrimerApellido' => 'Dashboard']);
        $datos = ['iIDCliente' => $cliente->iID, 'iIDServicio' => $servicio->iID, 'cPaquete' => $servicio->cNombre, 'cMoneda' => 'TST', 'iCantidad' => 1, 'dPrecioUnitario' => 1000, 'dTotal' => 1000, 'cTipoOperacion' => 'APARTADO'];
        $abierto = VentaPaquete::create($datos);
        $abierto->pagos()->create(['dImporte' => 200, 'cMetodo' => 'Efectivo', 'dFechaPago' => today()]);
        $abierto->pagos()->create(['dImporte' => 50, 'cMetodo' => 'Efectivo', 'dFechaPago' => today(), 'anulado_at' => now()]);
        $abierto->pagos()->create(['dImporte' => 100, 'cMetodo' => 'Efectivo', 'dFechaPago' => today()->startOfMonth()->subDay()]);
        VentaPaquete::create($datos + ['cancelada_at' => now()]);
        $pagado = VentaPaquete::create($datos);
        $pagado->pagos()->create(['dImporte' => 1000, 'cMetodo' => 'Transferencia', 'dFechaPago' => today()]);
        VentaPaquete::create($datos + ['finalizada_at' => now()]);
        foreach ([['INGRESO', 300, null], ['EGRESO', 400, null], ['INGRESO', 900, now()]] as [$tipo, $importe, $anulado]) {
            MovimientoFinanciero::create(['cTipo' => $tipo, 'cConcepto' => 'Prueba dashboard', 'dImporte' => $importe, 'cMoneda' => 'TST', 'cMetodo' => 'Efectivo', 'dFecha' => today(), 'anulado_at' => $anulado]);
        }
        MovimientoFinanciero::create(['cTipo' => 'INGRESO', 'cConcepto' => 'Otra moneda', 'dImporte' => 25, 'cMoneda' => 'TSU', 'cMetodo' => 'Efectivo', 'dFecha' => today()]);
        $this->get(route('dashboard'))->assertOk()->assertSee('Finanzas del mes')->assertSee('Apartados abiertos')->assertSee('Servicios del mes')
            ->assertViewHas('apartadosAbiertos', $abiertosAntes + 1)->assertViewHas('serviciosMes', $serviciosAntes + 1)
            ->assertViewHas('finanzas', fn ($finanzas) => $finanzas['TST']['ingresos'] == 1500 && $finanzas['TST']['egresos'] == 400 && $finanzas['TST']['balance'] == 1100 && $finanzas['TST']['abonos'] == 1200 && $finanzas['TST']['ventas'] == 0 && $finanzas['TST']['manuales'] == 300 && $finanzas['TSU']['ingresos'] == 25);
        $pagado->update(['finalizada_at' => now()]);
        $this->get(route('dashboard'))->assertOk()
            ->assertSee('Del '.today()->startOfMonth()->format('d/m/Y').' al '.today()->endOfMonth()->format('d/m/Y'))
            ->assertDontSee('Cobros y movimientos del mes')
            ->assertViewHas('finanzas', fn ($finanzas) => $finanzas['TST']['ingresos'] == 1500 && $finanzas['TST']['ventas'] == 1000 && $finanzas['TST']['abonos'] == 200);
    }

    public function test_dashboard_requiere_autenticacion(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
    }
}
