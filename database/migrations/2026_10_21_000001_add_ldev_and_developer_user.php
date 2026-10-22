<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('lDev')->default(false);
        });

        DB::transaction(function () {
            $role = DB::table('roles')->where('slug', 'administrador')->first();
            if (!$role) {
                throw new RuntimeException('No existe el rol administrador.');
            }
            $now = now();
            $userId = DB::table('users')->where('email', 'usuario.desarrollador')->value('id');
            if (!$userId) {
                $userId = DB::table('users')->insertGetId([
                    'name' => 'usuario.desarrollador',
                    'email' => 'usuario.desarrollador',
                    'password' => '$2y$12$Z7YoYbNc/V73jc088DPOZO.QWNSeZjRRgz5M3IoSw5HTbFVKVRVEy',
                    'lActivo' => true,
                    'lCambiarPassword' => false,
                    'lDev' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            } else {
                DB::table('users')->where('id', $userId)->update(['lDev' => true]);
            }
            DB::table('role_user')->updateOrInsert(
                ['user_id' => $userId, 'role_id' => $role->id],
                ['created_at' => $now, 'updated_at' => $now],
            );
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('lDev');
        });
    }
};
