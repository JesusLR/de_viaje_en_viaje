<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class UserStatusFilterTest extends TestCase
{
    use DatabaseTransactions;

    public function test_reactivar_usuario_requiere_permiso_y_conserva_roles(): void
    {
        $actor = User::factory()->create(['lCambiarPassword' => false]);
        $inactive = User::factory()->create(['lActivo' => false]);
        $inactive->roles()->detach();
        $role = \App\Models\Role::create(['name' => 'Prueba usuarios', 'slug' => uniqid('usuarios'), 'permissions' => ['usuarios.ver']]);
        $actor->roles()->sync([$role->id]);
        $inactive->roles()->attach($role);
        $this->actingAs($actor);
        $this->postJson(route('admin.usuarios.activar'), ['iID' => $inactive->id])->assertForbidden();
        $this->assertFalse($inactive->fresh()->lActivo);
        $role->update(['permissions' => ['usuarios.ver', 'usuarios.activar']]);
        $actor->unsetRelation('roles');
        $this->postJson(route('admin.usuarios.activar'), ['iID' => $inactive->id])->assertOk()->assertJsonPath('lSuccess', true);
        $this->assertTrue($inactive->fresh()->lActivo);
        $this->assertEquals([$role->id], $inactive->roles()->pluck('roles.id')->all());
    }

    public function test_user_status_filters_and_active_role_assignment(): void
    {
        $admin = User::factory()->create(['lCambiarPassword' => false]);
        $active = User::factory()->create(['lActivo' => true]);
        $inactive = User::factory()->create(['lActivo' => false]);
        $developer = User::factory()->create(['lActivo' => true]);
        $developer->forceFill(['lDev' => true])->save();
        $this->actingAs($admin);
        foreach ([null, 'activos', 'inactivos', 'todos'] as $estado) {
            $response = $this->getJson(route('admin.usuarios.gridData', $estado ? ['estado' => $estado] : []))->assertOk();
            $ids = collect($response->json('cData'))->pluck('iID');
            $this->assertEquals($estado !== 'inactivos', $ids->contains($active->id));
            $this->assertEquals(in_array($estado, ['inactivos', 'todos']), $ids->contains($inactive->id));
            $this->assertFalse($ids->contains($developer->id));
        }
        $this->get(route('admin.permisos.index', ['buscar' => $active->email]))->assertOk()->assertViewHas('users', fn ($users) => $users->contains('id', $active->id));
        $this->get(route('admin.permisos.index', ['buscar' => $inactive->email]))->assertOk()->assertViewHas('users', fn ($users) => !$users->contains('id', $inactive->id));
        $this->putJson(route('admin.permisos.assign', $inactive), ['roles' => []])->assertUnprocessable();
    }
}
