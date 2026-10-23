<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\{Role, User};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PermissionController extends Controller
{
    public function index(Request $request)
    {
        $request->validate(['buscar' => 'nullable|string|max:100']);
        $roles = Role::withCount('users')->orderBy('id')->get();
        $users = User::with('roles')->where('lActivo', true)->where('lDev', false)->when($request->filled('buscar'), fn ($q) => $q->where(fn ($query) => $query->where('name', 'like', '%'.$request->buscar.'%')->orWhere('email', 'like', '%'.$request->buscar.'%')))->orderBy('name')->paginate(10)->withQueryString();
        $audits = DB::table('permission_audits')->leftJoin('users', 'users.id', '=', 'permission_audits.actor_id')->select('permission_audits.*', 'users.name as actor')->orderByDesc('permission_audits.id')->limit(10)->get();
        return view('admin.permisos.index', compact('roles', 'users', 'audits'));
    }

    private function validateRole(Request $request): array
    {
        $data = $request->validate(['name' => 'required|string|max:100', 'permissions' => 'nullable|array', 'permissions.*' => ['string', 'distinct', Rule::in(Role::permissionKeys())]]);
        $permissions = $data['permissions'] ?? [];
        // Una acción necesita también el permiso de acceso a su módulo.
        foreach ($permissions as $permission) {
            if (str_starts_with($permission, 'dashboard.')) continue;
            $access = explode('.', $permission)[0].'.ver';
            if (!in_array($access, $permissions, true)) $permissions[] = $access;
        }
        if (in_array('dashboard.origen', $permissions, true)) $permissions[] = 'dashboard.ingresos';
        if (array_intersect(['dashboard.ingresos', 'dashboard.egresos', 'dashboard.origen'], $permissions)) $permissions[] = 'dashboard.finanzas';
        $permissions = array_values(array_unique($permissions));
        if (in_array('itinerarios.asignar', $permissions, true) && !in_array('servicios.ver', $permissions, true)) $permissions[] = 'servicios.ver';
        if (in_array('servicios.costos.editar', $permissions, true) && !in_array('servicios.costos.ver', $permissions, true)) $permissions[] = 'servicios.costos.ver';
        return ['name' => $data['name'], 'permissions' => $permissions];
    }

    private function audit(string $action, array $details): void
    {
        DB::table('permission_audits')->insert(['actor_id' => auth()->id(), 'action' => $action, 'details' => json_encode($details, JSON_UNESCAPED_UNICODE), 'created_at' => now(), 'updated_at' => now()]);
    }

    public function store(Request $request)
    {
        $data = $this->validateRole($request);
        DB::transaction(function () use ($data) {
            $role = Role::create($data + ['slug' => Str::slug($data['name']).'-'.Str::lower(Str::random(8))]);
            $this->audit('Rol creado', ['rol' => $role->name, 'permisos' => $data['permissions']]);
        });
        return back()->with('success', 'Rol creado correctamente.');
    }

    public function update(Request $request, Role $role)
    {
        abort_if($role->slug === 'administrador', 422, 'El rol Administrador conserva todos los permisos y no puede modificarse.');
        $data = $this->validateRole($request);
        DB::transaction(function () use ($role, $data) {
            $before = $role->permissions;
            $role->update($data);
            $this->audit('Rol actualizado', ['rol' => $role->name, 'antes' => $before, 'despues' => $data['permissions']]);
        });
        return back()->with('success', 'Permisos del rol actualizados.');
    }

    public function destroy(Role $role)
    {
        DB::transaction(function () use ($role) {
            $role = Role::whereKey($role->id)->lockForUpdate()->firstOrFail();
            abort_if($role->slug === 'administrador' || $role->users()->exists(), 422, 'No se puede eliminar el rol Administrador ni un rol asignado a usuarios.');
            $this->audit('Rol eliminado', ['rol' => $role->name, 'permisos' => $role->permissions]);
            $role->delete();
        });
        return back()->with('success', 'Rol eliminado correctamente.');
    }

    public function assign(Request $request, User $user)
    {
        abort_unless($user->lActivo, 422, 'No se pueden asignar roles a usuarios inactivos.');
        $data = $request->validate(['roles' => 'nullable|array', 'roles.*' => 'integer|distinct|exists:roles,id']);
        DB::transaction(function () use ($data, $user) {
            $admin = Role::where('slug', 'administrador')->lockForUpdate()->firstOrFail();
            $ids = array_map('intval', $data['roles'] ?? []);
            $before = $user->roles()->pluck('roles.id')->all();
            if (in_array($admin->id, $before) && !in_array($admin->id, $ids)) {
                if ($user->id === auth()->id() || !$admin->users()->where('lActivo', true)->where('users.id', '!=', $user->id)->exists()) {
                    throw ValidationException::withMessages(['roles' => 'No puedes quitarte tu rol Administrador ni retirar al último administrador activo.']);
                }
            }
            // Bloquea los roles seleccionados para evitar eliminarlos mientras se asignan.
            Role::whereIn('id', $ids)->lockForUpdate()->get();
            $user->roles()->sync($ids);
            $this->audit('Roles asignados', ['usuario' => $user->email, 'antes' => $before, 'despues' => $ids]);
        });
        return back()->with('success', 'Roles del usuario actualizados.');
    }
}
