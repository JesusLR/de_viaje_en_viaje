<?php

namespace Tests\Feature;

use App\Models\CategoriaTuristica;
use App\Models\Cliente;
use App\Models\DestinoTuristico;
use App\Models\MovimientoFinanciero;
use App\Models\ServicioTuristico;
use App\Models\User;
use App\Models\VentaPaquete;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class VentaPaqueteTest extends TestCase
{
    use DatabaseTransactions;

    private function datos(): array
    {
        $this->actingAs(User::factory()->create(['lCambiarPassword' => 0]));
        $cliente = Cliente::create(['cNombre' => 'Cliente', 'cPrimerApellido' => 'Prueba', 'lActivo' => true]);
        $categoria = CategoriaTuristica::create(['cNombre' => 'Ventas '.uniqid()]);
        $destino = DestinoTuristico::create(['cNombre' => 'Destino '.uniqid()]);
        $paquete = ServicioTuristico::create([
            'cCodigo' => uniqid('TEST'), 'cNombre' => 'Paquete de prueba',
            'iIDCategoria' => $categoria->iID, 'iIDDestino' => $destino->iID,
            'dPrecioVenta' => 1000, 'cMoneda' => 'MXN', 'lActivo' => true,
            'dFechaInicioVigencia' => today()->subDay(), 'dFechaFinVigencia' => today()->addMonth(),
            'lTienePromocion' => true, 'lPromocionActiva' => true, 'cTipoDescuento' => 'PORCENTAJE',
            'dValorDescuento' => 10, 'dFechaInicioPromocion' => now()->subDay(), 'dFechaFinPromocion' => now()->addDay(),
        ]);

        return [$paquete, ['iIDCliente' => $cliente->iID, 'iIDServicio' => $paquete->iID, 'iCantidad' => 2, 'dFechaViaje' => today()->addWeek()->toDateString()]];
    }

    public function test_venta_conserva_precio_promocional_y_controla_abonos(): void
    {
        [$paquete, $data] = $this->datos();
        $this->post(route('agencia.ventas.store'), $data + ['dTotal' => 1])->assertSessionHasNoErrors();
        $venta = VentaPaquete::latest('iID')->firstOrFail();
        $this->assertEquals('1800.00', $venta->dTotal);
        $detalle = $venta->detalles()->firstOrFail();
        $this->assertEquals($paquete->dFechaInicioVigencia->toDateString(), $detalle->dFechaInicio->toDateString());
        $this->assertEquals($paquete->dFechaFinVigencia->toDateString(), $detalle->dFechaFin->toDateString());
        $this->assertEquals('1000.00', $detalle->dPrecioRegular);
        $this->assertEquals('100.00', $detalle->dDescuentoUnitario);
        $this->assertEquals('Promoción vigente', $detalle->cPromocion);
        $paquete->update(['dPrecioVenta' => 9999, 'dFechaFinVigencia' => today()->addYear(), 'cNombrePromocion' => 'Otra promoción', 'dFechaFinPromocion' => now()->subHour()]);
        $this->assertEquals('900.00', $venta->fresh()->dPrecioUnitario);
        $this->assertEquals('100.00', $detalle->fresh()->dDescuentoUnitario);
        $this->assertEquals('Promoción vigente', $detalle->fresh()->cPromocion);
        $this->assertEquals(today()->addMonth()->toDateString(), $detalle->fresh()->dFechaFin->toDateString());
        $this->get(route('agencia.vender.index'))->assertOk()->assertSee('Vender paquetes');
        $this->get(route('agencia.apartados.index'))->assertOk()->assertSee($venta->folio)->assertViewHas('ventas', fn ($ventas) => $ventas->perPage() === 10);
        $this->get(route('agencia.ventas.show', $venta))->assertOk()->assertSee('Historial de abonos');
        $this->get(route('agencia.ventas.show', ['venta' => $venta, 'seccion' => 'detalle']))->assertOk()->assertDontSee('Imprimir comprobante')->assertDontSee('Historial de abonos')->assertDontSee('Guardar abono');
        $this->get(route('agencia.ventas.show', ['venta' => $venta, 'seccion' => 'historial']))->assertOk()->assertSee('Historial de abonos')->assertDontSee('Registrar abono');
        $this->get(route('agencia.ventas.show', ['venta' => $venta, 'seccion' => 'abono']))->assertOk()->assertSee('Guardar abono')->assertDontSee('Agregar paquete al apartado');
        $this->get(route('agencia.ventas.show', ['venta' => $venta, 'seccion' => 'paquetes']))->assertOk()->assertSee('Agregar paquete al apartado')->assertDontSee('Guardar abono');
        $this->get(route('agencia.ventas.show', ['venta' => $venta, 'seccion' => 'nombre']))->assertOk()->assertSee('Guardar nombre')->assertDontSee('Historial de abonos');
        $this->postJson(route('agencia.ventas.pago', $venta), ['dImporte' => 1800.01, 'cMetodo' => 'Efectivo'])->assertUnprocessable();
        $this->post(route('agencia.ventas.pago', $venta), ['dImporte' => 800, 'cMetodo' => 'Efectivo'])->assertSessionHasNoErrors();
        $this->assertEquals(100000, $venta->fresh()->saldo_centavos);
        $this->assertEquals('1000.00', $detalle->fresh()->dPrecioRegular);
        $this->assertEquals('100.00', $detalle->fresh()->dDescuentoUnitario);
        $this->assertEquals('900.00', $detalle->fresh()->dPrecioUnitario);
        $this->assertEquals('1800.00', $venta->fresh()->dTotal);
        $pdfHtml = view('agencia.ventas.comprobante-pdf', ['venta' => $venta->fresh()->load(['cliente', 'detalles', 'usuario', 'pagos.usuario']), 'logo' => null])->render();
        $this->assertStringContainsString('1,000.00', $pdfHtml);
        $this->assertStringContainsString('100.00', $pdfHtml);
        $this->assertStringNotContainsString('9,999.00', $pdfHtml);
        $this->postJson(route('agencia.ventas.finalizar', $venta))->assertUnprocessable();
        $this->post(route('agencia.ventas.pago', $venta), ['dImporte' => 1000, 'cMetodo' => 'Transferencia'])->assertSessionHasNoErrors();
        $this->assertEquals('Pagada', $venta->fresh()->estado);
        $this->get(route('agencia.apartados.index', ['estado' => 'Liquidado']))->assertOk()->assertSee($venta->folio);
        $this->get(route('agencia.ventas.index'))->assertOk()->assertDontSee($venta->folio);
        $this->post(route('agencia.ventas.finalizar', $venta))->assertSessionHasNoErrors();
        $this->get(route('agencia.ventas.index'))->assertOk()->assertSee($venta->folio);
        $this->get(route('agencia.apartados.index'))->assertOk()->assertDontSee($venta->folio);
        $this->postJson(route('agencia.ventas.finalizar', $venta))->assertUnprocessable();
        $this->postJson(route('agencia.ventas.anularPago', [$venta, $venta->pagos()->first()]))->assertUnprocessable();
        $this->postJson(route('agencia.ventas.cancelar', $venta))->assertUnprocessable();
    }

    public function test_rechaza_paquete_vencido_y_pago_en_venta_cancelada(): void
    {
        [$paquete, $data] = $this->datos();
        $paquete->update(['dFechaFinVigencia' => today()->subDay()]);
        $this->postJson(route('agencia.ventas.store'), $data)->assertUnprocessable();
        $paquete->update(['dFechaFinVigencia' => today()->addMonth()]);
        $this->post(route('agencia.ventas.store'), $data)->assertSessionHasNoErrors();
        $venta = VentaPaquete::latest('iID')->firstOrFail();
        $this->post(route('agencia.ventas.cancelar', $venta))->assertSessionHasNoErrors();
        $this->assertEquals('Cancelada', $venta->fresh()->estado);
        $this->postJson(route('agencia.ventas.pago', $venta), ['dImporte' => 10, 'cMetodo' => 'Tarjeta'])->assertUnprocessable();
    }

    public function test_ventas_requieren_autenticacion(): void
    {
        $this->get(route('agencia.ventas.index'))->assertRedirect(route('login'));
        $this->get(route('agencia.vender.index'))->assertRedirect(route('login'));
        $this->get(route('agencia.apartados.index'))->assertRedirect(route('login'));
        $this->get(route('agencia.finanzas.index'))->assertRedirect(route('login'));
        $this->get(route('agencia.ventas.comprobante', 1))->assertRedirect(route('login'));
        $this->get(route('agencia.ventas.comprobanteCompra', 1))->assertRedirect(route('login'));
    }

    public function test_comprobante_pdf_incluye_logo_y_solo_abonos_aplicados_sin_estado(): void
    {
        [$paquete, $data] = $this->datos();
        $this->post(route('agencia.ventas.store'), $data)->assertSessionHasNoErrors();
        $venta = VentaPaquete::latest('iID')->firstOrFail();
        $this->post(route('agencia.ventas.pago', $venta), ['dImporte' => 300, 'cMetodo' => 'Efectivo', 'cReferencia' => 'Abono inicial'])->assertSessionHasNoErrors();
        $pago = $venta->pagos()->firstOrFail();
        $this->post(route('agencia.ventas.anularPago', [$venta, $pago]))->assertSessionHasNoErrors();
        $this->post(route('agencia.ventas.pago', $venta), ['dImporte' => 500, 'cMetodo' => 'Transferencia'])->assertSessionHasNoErrors();
        $response = $this->get(route('agencia.ventas.comprobante', $venta));
        $response->assertOk()->assertHeader('Content-Type', 'application/pdf')->assertDownload('comprobante-'.$venta->folio.'.pdf');
        $this->assertStringStartsWith('%PDF-', $response->getContent());
        $html = view('agencia.ventas.comprobante-pdf', ['venta' => $venta->fresh()->load(['cliente', 'detalles', 'pagos.usuario', 'usuario']), 'logo' => 'data:image/jpeg;base64,'.base64_encode(file_get_contents(public_path('img/logo.jpg')))])->render();
        $this->assertStringContainsString('Historial de abonos', $html);
        $this->assertStringNotContainsString('Anulado', $html);
        $this->assertStringNotContainsString('Abono inicial', $html);
        $this->assertStringNotContainsString('<th>Estado</th>', $html);
        $this->assertStringNotContainsString('class="estado"', $html);
        $this->assertStringContainsString('Transferencia', $html);
        $this->assertStringContainsString('1,300.00', $html);
        $this->assertStringContainsString('data:image/jpeg;base64,', $html);
        $venta->detalles()->update(['dPrecioRegular' => null, 'dDescuentoUnitario' => null]);
        $historico = view('agencia.ventas.comprobante-pdf', ['venta' => $venta->fresh()->load(['cliente', 'detalles.servicio', 'pagos.usuario', 'usuario']), 'logo' => null])->render();
        $this->assertStringContainsString('Desglose histórico no disponible', $historico);
        $this->assertStringNotContainsString('1,000.00', $historico);
        $this->assertStringNotContainsString('catálogo actual', $historico);
        $this->assertStringContainsString('900.00', $historico);
        $this->assertEquals('1800.00', $venta->fresh()->dTotal);
        $this->get(route('agencia.ventas.comprobante', $venta))->assertOk()->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_compra_saldada_pdf_solo_para_ventas_pagadas_y_sin_historial(): void
    {
        [$paquete, $data] = $this->datos();
        $this->post(route('agencia.ventas.store'), $data)->assertSessionHasNoErrors();
        $venta = VentaPaquete::latest('iID')->firstOrFail();
        $this->getJson(route('agencia.ventas.comprobanteCompra', $venta))->assertUnprocessable();
        $this->post(route('agencia.ventas.pago', $venta), ['dImporte' => 1800, 'cMetodo' => 'Efectivo'])->assertSessionHasNoErrors();
        $this->getJson(route('agencia.ventas.comprobanteCompra', $venta))->assertUnprocessable();
        $this->post(route('agencia.ventas.finalizar', $venta))->assertSessionHasNoErrors();
        $this->get(route('agencia.ventas.comprobanteCompra', $venta))->assertOk()->assertHeader('Content-Type', 'application/pdf')->assertDownload('compra-saldada-'.$venta->folio.'.pdf');
        $html = view('agencia.ventas.comprobante-pdf', ['venta' => $venta->fresh()->load(['cliente', 'detalles.servicio', 'usuario', 'pagos']), 'logo' => null, 'compraSaldada' => true])->render();
        $this->assertStringContainsString('COMPRA SALDADA', $html);
        $this->assertStringContainsString('pagado en su totalidad', $html);
        $this->assertStringNotContainsString('Saldo pendiente', $html);
        $this->assertStringNotContainsString('POR ABONAR', $html);
        $this->assertStringContainsString('TOTAL PAGADO', $html);
        $this->assertStringNotContainsString('Historial de abonos', $html);
    }

    public function test_venta_directa_con_varios_paquetes_es_atomica_y_respeta_moneda(): void
    {
        [$paquete, $data] = $this->datos();
        $otro = $paquete->replicate();
        $otro->cCodigo = uniqid('TEST');
        $otro->cNombre = 'Segundo paquete';
        $otro->save();
        $ventaData = ['iIDCliente' => $data['iIDCliente'], 'dFechaViaje' => $data['dFechaViaje'], 'accion' => 'vender', 'cMetodo' => 'Tarjeta', 'items' => [
            ['iIDServicio' => $paquete->iID, 'iCantidad' => 2], ['iIDServicio' => $otro->iID, 'iCantidad' => 1],
        ]];
        $this->post(route('agencia.ventas.store'), $ventaData)->assertSessionHasNoErrors();
        $venta = VentaPaquete::latest('iID')->firstOrFail();
        $this->assertEquals('2700.00', $venta->dTotal);
        $this->assertCount(2, $venta->detalles);
        $this->assertEquals(0, $venta->saldo_centavos);
        $this->assertNotNull($venta->finalizada_at);
        $this->assertEquals('Tarjeta', $venta->pagos->first()->cMetodo);
        $this->getJson(route('agencia.ventas.comprobante', $venta))->assertUnprocessable();
        $this->get(route('agencia.ventas.comprobanteCompra', $venta))->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->get(route('agencia.ventas.index', ['cliente' => $data['iIDCliente']]))->assertSee($venta->folio);
        $this->postJson(route('agencia.ventas.agregarPaquete', $venta), ['iIDServicio' => $otro->iID, 'iCantidad' => 1])->assertUnprocessable();
        $otro->update(['cMoneda' => 'USD']);
        $antes = VentaPaquete::count();
        $this->postJson(route('agencia.ventas.store'), $ventaData)->assertUnprocessable();
        $this->assertEquals($antes, VentaPaquete::count());
        unset($ventaData['cMetodo']);
        $this->postJson(route('agencia.ventas.store'), $ventaData)->assertUnprocessable();
    }

    public function test_apartado_permite_agregar_paquete_corregir_abono_y_cancelar(): void
    {
        [$paquete, $data] = $this->datos();
        $this->post(route('agencia.ventas.store'), $data)->assertSessionHasNoErrors();
        $venta = VentaPaquete::latest('iID')->firstOrFail();
        $this->post(route('agencia.ventas.nombre', $venta), ['cNombre' => 'Vacaciones familiares'])->assertSessionHasNoErrors();
        $this->post(route('agencia.ventas.agregarPaquete', $venta), ['iIDServicio' => $paquete->iID, 'iCantidad' => 1])->assertSessionHasNoErrors();
        $this->assertEquals('2700.00', $venta->fresh()->dTotal);
        $this->post(route('agencia.ventas.pago', $venta), ['dImporte' => 700, 'cMetodo' => 'Efectivo', 'dFechaPago' => today()->subDay()->toDateString()])->assertSessionHasNoErrors();
        $pago = $venta->pagos()->firstOrFail();
        $this->post(route('agencia.ventas.fechaPago', [$venta, $pago]), ['dFechaPago' => today()->toDateString()])->assertSessionHasNoErrors();
        $this->assertEquals(today()->toDateString(), $pago->fresh()->dFechaPago->toDateString());
        $this->post(route('agencia.ventas.anularPago', [$venta, $pago]))->assertSessionHasNoErrors();
        $this->assertEquals(270000, $venta->fresh()->saldo_centavos);
        $this->assertNotNull($pago->fresh()->anulado_at);
        $this->post(route('agencia.ventas.pago', $venta), ['dImporte' => 500, 'cMetodo' => 'Efectivo'])->assertSessionHasNoErrors();
        $this->post(route('agencia.ventas.cancelar', $venta))->assertSessionHasNoErrors();
        $this->assertEquals('Cancelado', $venta->fresh()->estado_apartado);
        $this->assertEquals(220000, $venta->fresh()->saldo_centavos);
        $this->get(route('agencia.apartados.index', ['estado' => 'Cancelado', 'buscar' => 'Vacaciones familiares']))->assertSee($venta->folio);
    }

    public function test_finanzas_cuenta_cobros_una_vez_y_separa_monedas_y_fechas(): void
    {
        [$paquete, $data] = $this->datos();
        $paquete->update(['cMoneda' => 'TST']);
        $this->post(route('agencia.ventas.store'), $data)->assertSessionHasNoErrors();
        $venta = VentaPaquete::latest('iID')->firstOrFail();
        $this->post(route('agencia.ventas.pago', $venta), ['dImporte' => 1800, 'cMetodo' => 'Transferencia', 'dFechaPago' => '2000-01-15'])->assertSessionHasNoErrors();
        $filtros = ['desde' => '2000-01-01', 'hasta' => '2000-01-31', 'moneda' => 'TST'];
        $this->get(route('agencia.finanzas.index', $filtros))->assertOk()->assertViewHas('totales', fn ($totales) => (float) $totales->ingresos === 1800.0)->assertViewHas('origenes', fn ($origenes) => (float) $origenes->get('Abono') === 1800.0);
        $this->post(route('agencia.ventas.finalizar', $venta))->assertSessionHasNoErrors();
        $this->get(route('agencia.finanzas.index', $filtros))->assertViewHas('totales', fn ($totales) => (float) $totales->ingresos === 1800.0)->assertViewHas('origenes', fn ($origenes) => (float) $origenes->get('Venta') === 1800.0 && (float) $origenes->get('Abono', 0) === 0.0);
        foreach ([['EGRESO', 200, 'TST'], ['INGRESO', 100, 'TST'], ['INGRESO', 999, 'USD']] as [$tipo, $importe, $moneda]) {
            $this->post(route('agencia.finanzas.store'), ['cTipo' => $tipo, 'dImporte' => $importe, 'cMoneda' => $moneda, 'cConcepto' => 'Movimiento de prueba', 'cMetodo' => 'Efectivo', 'dFecha' => '2000-01-15'])->assertSessionHasNoErrors();
        }
        $this->get(route('agencia.finanzas.index', $filtros))->assertViewHas('totales', fn ($totales) => (float) $totales->ingresos === 1900.0 && (float) $totales->egresos === 200.0);
        $gasto = MovimientoFinanciero::where('cTipo', 'EGRESO')->where('cMoneda', 'TST')->firstOrFail();
        $this->post(route('agencia.finanzas.anular', $gasto))->assertSessionHasNoErrors();
        $this->get(route('agencia.finanzas.index', $filtros))->assertViewHas('totales', fn ($totales) => (float) $totales->egresos === 0.0);
        $export = $this->get(route('agencia.finanzas.exportar', $filtros))->assertOk()->assertDownload('finanzas-2000-01-01-2000-01-31.csv');
        $this->assertStringContainsString('1800.00', $export->streamedContent());
        $this->assertStringNotContainsString('999.00', $export->streamedContent());
        $this->get(route('agencia.finanzas.index', ['desde' => '2000-02-01', 'hasta' => '2000-02-28', 'moneda' => 'TST']))->assertViewHas('totales', fn ($totales) => (float) $totales->ingresos === 0.0);
        $this->postJson(route('agencia.finanzas.store'), ['cTipo' => 'INGRESO', 'dImporte' => -1])->assertUnprocessable();
        $this->getJson(route('agencia.finanzas.index', ['desde' => '2000-02-01', 'hasta' => '2000-01-01']))->assertUnprocessable();
    }

    public function test_fechas_y_promociones_se_toman_del_servicio_y_filtros_inician_hoy(): void
    {
        [$paquete, $data] = $this->datos();
        $paquete->update(['lTienePromocion' => false]);
        unset($data['dFechaViaje']);
        $this->post(route('agencia.ventas.store'), $data + ['dFechaInicio' => '2000-01-01', 'dDescuentoUnitario' => 500])->assertSessionHasNoErrors();
        $venta = VentaPaquete::latest('iID')->firstOrFail();
        $detalle = $venta->detalles()->firstOrFail();
        $this->assertEquals($paquete->dFechaInicioVigencia->toDateString(), $detalle->dFechaInicio->toDateString());
        $this->assertEquals('0.00', $detalle->dDescuentoUnitario);
        $this->assertNull($detalle->cPromocion);
        $this->get(route('agencia.ventas.show', $venta))->assertOk()->assertSee('Sin promoción')->assertSee($detalle->dFechaInicio->format('d/m/Y'))->assertSee($detalle->dFechaFin->format('d/m/Y'));
        $this->get(route('agencia.vender.index'))->assertOk()->assertSee('select2.min.js')->assertSee('Descuento total')->assertDontSee('name="dFechaViaje"', false);
        $this->get(route('agencia.finanzas.index'))->assertOk()->assertViewHas('desde', today()->startOfMonth()->toDateString())->assertViewHas('hasta', today()->endOfMonth()->toDateString());
        $venta->update(['created_at' => now()->subDay()]);
        $this->get(route('agencia.apartados.index'))->assertOk()->assertDontSee($venta->folio);
        $this->get(route('agencia.apartados.index', ['desde' => today()->subDay()->toDateString(), 'hasta' => today()->toDateString()]))->assertOk()->assertSee($venta->folio);
    }

    public function test_finanzas_pagina_ingresos_y_egresos_de_forma_independiente(): void
    {
        $this->actingAs(User::factory()->create(['lCambiarPassword' => false]));
        foreach (['INGRESO' => 12, 'EGRESO' => 11] as $tipo => $cantidad) {
            for ($i = 0; $i < $cantidad; $i++) {
                MovimientoFinanciero::create(['cTipo' => $tipo, 'cConcepto' => 'Paginación '.$tipo.' '.$i, 'dImporte' => 100, 'cMoneda' => 'TPG', 'cMetodo' => 'Efectivo', 'dFecha' => today()]);
            }
        }
        $this->get(route('agencia.finanzas.index', ['moneda' => 'TPG', 'ingresos_page' => 2]))->assertOk()
            ->assertViewHas('ingresos', fn ($tabla) => $tabla->perPage() === 10 && $tabla->count() === 2 && $tabla->total() === 12 && $tabla->every(fn ($fila) => $fila->tipo === 'INGRESO'))
            ->assertViewHas('egresos', fn ($tabla) => $tabla->count() === 10 && $tabla->total() === 11 && $tabla->currentPage() === 1 && $tabla->every(fn ($fila) => $fila->tipo === 'EGRESO'));
        $this->get(route('agencia.finanzas.index', ['moneda' => 'TPG', 'egresos_page' => 2]))->assertOk()
            ->assertViewHas('ingresos', fn ($tabla) => $tabla->count() === 10 && $tabla->currentPage() === 1)
            ->assertViewHas('egresos', fn ($tabla) => $tabla->count() === 1 && $tabla->currentPage() === 2);
    }
}
