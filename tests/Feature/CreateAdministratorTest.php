<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CreateAdministratorTest extends TestCase
{
    use DatabaseTransactions;

    public function test_crea_un_administrador_con_clave_privada_y_permisos(): void
    {
        $email = 'instalacion-'.uniqid().'@example.com';
        $password = 'Clave-segura-de-prueba-2026';
        $this->artisan('agencia:crear-administrador')
            ->expectsQuestion('Nombre del administrador', 'Administrador de instalación')
            ->expectsQuestion('Correo de ingreso', $email)
            ->expectsQuestion('Contraseña (mínimo 12 caracteres)', $password)
            ->expectsQuestion('Confirma la contraseña', $password)
            ->assertExitCode(0);
        $user = User::where('email', $email)->firstOrFail();
        $this->assertTrue(Hash::check($password, $user->password));
        $this->assertTrue($user->lActivo);
        $this->assertFalse($user->lCambiarPassword);
        $this->assertTrue($user->hasPermission('permisos.gestionar'));
    }

    public function test_no_reemplaza_usuarios_existentes_ni_acepta_claves_cortas(): void
    {
        $existing = User::factory()->create();
        $hash = $existing->password;
        $this->artisan('agencia:crear-administrador')
            ->expectsQuestion('Nombre del administrador', 'Otro nombre')
            ->expectsQuestion('Correo de ingreso', $existing->email)
            ->expectsQuestion('Contraseña (mínimo 12 caracteres)', 'Nueva-clave-larga-2026')
            ->expectsQuestion('Confirma la contraseña', 'Nueva-clave-larga-2026')
            ->assertExitCode(1);
        $this->assertSame($hash, $existing->fresh()->password);
        $email = 'invalido-'.uniqid().'@example.com';
        $this->artisan('agencia:crear-administrador')
            ->expectsQuestion('Nombre del administrador', 'Nuevo')
            ->expectsQuestion('Correo de ingreso', $email)
            ->expectsQuestion('Contraseña (mínimo 12 caracteres)', 'corta')
            ->expectsQuestion('Confirma la contraseña', 'corta')
            ->assertExitCode(1);
        $this->assertDatabaseMissing('users', ['email' => $email]);
    }
}
