<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB, Schema};

return new class extends Migration {
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id(); $table->string('name', 100); $table->string('slug', 120)->unique();
            $table->json('permissions'); $table->timestamps();
        });
        Schema::create('role_user', function (Blueprint $table) {
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps(); $table->primary(['role_id', 'user_id']);
        });
        Schema::create('permission_audits', function (Blueprint $table) {
            $table->id(); $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action'); $table->json('details'); $table->timestamps();
        });
        $all = \App\Models\Role::permissionKeys();
        $vendedor = ['servicios.ver', 'vender.ver', 'vender.venta', 'vender.apartado', 'apartados.ver', 'apartados.abonos', 'apartados.pdf', 'ventas.ver', 'ventas.pdf', 'clientes.ver', 'clientes.crear', 'clientes.editar'];
        foreach ([
            ['Administrador', 'administrador', $all],
            ['Supervisor', 'supervisor', array_values(array_filter($all, fn ($p) => !str_starts_with($p, 'permisos.') && !str_starts_with($p, 'usuarios.') && !str_starts_with($p, 'configuracion.')))],
            ['Vendedor', 'vendedor', $vendedor],
            ['Finanzas', 'finanzas', ['finanzas.ver', 'finanzas.ingresos', 'finanzas.egresos', 'finanzas.crear', 'finanzas.anular', 'finanzas.exportar', 'ventas.ver', 'ventas.todos', 'ventas.pdf', 'apartados.ver', 'apartados.todos', 'apartados.pdf']],
            ['Contenido', 'contenido', ['configuracion.ver', 'configuracion.editar', 'servicios.ver']],
        ] as [$name, $slug, $permissions]) {
            DB::table('roles')->insert(['name' => $name, 'slug' => $slug, 'permissions' => json_encode($permissions), 'created_at' => now(), 'updated_at' => now()]);
        }
        $admin = DB::table('roles')->where('slug', 'administrador')->value('id');
        // Conserva el acceso que tenían los usuarios existentes antes de introducir permisos.
        foreach (DB::table('users')->pluck('id') as $user) {
            DB::table('role_user')->insert(['role_id' => $admin, 'user_id' => $user, 'created_at' => now(), 'updated_at' => now()]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('permission_audits'); Schema::dropIfExists('role_user'); Schema::dropIfExists('roles');
    }
};
