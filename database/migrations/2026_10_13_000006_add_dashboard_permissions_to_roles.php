<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        foreach (DB::table('roles')->get() as $role) {
            $permissions = json_decode($role->permissions, true) ?? [];
            if ($role->slug === 'administrador') $permissions = \App\Models\Role::permissionKeys();
            else {
                foreach ([
                    'finanzas.ver' => ['dashboard.finanzas'],
                    'finanzas.ingresos' => ['dashboard.ingresos', 'dashboard.origen'],
                    'finanzas.egresos' => ['dashboard.egresos'],
                    'apartados.ver' => ['dashboard.apartados'],
                    'servicios.ver' => ['dashboard.servicios', 'dashboard.viajes'],
                ] as $old => $new) {
                    if (in_array($old, $permissions, true)) $permissions = array_merge($permissions, $new);
                }
                if (array_intersect(['vender.ver', 'apartados.ver', 'ventas.ver', 'clientes.ver'], $permissions)) $permissions[] = 'dashboard.accesos';
            }
            DB::table('roles')->where('id', $role->id)->update(['permissions' => json_encode(array_values(array_unique($permissions))), 'updated_at' => now()]);
        }
    }

    public function down(): void
    {
        foreach (DB::table('roles')->get() as $role) {
            $permissions = array_filter(json_decode($role->permissions, true) ?? [], fn ($key) => !str_starts_with($key, 'dashboard.'));
            DB::table('roles')->where('id', $role->id)->update(['permissions' => json_encode(array_values($permissions)), 'updated_at' => now()]);
        }
    }
};
