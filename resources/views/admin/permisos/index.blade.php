@extends('layouts.app')
@section('titulo', 'Roles y permisos')
@section('contenido')
<div class="apartados-panel permisos-panel">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div class="d-flex align-items-center gap-3"><span class="apartados-emblema"><i class="fas fa-user-lock" aria-hidden="true"></i></span><div><h3 class="fw-bold mb-1">Roles y permisos</h3><p class="text-muted small mb-0">Define qué puede consultar y hacer cada usuario.</p></div></div>
        @can('permisos.gestionar')
        <button class="apartado-icono nuevo-apartado" data-bs-toggle="modal" data-bs-target="#rol0"><i class="fas fa-plus" aria-hidden="true"></i>Nuevo rol</button>
        @endcan
    </div>
    @include('agencia.partials.avisos')
    <div class="permisos-nota mb-4"><i class="fas fa-info-circle me-2" aria-hidden="true"></i>Los permisos de todos los roles asignados se suman. Al habilitar una acción también se incluye el acceso a su módulo. El perfil personal y cerrar sesión siempre están disponibles.</div>
    <div class="row g-3 mb-4">
        @foreach($roles as $role)
        <div class="col-md-6 col-xl-4"><section class="apartados-filtros h-100">
            <div class="d-flex align-items-start justify-content-between gap-2"><div><h5 class="permiso-titulo">{{ $role->name }}</h5><small class="text-muted">{{ $role->users_count }} usuarios · {{ count($role->permissions) }} permisos</small></div>
                <div class="apartado-acciones">
                    @if($role->slug === 'administrador')
                        <span class="apartados-contador"><i class="fas fa-lock me-1"></i>Protegido</span>
                    @else
                        @can('permisos.gestionar')
                        <button class="apartado-icono" data-bs-toggle="modal" data-bs-target="#rol{{ $role->id }}" title="Editar permisos" aria-label="Editar permisos de {{ $role->name }}"><i class="fas fa-pen" aria-hidden="true"></i></button>
                        @endcan
                        @if($role->users_count === 0)
                            @can('permisos.eliminar')
                            <form method="POST" action="{{ route('admin.permisos.destroy', $role) }}" data-confirm="¿Eliminar este rol?">@csrf @method('DELETE')<button class="apartado-icono accion-cancelar" title="Eliminar rol" aria-label="Eliminar {{ $role->name }}"><i class="fas fa-trash-alt" aria-hidden="true"></i></button></form>
                            @endcan
                        @endif
                    @endif
                </div>
            </div>
        </section></div>
        @endforeach
    </div>
    <form class="apartados-filtros mb-4" method="GET" data-grid-filter="permisos"><label for="buscarPermisoUsuario" class="form-label">Buscar usuario</label><div class="d-flex gap-2"><div class="apartados-busqueda flex-grow-1"><i class="fas fa-search" aria-hidden="true"></i><input id="buscarPermisoUsuario" type="search" name="buscar" value="{{ request('buscar') }}" class="form-control" placeholder="Nombre o correo"></div><button class="apartado-icono accion-principal" aria-label="Buscar"><i class="fas fa-search"></i></button></div></form>
    <div class="apartados-grid mb-4" data-grid-results="permisos"><div class="px-4 py-3 border-bottom fw-semibold">Roles por usuario</div><div class="table-responsive"><table class="table align-middle mb-0"><thead><tr><th>Usuario</th><th>Estado</th><th>Roles</th><th class="text-end">Acciones</th></tr></thead><tbody>
        @foreach($users as $usuario)
        <tr><td><strong>{{ $usuario->nombre_completo }}</strong><small class="d-block text-muted">{{ $usuario->email }}</small></td><td><span class="apartado-estado {{ $usuario->lActivo ? 'liquidado' : 'cancelado' }}">{{ $usuario->lActivo ? 'Activo' : 'Inactivo' }}</span></td><td>@forelse($usuario->roles as $role)<span class="apartados-contador me-1">{{ $role->name }}</span>@empty<span class="text-muted small">Sin roles asignados</span>@endforelse</td><td class="text-end">
        @can('permisos.asignar')
        <button class="apartado-icono" data-bs-toggle="modal" data-bs-target="#usuarioRoles{{ $usuario->id }}" title="Asignar roles" aria-label="Asignar roles a {{ $usuario->name }}"><i class="fas fa-user-tag" aria-hidden="true"></i></button>
        @endcan
        </td></tr>
        @endforeach
    </tbody></table></div>@include('agencia.partials.paginacion', ['paginador' => $users, 'etiqueta' => 'usuarios'])</div>
    <section class="apartados-filtros"><h5 class="permiso-titulo">Últimos cambios</h5>@forelse($audits as $audit)<div class="permiso-auditoria"><span><strong>{{ $audit->action }}</strong> · {{ $audit->actor ?? 'Usuario eliminado' }}</span><small>{{ \Carbon\Carbon::parse($audit->created_at)->format('d/m/Y H:i') }}</small><small class="d-block text-muted">{{ json_decode($audit->details, true)['rol'] ?? json_decode($audit->details, true)['usuario'] ?? '' }}</small></div>@empty<p class="text-muted small mb-0">Aún no se han registrado cambios de permisos.</p>@endforelse</section>
