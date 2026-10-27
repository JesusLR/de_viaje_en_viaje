<?php

namespace Tests\Feature;

use App\Models\{CategoriaTuristica, Cliente, Cotizacion, CotizacionServicio, DestinoTuristico, MovimientoFinanciero, Role, ServicioTuristico, User, VentaPaquete};
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class EstadisticasTest extends TestCase
{
    use DatabaseTransactions;

    private function servicio(): ServicioTuristico
    {
        $categoria = CategoriaTuristica::create(['cNombre' => uniqid('Estadisticas')]);
        $destino = DestinoTuristico::create(['cNombre' => uniqid('Destino')]);
        return ServicioTuristico::create(['cCodigo' => uniqid('EST'), 'cNombre' => 'Servicio de estadísticas', 'iIDCategoria' => $categoria->iID,
            'iIDDestino' => $destino->iID, 'lActivo' => true, 'lUsaVigencia' => false, 'cMoneda' => 'TST', 'dPrecioVenta' => 1000]);
    }

    private function venta(User $user, ServicioTuristico $servicio, array $extra = []): VentaPaquete
    {
        $cliente = Cliente::create(['cNombre' => 'Cliente estadísticas', 'cPrimerApellido' => 'Prueba', 'lActivo' => true]);
        $venta = VentaPaquete::create(array_merge(['iIDCliente' => $cliente->iID, 'iIDUsuario' => $user->id, 'iIDServicio' => $servicio->iID,
            'cPaquete' => $servicio->cNombre, 'cMoneda' => 'TST', 'iCantidad' => 2, 'dPrecioUnitario' => 500, 'dTotal' => 1000,
            'dFechaViaje' => '2000-02-01', 'cTipoOperacion' => 'VENTA', 'created_at' => '2000-01-05', 'finalizada_at' => '2000-01-20'], $extra));
        $venta->detalles()->create(['iIDServicio' => $servicio->iID, 'cPaquete' => $servicio->cNombre, 'iCantidad' => 2, 'iCantidadMenores' => 1,
            'dPrecioUnitario' => 450, 'dSubtotal' => 1000, 'dPrecioRegular' => 500, 'dDescuentoUnitario' => 50, 'dPrecioMenor' => 100]);
        return $venta;
    }

    private function filtros(array $extra = []): array
    {
        return array_merge(['desde' => '2000-01-01', 'hasta' => '2000-01-31', 'moneda' => 'TST'], $extra);
    }

    public function test_historical_mode_ignores_date_limits_but_preserves_ownership(): void
    {
        $user = User::factory()->create(['lCambiarPassword' => false]); $other = User::factory()->create();
        $servicio = $this->servicio();
        $this->venta($user, $servicio);
        $this->venta($user, $servicio, ['created_at' => '1999-11-01', 'finalizada_at' => '1999-12-31']);
        $this->venta($other, $servicio, ['created_at' => '1970-01-01', 'finalizada_at' => '1970-02-01']);
        $role = Role::create(['name' => 'Histórico propio', 'slug' => uniqid('historico'), 'permissions' => ['estadisticas.ver']]);
        $user->roles()->sync([$role->id]); $this->actingAs($user);
        $this->get(route('estadisticas.index', ['moneda' => 'TST']))->assertOk()->assertViewHas('datos', fn ($d) => $d['ventas']['cantidad'] === 0);
        $this->get(route('estadisticas.index', ['periodo' => 'historico', 'moneda' => 'TST', 'usuario' => $other->id, 'desde' => 'incorrecta', 'hasta' => 'incorrecta']))->assertOk()
            ->assertViewHas('datos', fn ($d) => $d['ventas']['cantidad'] === 2 && $d['ventas']['importe'] === 2000.0 && $d['agrupacion'] === 'anual')
            ->assertViewHas('filtros', fn ($f) => $f['desde'] === '1999-11-01' && (int) $f['usuario'] === $user->id)
            ->assertSee('Histórico de todos los tiempos');
    }

    public function test_statistics_separate_sales_and_payments_and_exclude_deleted_and_voided(): void
    {
        $user = User::factory()->create(['lCambiarPassword' => false]); $this->actingAs($user);
        $servicio = $this->servicio(); $venta = $this->venta($user, $servicio);
        foreach ([['2000-01-15', 400, null], ['2000-02-01', 600, null], ['2000-01-16', 70, now()]] as [$fecha, $importe, $anulado]) {
            $venta->pagos()->create(['dFechaPago' => $fecha, 'dImporte' => $importe, 'cMetodo' => 'Efectivo', 'anulado_at' => $anulado, 'iIDUsuario' => $user->id]);
        }
        $eliminada = $this->venta($user, $servicio, ['eliminada_at' => '2000-01-25']);
        $eliminada->pagos()->create(['dFechaPago' => '2000-01-25', 'dImporte' => 1000, 'cMetodo' => 'Efectivo']);
        foreach ([['INGRESO', 100, null], ['EGRESO', 200, null], ['EGRESO', 999, now()]] as [$tipo, $importe, $anulado]) {
            MovimientoFinanciero::create(['cTipo' => $tipo, 'dImporte' => $importe, 'cConcepto' => 'Prueba', 'dFecha' => '2000-01-10',
                'cMoneda' => 'TST', 'cMetodo' => 'Transferencia', 'iIDUsuario' => $user->id, 'anulado_at' => $anulado]);
        }
        $this->get(route('estadisticas.index', $this->filtros()))->assertOk()->assertViewHas('datos', function ($d) {
            return $d['ventas']['cantidad'] === 1 && $d['ventas']['importe'] === 1000.0 && $d['ventas']['eliminadas'] === 1
                && $d['ventas']['descuentos'] === 100.0 && $d['finanzas']['ingresos'] === 500.0 && $d['finanzas']['egresos'] === 200.0
                && $d['finanzas']['balance'] === 300.0 && $d['meses']['2000-01']['cobrado'] === 500.0 && $d['ranking']->first()->ventas === 1;
        })->assertSee('Estadísticas')->assertSee('Vendido y dinero cobrado');
        $this->venta($user, $servicio, ['cMoneda' => 'USD', 'dTotal' => 9999]);
        $this->get(route('estadisticas.index', $this->filtros()))->assertViewHas('datos', fn ($d) => $d['ventas']['cantidad'] === 1);
    }

    public function test_service_filter_uses_only_matching_lines_and_does_not_allocate_payments(): void
    {
        $user = User::factory()->create(['lCambiarPassword' => false]); $this->actingAs($user);
        $servicio = $this->servicio(); $otro = $this->servicio(); $venta = $this->venta($user, $servicio, ['dTotal' => 3000]);
        $venta->detalles()->create(['iIDServicio' => $otro->iID, 'cPaquete' => 'Otro servicio', 'iCantidad' => 1, 'dPrecioUnitario' => 2000, 'dSubtotal' => 2000]);
        $this->get(route('estadisticas.index', $this->filtros(['servicio' => $servicio->iID])))->assertOk()
            ->assertViewHas('datos', fn ($d) => $d['ventas']['importe'] === 1000.0 && $d['ventas']['cantidad'] === 1 && $d['finanzas'] === null && $d['web'] === null && $d['ranking']->count() === 1);
    }

    public function test_own_scope_is_enforced_and_financial_and_quote_permissions_are_separate(): void
    {
        $user = User::factory()->create(['lCambiarPassword' => false]); $other = User::factory()->create(['lCambiarPassword' => false]);
        $servicio = $this->servicio(); $this->venta($user, $servicio); $this->venta($other, $servicio);
        $role = Role::create(['name' => 'Estadísticas propias', 'slug' => uniqid('stats'), 'permissions' => ['estadisticas.ver']]);
        $user->roles()->sync([$role->id]); $this->actingAs($user);
        $this->get(route('estadisticas.index', $this->filtros(['usuario' => $other->id])))->assertOk()->assertViewHas('filtros', fn ($f) => (int) $f['usuario'] === $user->id)
            ->assertViewHas('datos', fn ($d) => $d['ventas']['cantidad'] === 1 && $d['finanzas'] === null && $d['cotizaciones'] === null)
            ->assertDontSee('Balance del periodo')->assertDontSee('Cotizaciones de servicios</h4>', false);
        $role->update(['permissions' => ['estadisticas.ver', 'estadisticas.todos', 'estadisticas.finanzas', 'estadisticas.cotizaciones']]); $user->unsetRelation('roles');
        $this->get(route('estadisticas.index', $this->filtros()))->assertOk()->assertViewHas('datos', fn ($d) => $d['ventas']['cantidad'] === 2 && $d['finanzas'] !== null && $d['cotizaciones'] !== null);
        $user->roles()->detach(); $user->unsetRelation('roles');
        $this->get(route('estadisticas.index'))->assertForbidden();
    }

    public function test_apartados_quotes_and_date_validation(): void
    {
        $user = User::factory()->create(['lCambiarPassword' => false]); $this->actingAs($user); $servicio = $this->servicio();
        $abierto = $this->venta($user, $servicio, ['cTipoOperacion' => 'APARTADO', 'finalizada_at' => null]);
        $abierto->pagos()->create(['dFechaPago' => '2000-01-15', 'dImporte' => 100, 'cMetodo' => 'Tarjeta']);
        $this->venta($user, $servicio, ['cTipoOperacion' => 'APARTADO']);
        $this->venta($user, $servicio, ['cTipoOperacion' => 'APARTADO', 'finalizada_at' => null, 'cancelada_at' => '2000-01-15']);
        foreach (['NUEVA', 'EN_ATENCION', 'ATENDIDO'] as $estado) {
            Cotizacion::create(['nombre' => 'Prueba', 'whatsapp' => '9991234567', 'fechas' => 'Febrero', 'dias' => 5, 'noches' => 4,
                'personas' => 2, 'adultos' => 2, 'menores' => 0, 'edades_menores' => [], 'tipo_hotel' => 'Familiar', 'ambiente' => 'Ambos', 'presupuesto' => 1000,
                'moneda' => 'TST', 'estado' => $estado, 'atendida_por' => $estado === 'NUEVA' ? null : $user->id,
                'created_at' => '2000-01-10 12:00:00', 'atendida_at' => $estado === 'ATENDIDO' ? '2000-01-11 12:00:00' : null]);
        }
        CotizacionServicio::create(['usuario_id' => $user->id, 'cliente_nombre' => 'Prueba', 'moneda' => 'TST', 'total' => 700,
            'created_at' => '2000-01-10', 'partidas' => [['servicio_id' => $servicio->iID, 'nombre' => 'Prueba', 'adultos' => 2, 'menores' => 0, 'subtotal' => 700]]]);
        $this->get(route('estadisticas.index', $this->filtros()))->assertOk()->assertViewHas('datos', fn ($d) => $d['apartados']['estados']['Abiertos'] === 1
            && $d['apartados']['estados']['Finalizados'] === 1 && $d['apartados']['estados']['Cancelados'] === 1 && $d['finanzas']['saldo'] === 900.0
            && $d['web']['total'] === 3 && $d['web']['horas'] === 24.0 && $d['cotizaciones']['importe'] === 700.0);
        $this->getJson(route('estadisticas.index', $this->filtros(['hasta' => '1999-01-01'])))->assertUnprocessable();
        $this->getJson(route('estadisticas.index', $this->filtros(['hasta' => '2005-01-01'])))->assertUnprocessable();
    }
}
