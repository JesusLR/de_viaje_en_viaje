<?php

namespace Tests\Feature;

use App\Models\{Cliente, Itinerario, Role, ServicioTuristico, User, VentaPaquete};
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ItinerarioTest extends TestCase
{
    use DatabaseTransactions;

    private function service(): ServicioTuristico
    {
        return ServicioTuristico::create(['cCodigo' => uniqid('ITI'), 'cNombre' => 'Servicio de prueba',
            'dPrecioVenta' => 1000, 'lUsaVigencia' => false, 'lActivo' => true]);
    }

    private function days(string $title = 'Llegada'): array
    {
        return [['titulo' => $title, 'descripcion' => 'Primer día', 'actividades' => [
            ['titulo' => 'Recepción', 'hora' => '10:00', 'lugar' => 'Aeropuerto', 'incluidos' => 'Traslado',
                'descripcion' => 'Encuentro con el guía', 'notas_internas' => 'NOTA PRIVADA DEL OPERADOR'],
        ]]];
    }

    private function createItinerary(string $name = 'Recorrido familiar'): Itinerario
    {
        $this->post(route('itinerarios.store'), ['nombre' => $name, 'dias_json' => json_encode($this->days())])
            ->assertSessionHasNoErrors()->assertRedirect();
        return Itinerario::latest('id')->firstOrFail();
    }

    private function assign(ServicioTuristico $service, ?int $id, bool $publish = false)
    {
        return $this->postJson(route('servicios.paquetes.saveData'), [
            'iID' => $service->iID, 'cNombre' => $service->cNombre,
            'dPrecioCompra' => 0, 'dPrecioVenta' => 1000, 'lUsaVigencia' => 0, 'lActivo' => 1,
            'iIDItinerario' => $id, 'lPublicarItinerario' => (int) $publish,
        ]);
    }

    public function test_named_itineraries_exist_without_services_and_can_be_shared(): void
    {
        $this->actingAs(User::factory()->create(['lCambiarPassword' => false]));
        $this->get(route('itinerarios.create'))->assertOk()->assertSee('Nombre del itinerario');
        $itinerary = $this->createItinerary();
        $this->get(route('itinerarios.index', ['buscar' => $itinerary->nombre]))->assertOk()
            ->assertSee($itinerary->nombre)->assertSee('Servicios asignados')
            ->assertViewHas('itinerarios', fn ($items) => $items->total() === 1 && $items->first()->servicios_count === 0);
        $first = $this->service();
        $second = $this->service();
        $this->assign($first, $itinerary->id, true)->assertOk()->assertJsonPath('lSuccess', true);
        $this->assign($second, $itinerary->id)->assertOk()->assertJsonPath('lSuccess', true);
        $this->get(route('itinerarios.index', ['buscar' => $itinerary->nombre]))
            ->assertViewHas('itinerarios', fn ($items) => $items->first()->servicios_count === 2 && $items->first()->dias_count === 1);
        $this->getJson(route('servicios.paquetes.getData', $first))->assertJsonPath('cData.iIDItinerario', $itinerary->id);
        $this->get(route('servicios.paquetes.index'))->assertOk()->assertSee('Itinerario asignado')->assertSee($itinerary->nombre);
        $this->get(route('catalogo.publico.show', $first))->assertOk()->assertSee('Recepción')->assertDontSee('NOTA PRIVADA DEL OPERADOR');
        $this->get(route('catalogo.publico.show', $second))->assertDontSee('Itinerario del viaje');
        $this->put(route('itinerarios.update', $itinerary), ['nombre' => 'Recorrido actualizado', 'dias_json' => json_encode($this->days('Día compartido'))])->assertSessionHasNoErrors();
        $this->assertSame('Día compartido', $first->fresh()->itinerarioSnapshot()[0]['titulo']);
        $this->assertSame('Día compartido', $second->fresh()->itinerarioSnapshot()[0]['titulo']);
        $this->assign($second, null)->assertOk();
        $this->assertSame(1, $itinerary->servicios()->count());
        $this->assertSame([], $second->fresh()->itinerarioSnapshot());
        $this->assertSame(1, $itinerary->dias()->count());
    }

    public function test_copy_is_a_new_independent_itinerary(): void
    {
        $this->actingAs(User::factory()->create(['lCambiarPassword' => false]));
        $original = $this->createItinerary('Original');
        $this->get(route('itinerarios.create', ['copiar' => $original->id]))->assertOk()
            ->assertViewHas('dias', fn ($days) => $days[0]['titulo'] === 'Llegada');
        $copy = $this->createItinerary('Copia');
        $this->put(route('itinerarios.update', $copy), ['nombre' => 'Copia editada', 'dias_json' => json_encode($this->days('Cambio'))])->assertSessionHasNoErrors();
        $this->assertSame('Llegada', $original->snapshot()[0]['titulo']);
        $this->assertSame('Cambio', $copy->snapshot()[0]['titulo']);
    }

    public function test_sale_keeps_snapshot_when_shared_itinerary_changes(): void
    {
        $this->actingAs(User::factory()->create(['lCambiarPassword' => false]));
        $itinerary = $this->createItinerary();
        $service = $this->service();
        $this->assign($service, $itinerary->id)->assertOk();
        $client = Cliente::create(['cNombre' => 'Itinerario', 'cPrimerApellido' => 'Cliente', 'lActivo' => true]);
        $this->post(route('agencia.ventas.store'), ['iIDCliente' => $client->iID,
            'items' => [['iIDServicio' => $service->iID, 'iCantidad' => 1]], 'accion' => 'apartar'])->assertSessionHasNoErrors();
        $sale = VentaPaquete::latest('iID')->firstOrFail();
        $detail = $sale->detalles()->firstOrFail();
        $this->assertSame('Llegada', $detail->itinerario[0]['titulo']);
        $this->put(route('itinerarios.update', $itinerary), ['nombre' => 'Nuevo nombre', 'dias_json' => json_encode($this->days('Nuevo recorrido'))])->assertSessionHasNoErrors();
        $this->assertSame('Llegada', $detail->fresh()->itinerario[0]['titulo']);
        $this->get(route('agencia.ventas.show', $sale))->assertOk()->assertSee('Itinerario contratado')->assertSee('Llegada')->assertDontSee('Nuevo recorrido');
        $this->get(route('agencia.ventas.show', ['venta' => $sale, 'seccion' => 'itinerario']))->assertOk()->assertSee('Llegada')->assertDontSee('Nuevo recorrido');
        $this->get(route('agencia.apartados.index', ['buscar' => $sale->folio]))->assertOk()->assertSee('Ver itinerario');
        $sale->update(['finalizada_at' => now()]);
        $this->get(route('agencia.ventas.index', ['buscar' => $sale->folio]))->assertOk()->assertSee('Ver itinerario');
        $this->get(route('agencia.ventas.show', ['venta' => $sale, 'seccion' => 'itinerario']))->assertOk()->assertSee('Itinerario contratado');
        $detail->update(['itinerario' => null]);
        $this->get(route('agencia.ventas.show', ['venta' => $sale, 'seccion' => 'itinerario']))->assertOk()
            ->assertSee('Itinerario actual del servicio')->assertSee('Nuevo recorrido')->assertDontSee('Itinerario contratado');
        $user = auth()->user();
        $user->roles()->detach();
        $role = Role::create(['name' => 'Ventas sin itinerarios', 'slug' => uniqid('sin-iti'), 'permissions' => ['ventas.ver']]);
        $user->roles()->attach($role);
        $user->unsetRelation('roles');
        $this->get(route('agencia.ventas.show', ['venta' => $sale, 'seccion' => 'itinerario']))->assertForbidden();
        $this->get(route('agencia.ventas.index', ['buscar' => $sale->folio]))->assertOk()->assertDontSee('Ver itinerario');
    }

    public function test_permissions_separate_view_edit_assignment_and_publication(): void
    {
        $admin = User::factory()->create(['lCambiarPassword' => false]);
        $this->actingAs($admin);
        $itinerary = $this->createItinerary();
        $service = $this->service();
        $user = User::factory()->create(['lCambiarPassword' => false]);
        $user->roles()->detach();
        $this->actingAs($user);
        $this->get(route('itinerarios.index'))->assertForbidden();
        $role = Role::create(['name' => 'Itinerarios', 'slug' => uniqid('iti'), 'permissions' => ['itinerarios.ver', 'servicios.ver', 'servicios.editar']]);
        $user->roles()->attach($role);
        $user->unsetRelation('roles');
        $this->get(route('itinerarios.index'))->assertOk()->assertDontSee('Nuevo itinerario');
        $this->get(route('itinerarios.edit', $itinerary))->assertOk()->assertDontSee('id="agregarDia"', false);
        $this->get(route('itinerarios.create'))->assertForbidden();
        $data = ['nombre' => 'Intento', 'dias_json' => json_encode($this->days())];
        $this->post(route('itinerarios.store'), $data)->assertForbidden();
        $this->put(route('itinerarios.update', $itinerary), $data)->assertForbidden();
        $this->assign($service, $itinerary->id)->assertForbidden();
        $role->update(['permissions' => ['itinerarios.ver', 'itinerarios.editar', 'itinerarios.asignar', 'servicios.ver', 'servicios.editar']]);
        $user->unsetRelation('roles');
        $this->post(route('itinerarios.store'), $data)->assertSessionHasNoErrors()->assertRedirect();
        $this->assign($service, $itinerary->id)->assertOk();
        $this->assign($service, $itinerary->id, true)->assertForbidden();
        $this->assertFalse($service->fresh()->lPublicarItinerario);
    }

    public function test_service_itinerary_pdf_is_printable_and_protects_internal_notes(): void
    {
        $this->actingAs(User::factory()->create(['lCambiarPassword' => false]));
        $itinerary = $this->createItinerary();
        $service = $this->service();
        $url = route('servicios.paquetes.itinerarioPdf', $service);
        $this->get($url)->assertStatus(422);
        $this->assign($service, $itinerary->id)->assertOk();
        $response = $this->get($url)->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-', $response->getContent());
        $this->assertStringContainsString('attachment;', $response->headers->get('Content-Disposition'));
        $service = $service->fresh();
        $html = view('servicios.itinerario.pdf', ['servicio' => $service, 'dias' => $service->itinerarioSnapshot(), 'marca' => 'De Viaje', 'logo' => null])->render();
        $this->assertStringContainsString('Recorrido familiar', $html);
        $this->assertStringContainsString('Recepción', $html);
        $this->assertStringContainsString('Por confirmar', $html);
        $this->assertStringNotContainsString('NOTA PRIVADA DEL OPERADOR', $html);
        $user = User::factory()->create(['lCambiarPassword' => false]);
        $user->roles()->detach();
        $role = Role::create(['name' => 'PDF itinerarios', 'slug' => uniqid('pdf'), 'permissions' => ['servicios.ver', 'itinerarios.ver']]);
        $user->roles()->attach($role);
        $this->actingAs($user)->get($url)->assertForbidden();
        $role->update(['permissions' => ['servicios.ver', 'itinerarios.ver', 'itinerarios.pdf']]);
        $user->unsetRelation('roles');
        $this->get($url)->assertOk()->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_validation_does_not_erase_existing_itinerary(): void
    {
        $this->actingAs(User::factory()->create(['lCambiarPassword' => false]));
        $itinerary = $this->createItinerary();
        $days = $this->days();
        $days[0]['actividades'][0]['hora'] = '25:90';
        $this->put(route('itinerarios.update', $itinerary), ['nombre' => 'Inválido', 'dias_json' => json_encode($days)])->assertSessionHasErrors();
        $this->put(route('itinerarios.update', $itinerary), ['nombre' => 'Inválido', 'dias_json' => '[]'])->assertSessionHasErrors();
        $this->assertSame('Recorrido familiar', $itinerary->fresh()->nombre);
        $this->assertSame('10:00', $itinerary->snapshot()[0]['actividades'][0]['hora']);
        $this->assign($this->service(), null, true)->assertStatus(422);
        $this->assign($this->service(), 99999999)->assertStatus(422);
    }
}
