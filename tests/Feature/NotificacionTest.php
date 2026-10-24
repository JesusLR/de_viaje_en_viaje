<?php

namespace Tests\Feature;

use App\Models\{Cotizacion, Role, User};
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class NotificacionTest extends TestCase
{
    use DatabaseTransactions;

    private function usuario(array $permissions, bool $activo = true): User
    {
        $user = User::factory()->create(['lActivo' => $activo, 'lCambiarPassword' => false]);
        $role = Role::create(['name' => 'Notificaciones prueba', 'slug' => uniqid('avisos'), 'permissions' => $permissions]);
        $user->roles()->sync([$role->id]);
        return $user;
    }

    public function test_public_quote_notifies_only_active_authorized_users_and_read_is_individual(): void
    {
        $admin = User::factory()->create(['lCambiarPassword' => false]);
        $asesor = $this->usuario(['cotizaciones.ver']);
        $sinPermiso = $this->usuario([]);
        $inactivo = $this->usuario(['cotizaciones.ver'], false);
        $this->post(route('cotizacion.enviar'), ['nombre' => 'Cliente notificaciones', 'whatsapp' => '9991234567',
            'fechas' => 'Diciembre', 'dias' => 5, 'noches' => 4, 'personas' => 2, 'adultos' => 2, 'menores' => 0,
            'tipo_hotel' => 'Todo incluido', 'ambiente' => 'Ambos', 'presupuesto' => 10000, 'moneda' => 'MXN'])
            ->assertSessionHasNoErrors()->assertRedirect();
        $cotizacion = Cotizacion::latest('id')->firstOrFail();
        $this->assertEquals(1, $asesor->notifications()->count());
        $this->assertEquals(1, $admin->notifications()->count());
        $this->assertEquals(0, $sinPermiso->notifications()->count());
        $this->assertEquals(0, $inactivo->notifications()->count());
        $notification = $asesor->notifications()->firstOrFail();
        $this->actingAs($asesor);
        $this->getJson(route('notificaciones.index'))->assertOk()->assertJsonPath('sin_leer', 1)
            ->assertJsonPath('notificaciones.0.url', route('cotizaciones.show', $cotizacion, false));
        $this->get(route('dashboard'))->assertOk()->assertSee('campanaNotificaciones', false)->assertSee('notificaciones.js');
        $this->postJson(route('notificaciones.leer', $notification->id))->assertOk();
        $this->getJson(route('notificaciones.index'))->assertJsonPath('sin_leer', 0)->assertJsonPath('notificaciones.0.leida', true);
        $this->assertEquals(1, $admin->unreadNotifications()->count());
        $this->postJson(route('notificaciones.leer', $admin->notifications()->first()->id))->assertNotFound();
        $asesor->roles()->detach();
        $asesor->unsetRelation('roles');
        $this->getJson(route('notificaciones.index'))->assertJsonCount(0, 'notificaciones');
        $this->postJson(route('notificaciones.leer', $notification->id))->assertNotFound();
    }

    public function test_generic_notifications_read_all_and_authentication(): void
    {
        $this->getJson(route('notificaciones.index'))->assertUnauthorized();
        $user = $this->usuario([]);
        $otro = $this->usuario([]);
        foreach ([$user, $user, $otro] as $recipient) {
            DB::table('notifications')->insert(['id' => (string) Str::uuid(), 'type' => 'EventoPrueba',
                'notifiable_type' => User::class, 'notifiable_id' => $recipient->id,
                'data' => json_encode(['titulo' => 'Otro evento', 'mensaje' => 'Notificación genérica']), 'created_at' => now(), 'updated_at' => now()]);
        }
        $this->actingAs($user);
        $this->getJson(route('notificaciones.index'))->assertOk()->assertJsonPath('sin_leer', 2)->assertJsonCount(2, 'notificaciones')->assertJsonPath('notificaciones.0.url', null);
        $this->postJson(route('notificaciones.leerTodas'))->assertOk();
        $this->getJson(route('notificaciones.index'))->assertJsonPath('sin_leer', 0);
        $this->assertEquals(1, $otro->unreadNotifications()->count());
    }
}
