@php
    $roleId = $role?->id ?? 0;
    $editingOld = (int) old('role_id', -1) === $roleId;
    $selected = $editingOld ? old('permissions', []) : ($role?->permissions ?? []);
@endphp
<div class="modal fade apartado-modal-shell permisos-rol-modal" id="rol{{ $roleId }}" tabindex="-1" aria-labelledby="rolTitulo{{ $roleId }}" aria-hidden="true"><div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable"><div class="modal-content">
    <div class="modal-header"><h5 class="modal-title" id="rolTitulo{{ $roleId }}"><i class="fas fa-user-lock me-2"></i>{{ $role ? 'Editar rol' : 'Nuevo rol' }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button></div>
    <form method="POST" action="{{ $role ? route('admin.permisos.update', $role) : route('admin.permisos.store') }}">@csrf @if($role) @method('PUT') @endif
        <input type="hidden" name="role_id" value="{{ $roleId }}">
        <div class="modal-body venta-detalle-page">
            <label for="rolNombre{{ $roleId }}" class="form-label">Nombre del rol</label><input id="rolNombre{{ $roleId }}" name="name" class="form-control mb-4" required maxlength="100" value="{{ $editingOld ? old('name') : $role?->name }}">
            <div class="row g-3">@foreach(config('permissions') as $group)
                <div class="col-md-6 col-lg-4"><section class="permiso-grupo"><h6 class="permiso-titulo">{{ $group['label'] }}</h6>
                @foreach($group['actions'] as $key => $label)
                <div class="form-check"><input type="checkbox" class="form-check-input" name="permissions[]" value="{{ $key }}" id="permiso{{ $roleId }}{{ str_replace('.', '-', $key) }}" @checked(in_array($key, $selected))><label class="form-check-label" for="permiso{{ $roleId }}{{ str_replace('.', '-', $key) }}">{{ $label }}</label></div>
                @endforeach</section></div>
            @endforeach</div>
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal"><i class="fas fa-times me-1"></i>Cancelar</button><button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Guardar permisos</button></div>
    </form>
</div></div></div>
