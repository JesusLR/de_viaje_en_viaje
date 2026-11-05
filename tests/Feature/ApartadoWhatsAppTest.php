<?php

namespace Tests\Feature;

use App\Models\{Cliente, Role, ServicioTuristico, User, VentaPaquete};
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class ApartadoWhatsAppTest extends TestCase
{
    use DatabaseTransactions;
    private const SESION = '12345678-1234-4234-8234-123456789012';
    private array $envios = [];

    protected function setUp(): void
    {
        parent::setUp();
        \App\Models\VariableSistema::updateOrCreate(['clave' => 'whatsapp_venta_automatica'], ['valor' => 1]);
        config(['cache.default' => 'array', 'services.openwa.url' => 'http://openwa.test/api', 'services.openwa.system_key' => 'solo-pruebas']);
        Http::preventStrayRequests();
        Http::fake(function ($request) {
            if ($request->method() === 'GET') return Http::response(['sourceSystem' => 'de_viaje_en_viaje', 'sessions' => [[
                'id' => self::SESION, 'name' => 'de-viaje-en-viaje--recepcion', 'sourceSystem' => 'de_viaje_en_viaje', 'status' => 'ready', 'engineLoaded' => true, 'phone' => '529991111111',
            ]]]);
            $this->envios[] = $request;
            return Http::response(['id' => 'mensaje-prueba', 'status' => 'sent'], 201);
        });
    }

    private function usuario(array $permisos): User
    {
        $usuario = User::factory()->create(['lCambiarPassword' => false]);
        $rol = Role::create(['name' => 'Envío prueba', 'slug' => uniqid('envio-'), 'permissions' => $permisos]);
        $usuario->roles()->sync([$rol->id]);
        return $usuario;
    }

    private function apartado(?User $dueno = null): VentaPaquete
    {
        $cliente = Cliente::create(['cNombre' => 'Cliente WhatsApp', 'cPrimerApellido' => 'Prueba', 'cTelefono' => '+52 (999) 123-4567', 'lActivo' => true]);
        $servicio = ServicioTuristico::create(['cCodigo' => uniqid('WA'), 'cNombre' => 'Servicio WhatsApp', 'dPrecioCompra' => 500, 'dPrecioVenta' => 1000, 'lActivo' => true, 'lUsaVigencia' => false]);
        $venta = VentaPaquete::create(['iIDCliente' => $cliente->iID, 'iIDUsuario' => $dueno?->id ?? auth()->id(), 'cTipoOperacion' => 'APARTADO', 'iIDServicio' => $servicio->iID, 'cPaquete' => $servicio->cNombre, 'iCantidad' => 1, 'dPrecioUnitario' => 1000, 'dTotal' => 1000, 'cMoneda' => 'MXN']);
        $venta->detalles()->create(['iIDServicio' => $servicio->iID, 'cPaquete' => $servicio->cNombre, 'iCantidad' => 1, 'dPrecioUnitario' => 1000, 'dSubtotal' => 1000, 'lUsaVigencia' => false, 'itinerario' => [['numero' => 1, 'titulo' => 'Llegada de prueba', 'descripcion' => 'Recorrido del día', 'actividades' => []]]]);
        $venta->pagos()->create(['dImporte' => 200, 'dFechaPago' => today(), 'cMetodo' => 'Efectivo', 'iIDUsuario' => auth()->id()]);
        return $venta;
    }

    private function envio(string $tipo = 'abonos'): array
    {
        return ['tipo' => $tipo, 'telefono' => '+52 999 765 4321', 'solicitud' => (string) Str::uuid()];
    }

    public function test_confirma_telefono_editable_y_envia_el_historial_pdf_sin_modificar_el_cliente(): void
    {
        $this->actingAs($this->usuario(['apartados.ver', 'apartados.pdf', 'apartados.whatsapp']));
        $venta = $this->apartado();
        $this->get(route('agencia.apartados.index', ['buscar' => $venta->folio]))->assertOk()->assertSee('Enviar por WhatsApp')->assertSee('Enviar historial de abonos PDF');
        $this->postJson(route('agencia.apartados.whatsappPreparar', $venta), ['tipo' => 'abonos'])->assertOk()->assertJsonPath('datos.telefono', '+529991234567')->assertJsonPath('datos.sesion.id', self::SESION);
        $datos = $this->envio();
        $this->postJson(route('agencia.apartados.whatsappPreparar', $venta), array_diff_key($datos, ['solicitud' => true]))->assertOk()->assertJsonPath('datos.telefono', '+529997654321');
        $this->assertCount(0, $this->envios);
        $this->postJson(route('agencia.apartados.whatsappEnviar', $venta), $datos)->assertOk()->assertJsonPath('datos.telefono', '+529997654321');
        $this->assertCount(1, $this->envios);
        $envio = $this->envios[0];
        $this->assertSame('529997654321', $envio['phone']);
        $this->assertStringContainsString('tu historial de abonos', $envio['caption']);
        $this->assertStringContainsString(self::SESION.'/documents', $envio->url());
        $this->assertStringStartsWith('%PDF-', base64_decode($envio['base64']));
        $this->assertStringStartsWith('Abono _ Cliente WhatsApp Prueba _ Servicio WhatsApp', $envio['filename']);
        $this->assertTrue($envio->hasHeader('Idempotency-Key', $datos['solicitud']));
        $this->assertSame('+52 (999) 123-4567', $venta->cliente->fresh()->cTelefono);
        $this->postJson(route('agencia.apartados.whatsappEnviar', $venta), $datos)->assertOk();
        $this->assertCount(1, $this->envios);
    }

    public function test_itinerario_generado_y_pdf_subido_utilizan_los_documentos_actuales(): void
    {
        $this->actingAs($this->usuario(['apartados.ver', 'apartados.pdf', 'apartados.whatsapp', 'itinerarios.ver', 'itinerarios.pdf']));
        $venta = $this->apartado();
        $this->postJson(route('agencia.apartados.whatsappEnviar', $venta), $this->envio('itinerario'))->assertOk();
        $this->assertStringContainsString('tu itinerario de viaje', $this->envios[0]['caption']);
        $generado = base64_decode($this->envios[0]['base64']);
        $this->assertStringStartsWith('%PDF-', $generado);
        Storage::fake('local');
        Storage::disk('local')->put('itinerarios/ejemplo-whatsapp.pdf', $generado);
        $venta->detalles()->first()->update(['itinerario' => null, 'cItinerarioPdf' => 'itinerarios/ejemplo-whatsapp.pdf']);
        $this->postJson(route('agencia.apartados.whatsappEnviar', $venta), $this->envio('itinerario'))->assertOk();
        $this->assertSame($generado, base64_decode($this->envios[1]['base64']));
    }

    public function test_permisos_propiedad_y_disponibilidad_se_validan_antes_de_enviar(): void
    {
        $this->actingAs($this->usuario(['apartados.ver', 'apartados.pdf']));
        $venta = $this->apartado();
        $this->postJson(route('agencia.apartados.whatsappEnviar', $venta), $this->envio())->assertForbidden();
        $this->actingAs($this->usuario(['apartados.ver', 'apartados.whatsapp', 'apartados.pdf']));
        $this->postJson(route('agencia.apartados.whatsappPreparar', $venta), ['tipo' => 'abonos'])->assertForbidden();
        $venta->update(['iIDUsuario' => auth()->id()]);
        $this->postJson(route('agencia.apartados.whatsappEnviar', $venta), $this->envio('itinerario'))->assertForbidden();
        $this->postJson(route('agencia.apartados.whatsappEnviar', $venta), array_merge($this->envio(), ['telefono' => '+52 52 9991234567']))->assertUnprocessable();
        $this->postJson(route('agencia.apartados.whatsappEnviar', $venta), array_merge($this->envio(), ['sesion' => (string) Str::uuid()]))->assertUnprocessable();
        $this->assertCount(0, $this->envios);
        $venta->update(['finalizada_at' => now()]);
        $this->postJson(route('agencia.apartados.whatsappEnviar', $venta), $this->envio())->assertUnprocessable();
    }

    public function test_ventas_envia_documentos_con_permiso_propio_y_respeta_propiedad_y_eliminacion(): void
    {
        $this->actingAs($this->usuario(['ventas.ver', 'ventas.pdf', 'ventas.whatsapp', 'itinerarios.ver', 'itinerarios.pdf']));
        $venta = $this->apartado();
        $venta->update(['finalizada_at' => now()]);
        $this->get(route('agencia.ventas.index', ['buscar' => $venta->folio]))->assertOk()->assertSee('Enviar por WhatsApp')->assertDontSee('Enviar desde');
        $this->postJson(route('agencia.ventas.whatsappPreparar', $venta), ['tipo' => 'itinerario'])->assertOk();
        $this->postJson(route('agencia.ventas.whatsappEnviar', $venta), $this->envio('itinerario'))->assertOk();
        $this->assertSame('itinerario_servicio_whatsapp.pdf', $this->envios[0]['filename']);
        $this->postJson(route('agencia.ventas.whatsappEnviar', $venta), $this->envio())->assertOk();
        $this->actingAs($this->usuario(['ventas.ver', 'ventas.pdf', 'ventas.whatsapp']));
        $this->postJson(route('agencia.ventas.whatsappEnviar', $venta), $this->envio())->assertForbidden();
        $venta->update(['iIDUsuario' => auth()->id(), 'eliminada_at' => now()]);
        $this->postJson(route('agencia.ventas.whatsappEnviar', $venta), $this->envio())->assertUnprocessable();
        $this->assertCount(2, $this->envios);
    }

    public function test_ventas_no_permite_enviar_solo_con_permiso_de_apartados(): void
    {
        $this->actingAs($this->usuario(['ventas.ver', 'ventas.pdf', 'apartados.whatsapp']));
        $venta = $this->apartado();
        $venta->update(['finalizada_at' => now()]);
        $this->postJson(route('agencia.ventas.whatsappEnviar', $venta), $this->envio())->assertForbidden();
        $this->assertCount(0, $this->envios);
    }

    public function test_cotizacion_generica_envia_pdf_con_nombre_texto_y_sin_duplicar(): void
    {
        $this->actingAs($this->usuario(['cotizaciones_servicios.ver', 'cotizaciones_servicios.pdf', 'cotizaciones_servicios.whatsapp']));
        $cotizacion = \App\Models\CotizacionServicio::create(['usuario_id' => auth()->id(), 'cliente_nombre' => 'Cliente Prueba', 'moneda' => 'MXN', 'total' => 1000, 'partidas' => [['nombre' => 'Viaje ejemplo', 'promocion' => null, 'inicio' => null, 'fin' => null, 'precio_menor' => null, 'adultos' => 1, 'menores' => 0, 'precio' => 1000, 'regular' => 1000, 'descuento' => 0, 'subtotal' => 1000]]]);
        $this->get(route('atencion.cotizaciones.index'))->assertOk()->assertSee('Enviar cotización PDF')->assertDontSee('Enviar desde');
        $this->postJson(route('atencion.cotizaciones.whatsappPreparar', $cotizacion), ['tipo' => 'cotizacion'])->assertOk()->assertJsonPath('datos.telefono', '')->assertJsonPath('datos.cliente', 'Cliente Prueba');
        $datos = $this->envio('cotizacion');
        $this->postJson(route('atencion.cotizaciones.whatsappEnviar', $cotizacion), $datos)->assertOk();
        $this->assertSame('cotizacion_cliente_prueba_viaje_ejemplo.pdf', $this->envios[0]['filename']);
        $this->assertStringContainsString('tu cotización de viaje', $this->envios[0]['caption']);
        $this->assertStringStartsWith('%PDF-', base64_decode($this->envios[0]['base64']));
        $this->postJson(route('atencion.cotizaciones.whatsappEnviar', $cotizacion), $datos)->assertOk();
        $this->assertCount(1, $this->envios);
        $this->actingAs($this->usuario(['cotizaciones_servicios.ver', 'cotizaciones_servicios.pdf']));
        $this->postJson(route('atencion.cotizaciones.whatsappEnviar', $cotizacion), $this->envio('cotizacion'))->assertForbidden();
        $this->get(route('atencion.cotizaciones.index'))->assertOk()->assertDontSee('Enviar cotización PDF');
    }

    public function test_venta_directa_envia_comprobante_automaticamente_y_no_repite_el_envio(): void
    {
        $this->actingAs($this->usuario(['vender.ver', 'vender.venta', 'vender.apartado']));
        $base = $this->apartado();
        $datos = ['iIDCliente' => $base->iIDCliente, 'items' => [['iIDServicio' => $base->iIDServicio, 'iCantidad' => 1]], 'accion' => 'vender', 'cMetodo' => 'Efectivo'];
        $this->post(route('agencia.ventas.store'), $datos)->assertSessionHasNoErrors()->assertSessionHas('success', fn ($texto) => str_contains($texto, 'se envió por WhatsApp'));
        $venta = VentaPaquete::latest('iID')->firstOrFail();
        $this->assertNotNull($venta->finalizada_at);
        $this->assertSame(0, $venta->saldo_centavos);
        $this->assertCount(1, $this->envios);
        $this->assertSame('529991234567', $this->envios[0]['phone']);
        $this->assertStringStartsWith('Comprobante de pago _ Cliente WhatsApp Prueba _ Servicio WhatsApp', $this->envios[0]['filename']);
        $this->assertStringContainsString('comprobante de pago', $this->envios[0]['caption']);
        app(\App\Services\VentaLiquidadaWhatsAppService::class)->enviar($venta);
        $this->assertCount(1, $this->envios);
        $datos['accion'] = 'apartar';
        $this->post(route('agencia.ventas.store'), $datos)->assertSessionHasNoErrors();
        $this->assertCount(1, $this->envios);
    }

    public function test_fallo_de_whatsapp_no_revierte_venta_ni_pago(): void
    {
        $this->actingAs($this->usuario(['vender.ver', 'vender.venta']));
        $base = $this->apartado();
        Http::swap(new \Illuminate\Http\Client\Factory());
        Http::preventStrayRequests();
        Http::fake(['*' => Http::response([], 503)]);
        $this->post(route('agencia.ventas.store'), ['iIDCliente' => $base->iIDCliente, 'items' => [['iIDServicio' => $base->iIDServicio, 'iCantidad' => 1]], 'accion' => 'vender', 'cMetodo' => 'Efectivo'])->assertRedirect()->assertSessionHas('whatsapp_warning');
        $venta = VentaPaquete::latest('iID')->firstOrFail();
        $this->assertNotNull($venta->finalizada_at);
        $this->assertEquals(1000, $venta->pagos()->sum('dImporte'));
        $this->assertSame(0, $venta->saldo_centavos);
        Http::assertNotSent(fn ($peticion) => $peticion->method() === 'POST');
    }

    public function test_variable_desactivada_registra_venta_sin_consultar_ni_enviar_whatsapp(): void
    {
        $this->actingAs($this->usuario(['vender.ver', 'vender.venta']));
        \App\Models\VariableSistema::updateOrCreate(['clave' => 'whatsapp_venta_automatica'], ['valor' => 0]);
        $base = $this->apartado();
        $this->post(route('agencia.ventas.store'), ['iIDCliente' => $base->iIDCliente, 'items' => [['iIDServicio' => $base->iIDServicio, 'iCantidad' => 1]], 'accion' => 'vender', 'cMetodo' => 'Efectivo'])->assertRedirect()->assertSessionHasNoErrors()->assertSessionMissing('whatsapp_warning');
        $venta = VentaPaquete::latest('iID')->firstOrFail();
        $this->assertNotNull($venta->finalizada_at);
        $this->assertSame(0, $venta->saldo_centavos);
        app(\App\Services\VentaLiquidadaWhatsAppService::class)->enviar($venta);
        Http::assertNothingSent();
    }

    public function test_configuracion_whatsapp_se_guarda_independiente_de_dias_y_requiere_permiso(): void
    {
        $this->actingAs($this->usuario(['variables_entorno.ver']));
        $this->get(route('configuracion.variables.edit'))->assertOk()->assertSee('whatsappVentaAutomatica');
        $this->putJson(route('configuracion.variables.update'), ['whatsapp_venta_automatica' => 0])->assertForbidden();
        $this->actingAs($this->usuario(['variables_entorno.ver', 'variables_entorno.editar']));
        $dias = \App\Models\VariableSistema::diasSinAbonos();
        foreach ([0, 1] as $valor) {
            $this->put(route('configuracion.variables.update'), ['whatsapp_venta_automatica' => $valor])->assertSessionHasNoErrors()->assertRedirect();
            $this->assertSame((bool) $valor, \App\Models\VariableSistema::envioAutomaticoVentaWhatsApp());
            $this->assertEquals(auth()->id(), \App\Models\VariableSistema::find('whatsapp_venta_automatica')->actualizada_por);
        }
        $this->assertSame($dias, \App\Models\VariableSistema::diasSinAbonos());
        $this->putJson(route('configuracion.variables.update'), ['whatsapp_venta_automatica' => 2])->assertUnprocessable();
        $this->put(route('configuracion.variables.update'), ['dias_sin_abonos' => 15])->assertSessionHasNoErrors();
        $this->assertTrue(\App\Models\VariableSistema::envioAutomaticoVentaWhatsApp());
    }

    public function test_venta_directa_solo_ofrece_comprobante_y_envia_pdf_con_numero_confirmado(): void
    {
        $this->actingAs($this->usuario(['ventas.ver', 'ventas.pdf', 'ventas.whatsapp']));
        $venta = $this->apartado();
        $venta->update(['cTipoOperacion' => 'VENTA', 'finalizada_at' => now()]);
        $venta->pagos()->update(['dImporte' => 1000]);
        foreach ([route('agencia.ventas.index', ['buscar' => $venta->folio]), route('agencia.ventas.show', $venta)] as $url) {
            $this->get($url)->assertOk()->assertSee('comprobante de pago PDF')->assertDontSee('Historial de abonos')->assertDontSee('historial de abonos PDF');
        }
        $this->getJson(route('agencia.ventas.comprobante', $venta))->assertUnprocessable();
        $this->getJson(route('agencia.ventas.show', ['venta' => $venta, 'seccion' => 'historial']))->assertUnprocessable();
        $this->postJson(route('agencia.ventas.whatsappEnviar', $venta), $this->envio('abonos'))->assertUnprocessable();
        $this->postJson(route('agencia.ventas.whatsappPreparar', $venta), ['tipo' => 'comprobante'])->assertOk()->assertJsonPath('datos.telefono', '+529991234567');
        $this->postJson(route('agencia.ventas.whatsappEnviar', $venta), $this->envio('comprobante'))->assertOk();
        $this->assertSame('529997654321', $this->envios[0]['phone']);
        $this->assertStringStartsWith('Comprobante de pago _ ', $this->envios[0]['filename']);
        $this->assertStringContainsString('tu comprobante de pago', $this->envios[0]['caption']);
        $this->assertCount(1, $this->envios);
        $venta->update(['cTipoOperacion' => 'APARTADO']);
        $this->get(route('agencia.ventas.index', ['buscar' => $venta->folio]))->assertOk()->assertSee('Enviar historial de abonos PDF')->assertSee('Enviar comprobante de pago PDF');
        $this->postJson(route('agencia.ventas.whatsappEnviar', $venta), $this->envio('abonos'))->assertOk();
    }

    public function test_un_resultado_incierto_no_se_reintenta_y_no_duplica_documentos(): void
    {
        $this->actingAs($this->usuario(['apartados.ver', 'apartados.pdf', 'apartados.whatsapp']));
        $venta = $this->apartado();
        Http::swap(new \Illuminate\Http\Client\Factory());
        Http::preventStrayRequests();
        $peticiones = 0;
        Http::fake(function ($request) use (&$peticiones) {
            if ($request->method() === 'POST') { $peticiones++; throw new \Illuminate\Http\Client\ConnectionException('Tiempo de espera agotado'); }
            return Http::response(['sourceSystem' => 'de_viaje_en_viaje', 'sessions' => [[
                'id' => self::SESION, 'name' => 'de-viaje-en-viaje--recepcion', 'sourceSystem' => 'de_viaje_en_viaje', 'status' => 'ready', 'engineLoaded' => true,
            ]]]);
        });
        $datos = $this->envio();
        $this->postJson(route('agencia.apartados.whatsappEnviar', $venta), $datos)->assertStatus(503)->assertSee('Revisa el chat');
        $this->postJson(route('agencia.apartados.whatsappEnviar', $venta), $datos)->assertStatus(409);
        $this->assertSame(1, $peticiones);
    }
}
