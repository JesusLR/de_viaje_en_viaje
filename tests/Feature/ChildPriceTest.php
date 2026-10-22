<?php

namespace Tests\Feature;

use App\Models\{Role, ServicioTuristico, User};
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ChildPriceTest extends TestCase
{
    use DatabaseTransactions;

    public function test_child_price_can_be_enabled_free_priced_and_hidden_again(): void
    {
        $this->actingAs(User::factory()->create(['lCambiarPassword' => false]));
        $data = ['cNombre' => 'Paquete menores '.uniqid(), 'dPrecioCompra' => 100,
            'dPrecioVenta' => 500, 'lUsaVigencia' => 0, 'lActivo' => 1];
        $response = $this->postJson(route('servicios.paquetes.saveData'), $data)
            ->assertOk()->assertJsonPath('lSuccess', true);
        $id = $response->json('cData.iID');
        $service = ServicioTuristico::findOrFail($id);
        $this->assertFalse($service->lPrecioMenorActivo);
        $this->get(route('catalogo.publico.show', $id))->assertOk()->assertDontSee('Precio para menor:');

        foreach (['', 0, 125.50] as $price) {
            $this->postJson(route('servicios.paquetes.saveData'), $data + [
                'iID' => $id, 'lPrecioMenorActivo' => 1, 'dPrecioMenor' => $price,
            ])->assertOk()->assertJsonPath('lSuccess', true);
            $this->assertTrue($service->fresh()->lPrecioMenorActivo);
            $this->assertEquals((float) $price, $service->fresh()->dPrecioMenor);
            $this->getJson(route('servicios.paquetes.getData', $id))
                ->assertOk()->assertJsonPath('cData.lPrecioMenorActivo', true);
            $label = (float) $price > 0 ? '125.50 MXN' : 'Gratis';
            $this->get(route('catalogo.publico.show', $id))->assertOk()->assertSee('Precio para menor:')->assertSee($label);
            $html = view('publico.partials.paquete-card', ['paquete' => $service->fresh()])->render();
            $this->assertStringContainsString($label, $html);
        }
        $this->postJson(route('servicios.paquetes.saveData'), $data + [
            'iID' => $id, 'lPrecioMenorActivo' => 0, 'dPrecioMenor' => 125.50,
        ])->assertOk();
        $this->get(route('catalogo.publico.show', $id))->assertOk()->assertDontSee('Precio para menor:');
        $this->assertStringNotContainsString('Precio para menor:', view('publico.partials.paquete-card', ['paquete' => $service->fresh()])->render());
        $grid = $this->getJson(route('servicios.paquetes.gridData', ['search' => ['value' => $data['cNombre']]]))->assertOk();
        $grid->assertJsonPath('cData.0.lPrecioMenorActivo', false)->assertJsonPath('cData.0.dPrecioMenor', null);
        $this->postJson(route('servicios.paquetes.saveData'), $data + [
            'iID' => $id, 'lPrecioMenorActivo' => 1, 'dPrecioMenor' => -1,
        ])->assertStatus(422);
    }

    public function test_child_price_requires_price_permission(): void
    {
        $user = User::factory()->create(['lCambiarPassword' => false]);
        $user->roles()->detach();
        $role = Role::create(['name' => 'Editor de paquetes', 'slug' => uniqid('editor'),
            'permissions' => ['servicios.ver', 'servicios.editar']]);
        $user->roles()->attach($role);
        $service = ServicioTuristico::create(['cCodigo' => uniqid('MEN'), 'cNombre' => 'Precio protegido',
            'dPrecioVenta' => 500, 'lUsaVigencia' => false, 'lActivo' => true]);
        $this->actingAs($user)->postJson(route('servicios.paquetes.saveData'), [
            'iID' => $service->iID, 'cNombre' => $service->cNombre,
            'lPrecioMenorActivo' => 1, 'dPrecioMenor' => 100,
        ])->assertForbidden();
        $this->assertFalse($service->fresh()->lPrecioMenorActivo);
    }
}
