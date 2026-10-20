<?php

namespace App\Console\Commands;

use App\Models\{Role, User};
use Illuminate\Console\Command;
use Illuminate\Support\Facades\{DB, Validator};

class CreateAdministrator extends Command
{
    protected $signature = 'agencia:crear-administrador';
    protected $description = 'Crea un administrador nuevo sin contraseñas predeterminadas ni modificar usuarios existentes';

    public function handle(): int
    {
        $data = [
            'name' => $this->ask('Nombre del administrador'),
            'email' => $this->ask('Correo de ingreso'),
            'password' => $this->secret('Contraseña (mínimo 12 caracteres)'),
            'password_confirmation' => $this->secret('Confirma la contraseña'),
        ];
        $validator = Validator::make($data, [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:12|confirmed',
        ]);
        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) $this->error($error);
            return self::FAILURE;
        }
        $role = Role::where('slug', 'administrador')->first();
        if (!$role) {
            $this->error('Ejecuta las migraciones antes de crear el administrador.');
            return self::FAILURE;
        }
        DB::transaction(function () use ($data, $role) {
            $user = User::create([
                'name' => $data['name'], 'email' => $data['email'], 'password' => $data['password'],
                'lActivo' => true, 'lCambiarPassword' => false,
            ]);
            $user->roles()->attach($role);
        });
        $this->info('Administrador creado. Ingresa en /login con el correo y la contraseña elegidos.');
        return self::SUCCESS;
    }
}
