<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use DatabaseTransactions;

    private function datos(): array
    {
        return ['name' => 'Nombre actualizado', 'cPrimerApellido' => 'Apellido', 'cTelefono' => '5512345678'];
    }

    public function test_puede_cerrar_sesion_sin_cambiar_la_password_inicial(): void
    {
        $user = User::factory()->create(['lCambiarPassword' => true]);
        $password = $user->password;
        $this->actingAs($user)->get(route('password.change'))->assertOk()->assertSee('Cerrar sesión');
        $this->post(route('logout'))->assertRedirect(route('login'));
        $this->assertGuest();
        $this->get(route('login'))->assertOk();
        $this->assertSame($password, $user->fresh()->password);
        $this->assertTrue($user->fresh()->lCambiarPassword);
    }

    public function test_solo_el_usuario_autenticado_edita_su_perfil_sin_cambiar_el_ingreso(): void
    {
        $this->get(route('perfil.edit'))->assertRedirect(route('login'));
        $this->put(route('perfil.update'), $this->datos())->assertRedirect(route('login'));
        $user = User::factory()->create(['lCambiarPassword' => false]);
        $otro = User::factory()->create(['lCambiarPassword' => false]);
        $email = $user->email;
        $password = $user->password;
        $this->actingAs($user)->get(route('perfil.edit'))->assertOk()->assertSee('Usuario de ingreso')->assertSee('Editar perfil')->assertSee('Cerrar sesión');
        $this->put(route('perfil.update'), $this->datos() + ['email' => 'otro@example.com', 'id' => $otro->id, 'lActivo' => false])
            ->assertRedirect(route('perfil.edit'))->assertSessionHasNoErrors();
        $user->refresh();
        $this->assertSame($email, $user->email);
        $this->assertSame($password, $user->password);
        $this->assertSame('5512345678', $user->cTelefono);
        $this->assertTrue($user->lActivo);
        $this->assertNotSame('Nombre actualizado', $otro->fresh()->name);
    }

    public function test_cambiar_password_exige_la_actual_y_confirmacion(): void
    {
        $user = User::factory()->create(['lCambiarPassword' => false, 'password' => 'Original123!']);
        $this->actingAs($user);
        $datos = $this->datos() + ['password' => 'Nueva123!', 'password_confirmation' => 'Nueva123!'];
        $this->put(route('perfil.update'), $datos)->assertSessionHasErrors('current_password');
        $this->put(route('perfil.update'), $datos + ['current_password' => 'Incorrecta'])->assertSessionHasErrors('current_password');
        $this->assertTrue(Hash::check('Original123!', $user->fresh()->password));
        $this->put(route('perfil.update'), array_merge($datos, ['current_password' => 'Original123!', 'password_confirmation' => 'Diferente']))->assertSessionHasErrors('password');
        $this->put(route('perfil.update'), $datos + ['current_password' => 'Original123!'])->assertSessionHasNoErrors()->assertRedirect(route('perfil.edit'));
        $this->assertTrue(Hash::check('Nueva123!', $user->fresh()->password));
    }

    public function test_foto_se_reemplaza_y_se_sirve_solo_para_su_dueno(): void
    {
        Storage::fake('local');
        $user = User::factory()->create(['lCambiarPassword' => false]);
        $this->actingAs($user)->put(route('perfil.update'), $this->datos() + ['foto' => UploadedFile::fake()->image('foto.jpg')])->assertSessionHasNoErrors();
        $primera = $user->fresh()->profile_photo_path;
        Storage::disk('local')->assertExists($primera);
        $this->get(route('perfil.photo'))->assertOk();
        $this->get(route('admin.usuarios.gridData'))->assertOk()
            ->assertJsonFragment(['fotoUrl' => route('admin.usuarios.foto', ['user' => $user->id, 'v' => $user->fresh()->updated_at?->timestamp])]);
        $this->get(route('admin.usuarios.foto', $user))->assertOk();
        $this->put(route('perfil.update'), $this->datos() + ['foto' => UploadedFile::fake()->image('nueva.png')])->assertSessionHasNoErrors();
        Storage::disk('local')->assertMissing($primera);
        Storage::disk('local')->assertExists($user->fresh()->profile_photo_path);
        $this->put(route('perfil.update'), $this->datos() + ['foto' => UploadedFile::fake()->create('archivo.svg', 10, 'image/svg+xml')])->assertSessionHasErrors('foto');
        $this->actingAs(User::factory()->create(['lCambiarPassword' => false]))->get(route('perfil.photo'))->assertNotFound();
    }
}
