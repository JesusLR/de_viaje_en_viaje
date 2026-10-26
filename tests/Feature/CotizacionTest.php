<?php

namespace Tests\Feature;

use App\Models\{Cotizacion, Role, User};
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class CotizacionTest extends TestCase
{
    use DatabaseTransactions;

    private function datos(): array
    {
        return ['nombre' => 'Familia de prueba', 'whatsapp' => '+52 9991234567', 'fechas' => 'Aproximadamente en diciembre',
            'dias' => 5, 'noches' => 4, 'personas' => 4, 'adultos' => 2, 'menores' => 2, 'edades' => '4, 12',
            'hotel' => 'Hotel de prueba', 'tipo_hotel' => 'Todo incluido', 'ambiente' => 'Familiar', 'presupuesto' => 15000, 'moneda' => 'MXN'];
    }

    private function solicitud(): Cotizacion
    {
        $this->post(route('cotizacion.enviar'), $this->datos())->assertRedirect(route('cotizacion.formulario'))->assertSessionHasNoErrors();
        return Cotizacion::latest('id')->firstOrFail();
    }

    public function test_ajax_submission_returns_success_without_redirect_and_keeps_form_available(): void
    {
        $before = Cotizacion::count();
        $this->postJson(route('cotizacion.enviar'), $this->datos())->assertCreated()
            ->assertJsonPath('success', true)->assertJsonStructure(['message'])->assertHeaderMissing('Location');
        $this->assertEquals($before + 1, Cotizacion::count());
        $this->get(route('cotizacion.formulario'))->assertOk()->assertSee('id="formCotizacion"', false)
            ->assertSee('cotizacion-publica.js')->assertSee('action="/cotizar"', false);
    }

    public function test_web_quotes_filter_by_sent_date_inclusively_and_show_date_without_time(): void
    {
        $base = $this->solicitud();
        $base->update(['created_at' => '2000-01-01 00:00:00']);
        $last = $base->replicate(); $last->created_at = '2000-01-31 23:59:59'; $last->save();
        $outside = $base->replicate(); $outside->created_at = '2000-02-01 00:00:00'; $outside->save();
        $this->actingAs(User::factory()->create(['lCambiarPassword' => false]));
        $this->get(route('cotizaciones.index'))->assertOk()
            ->assertSee('value="'.today()->startOfMonth()->toDateString().'"', false)
            ->assertSee('value="'.today()->endOfMonth()->toDateString().'"', false)
            ->assertViewHas('cotizaciones', fn ($items) => !$items->contains('id', $base->id));
        $this->get(route('cotizaciones.index', ['desde' => '2000-01-01', 'hasta' => '2000-01-31']))->assertOk()
            ->assertViewHas('cotizaciones', fn ($items) => $items->contains('id', $base->id) && $items->contains('id', $last->id) && !$items->contains('id', $outside->id))
            ->assertSee('Fecha de envío')->assertSee('31/01/2000')->assertDontSee('23:59');
        $this->get(route('cotizaciones.index', ['desde' => '2000-01-01', 'hasta' => '2000-01-01']))->assertOk()
            ->assertViewHas('cotizaciones', fn ($items) => $items->contains('id', $base->id) && !$items->contains('id', $last->id));
        $this->getJson(route('cotizaciones.index', ['desde' => '2000-02-01', 'hasta' => '2000-01-01']))->assertUnprocessable();
    }

    public function test_public_form_saves_request_without_exposing_private_data(): void
    {
        $this->get(route('landing'))->assertOk()->assertSee('¡COTIZA TU PRÓXIMO VIAJE DE FORMA RÁPIDA Y FÁCIL!')->assertSee(route('cotizacion.formulario'));
        $this->get(route('cotizacion.formulario'))->assertOk()->assertSee('Nombre completo')->assertSee('Número de WhatsApp');
        $this->post(route('cotizacion.enviar'), $this->datos() + ['estado' => 'ATENDIDO', 'atendida_por' => 1])->assertSessionHasNoErrors()->assertRedirect(route('cotizacion.formulario'));
        $solicitud = Cotizacion::latest('id')->firstOrFail();
        $this->assertSame('NUEVA', $solicitud->estado);
        $this->assertNull($solicitud->atendida_por);
        $this->assertSame([4, 12], $solicitud->edades_menores);
        $this->get(route('cotizacion.formulario'))->assertOk()->assertSee('¡Recibimos tu solicitud!')->assertDontSee($solicitud->whatsapp);
        $this->get(route('cotizaciones.index'))->assertRedirect(route('login'));
        $this->get(route('cotizaciones.show', $solicitud))->assertRedirect(route('login'));
    }

    public function test_public_validation_checks_totals_ages_and_required_answers(): void
    {
        $this->withoutMiddleware(\Illuminate\Routing\Middleware\ThrottleRequests::class);
        foreach ([['personas' => 6], ['edades' => '4'], ['edades' => '4,18'], ['noches' => 7], ['whatsapp' => 'no es un teléfono'], ['presupuesto' => -1]] as $cambio) {
            $this->postJson(route('cotizacion.enviar'), array_replace($this->datos(), $cambio))->assertUnprocessable();
        }
    }

    public function test_followup_keeps_responsible_and_requires_final_observation(): void
    {
        $solicitud = $this->solicitud();
        $asesor = User::factory()->create(['lCambiarPassword' => false]);
        $this->actingAs($asesor);
        $url = route('cotizaciones.seguimiento', $solicitud);
        $this->putJson($url, ['estado' => 'ATENDIDO', 'observacion' => 'Cotización enviada'])->assertUnprocessable();
        $this->put($url, ['estado' => 'EN_ATENCION', 'observacion' => 'Contacté por WhatsApp'])->assertSessionHasNoErrors();
        $this->assertEquals($asesor->id, $solicitud->fresh()->atendida_por);
        $this->putJson($url, ['estado' => 'ATENDIDO', 'observacion' => '   '])->assertUnprocessable();
        $segundo = User::factory()->create(['lCambiarPassword' => false]);
        $this->actingAs($segundo);
        $this->put($url, ['estado' => 'EN_ATENCION', 'observacion' => 'Se recibieron preferencias del hotel'])->assertSessionHasNoErrors();
        $this->assertEquals($asesor->id, $solicitud->fresh()->atendida_por);
        $this->put($url, ['estado' => 'ATENDIDO', 'observacion' => 'Se envió la cotización final al cliente'])->assertSessionHasNoErrors();
        $this->assertSame('ATENDIDO', $solicitud->fresh()->estado);
        $this->assertNotNull($solicitud->fresh()->atendida_at);
        $this->assertSame('Se envió la cotización final al cliente', $solicitud->fresh()->observacion_final);
        $this->assertEquals($segundo->id, $solicitud->observaciones()->reorder('id', 'desc')->first()->usuario_id);
        $this->get(route('cotizaciones.show', $solicitud))->assertOk()->assertSee('Contacté por WhatsApp')->assertSee('Observación final')->assertDontSee('Guardar seguimiento');
        $this->get(route('cotizaciones.index', ['estado' => 'ATENDIDO', 'buscar' => 'Familia']))->assertOk()->assertViewHas('cotizaciones', fn ($items) => $items->contains('id', $solicitud->id));
        $this->get(route('cotizaciones.index', ['estado' => 'NUEVA']))->assertOk()->assertViewHas('cotizaciones', fn ($items) => !$items->contains('id', $solicitud->id));
        $this->putJson($url, ['estado' => 'EN_ATENCION'])->assertUnprocessable();
    }

    public function test_permissions_allow_read_only_and_cancel_history(): void
    {
        $solicitud = $this->solicitud();
        $user = User::factory()->create(['lCambiarPassword' => false]);
        $role = Role::create(['name' => 'Cotizaciones prueba', 'slug' => uniqid('cotizaciones'), 'permissions' => []]);
        $user->roles()->sync([$role->id]);
        $this->actingAs($user);
        $this->get(route('cotizaciones.index'))->assertForbidden();
        $role->update(['permissions' => ['cotizaciones.ver']]);
        $user->unsetRelation('roles');
        $this->get(route('cotizaciones.index'))->assertOk()->assertSee('Cotizaciones');
        $this->get(route('cotizaciones.show', $solicitud))->assertOk()->assertDontSee('Guardar seguimiento');
        $this->putJson(route('cotizaciones.seguimiento', $solicitud), ['estado' => 'CANCELADO'])->assertForbidden();
        $role->update(['permissions' => ['cotizaciones.ver', 'cotizaciones.atender']]);
        $user->unsetRelation('roles');
        $this->put(route('cotizaciones.seguimiento', $solicitud), ['estado' => 'CANCELADO', 'observacion' => 'El cliente no desea continuar'])->assertSessionHasNoErrors();
        $this->assertSame('CANCELADO', $solicitud->fresh()->estado);
        $this->assertSame('El cliente no desea continuar', $solicitud->observaciones()->first()->observacion);
    }
}
