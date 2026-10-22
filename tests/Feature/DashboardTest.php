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
        $serviciosAntes = $antes->viewData('serviciosActivos');
        $servicio = ServicioTuristico::create(['cCodigo' => uniqid('DASH'), 'cNombre' => 'Servicio dashboard', 'dPrecioVenta' => 1000, 'lUsaVigencia' => false, 'lActivo' => true]);
        $servicio->update(['created_at' => today()->subMonths(3)]);
        ServicioTuristico::create(['cCodigo' => uniqid('OFF'), 'cNombre' => 'Inactivo', 'lUsaVigencia' => false, 'lActivo' => false]);
        ServicioTuristico::create(['cCodigo' => uniqid('EXP'), 'cNombre' => 'Vencido', 'lUsaVigencia' => true, 'lActivo' => true, 'dFechaInicioVigencia' => today()->subDays(3), 'dFechaFinVigencia' => today()->subDay()]);
        ServicioTuristico::create(['cCodigo' => uniqid('NEXT'), 'cNombre' => 'Programado', 'lUsaVigencia' => true, 'lActivo' => true, 'dFechaInicioVigencia' => today()->addDay(), 'dFechaFinVigencia' => today()->addWeek()]);
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
        $this->get(route('dashboard'))->assertOk()->assertSee('Finanzas del mes')->assertSee('Apartados abiertos')->assertSee('Servicios activos')
            ->assertViewHas('apartadosAbiertos', $abiertosAntes + 1)->assertViewHas('serviciosActivos', $serviciosAntes + 1)
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

    public function test_dashboard_muestra_las_cinco_salidas_activas_mas_cercanas(): void
    {
        $this->travelTo(\Carbon\Carbon::parse('2035-06-15 12:00:00'));
        $this->actingAs(User::factory()->create(['lCambiarPassword' => false]));
        $crear = function (int $dias, array $extra = []) {
            return ServicioTuristico::create(array_merge([
                'cCodigo' => uniqid('VIAJE'), 'cNombre' => 'Viaje próximo '.$dias,
                'dPrecioVenta' => 1000, 'lActivo' => true, 'lUsaVigencia' => true,
                'dFechaInicioViaje' => today()->addDays($dias),
                'dFechaFinViaje' => today()->addDays($dias + 3),
            ], $extra));
        };
        $crear(-1);
        $crear(0, ['lActivo' => false]);
        $crear(0, ['dFechaInicioViaje' => null]);
        $crear(0, ['dFechaFinViaje' => null]);
        $crear(0, ['dFechaFinViaje' => today()->subDay()]);
        $ids = [];
        foreach ([8, 4, 0, 2, 1, 3] as $dias) {
            $ids[$dias] = $crear($dias)->iID;
        }
        $this->get(route('dashboard'))->assertOk()->assertSee('Próximos viajes')
            ->assertSee('Sale hoy')->assertSee('Sale mañana')->assertSee('15/06/2035')
            ->assertSee('18/06/2035')->assertViewHas('proximosViajes',
                fn ($viajes) => $viajes->pluck('iID')->all() === [$ids[0], $ids[1], $ids[2], $ids[3], $ids[4]]);
        $this->travelBack();
    }
}