</div>
@can('permisos.gestionar')
    @include('admin.permisos.rol-modal', ['role' => null])
    @foreach($roles->where('slug', '!=', 'administrador') as $role)
        @include('admin.permisos.rol-modal', ['role' => $role])
    @endforeach
@endcan
<div data-grid-results="permisos">
@can('permisos.asignar')
@foreach($users as $usuario)
<div class="modal fade apartado-modal-shell" id="usuarioRoles{{ $usuario->id }}" tabindex="-1" aria-labelledby="usuarioRolesTitulo{{ $usuario->id }}" aria-hidden="true"><div class="modal-dialog modal-dialog-centered"><div class="modal-content">
    <div class="modal-header"><h5 class="modal-title" id="usuarioRolesTitulo{{ $usuario->id }}"><i class="fas fa-user-tag me-2"></i>Asignar roles</h5><button class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button></div>
    <form method="POST" action="{{ route('admin.permisos.assign', $usuario) }}">@csrf @method('PUT')
        <div class="modal-body venta-detalle-page"><p class="fw-semibold">{{ $usuario->nombre_completo }}</p><label class="form-label" for="roles{{ $usuario->id }}">Roles del usuario</label><select class="form-select" id="roles{{ $usuario->id }}" name="roles[]" multiple>@foreach($roles as $role)<option value="{{ $role->id }}" @selected($usuario->roles->contains('id', $role->id))>{{ $role->name }}</option>@endforeach</select><p class="text-muted small mt-3 mb-0">Sin roles, el usuario solo podrá consultar su perfil y cerrar sesión.</p></div>
        <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal"><i class="fas fa-times me-1"></i>Cancelar</button><button class="btn btn-primary"><i class="fas fa-save me-1"></i>Guardar roles</button></div>
    </form>
</div></div></div>
@endforeach
@endcan
</div>
@endsection
@push('styles')
<link rel="stylesheet" href="{{ asset('css/agencia-grid.css') }}">
<link rel="stylesheet" href="{{ asset('css/apartados-modals.css') }}">
<link rel="stylesheet" href="{{ asset('css/catalogos-module.css') }}">
<style>
.permisos-rol-modal .modal-content{max-height:calc(100vh - 3.5rem);max-height:calc(100dvh - 3.5rem)}
.permisos-rol-modal .modal-content>form{display:flex;flex-direction:column;flex:1;min-height:0;overflow:hidden}
.permisos-rol-modal .modal-body{flex:1;min-height:0;overflow-y:auto;overscroll-behavior:contain}
.permisos-rol-modal .modal-header,.permisos-rol-modal .modal-footer{flex-shrink:0}
.permisos-rol-modal .modal-footer{background:#fff;border-top:1px solid #e8edf4;padding:16px 22px}
.permisos-rol-modal .modal-footer .btn{border-radius:10px;font-size:13px;padding:10px 16px}
.permisos-rol-modal .modal-footer .btn-primary{background:#536fe8;border-color:#536fe8}
@media(max-width:575px){.permisos-rol-modal .modal-content{max-height:calc(100vh - 1rem);max-height:calc(100dvh - 1rem)}}
.permiso-titulo{font-size:14px;font-weight:650;color:#25324b;margin-bottom:8px}.permisos-nota{background:#edf1ff;color:#64748b;border-radius:14px;padding:18px 22px;font-size:12px;line-height:1.7}.permiso-grupo{padding:16px;background:#fff;border:1px solid #e8edf4;border-radius:12px;height:100%}.permiso-grupo .form-check{margin-bottom:9px;font-size:12px;color:#64748b}.permiso-auditoria{padding:12px 0;border-bottom:1px solid #eef2f7;font-size:12px;color:#64748b}.permiso-auditoria>small{font-size:10px;margin-left:12px}.permisos-panel .modal-footer .btn-primary{background:#536fe8;border-color:#536fe8}.permisos-panel .modal-content{border-radius:18px}
</style>
@endpush
