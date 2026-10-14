@extends('layouts.app')
@section('titulo', 'Mi perfil')
@section('contenido')
<div class="apartados-panel perfil-panel">
    <div class="d-flex align-items-center gap-3 mb-4"><span class="apartados-emblema"><i class="fas fa-user-edit" aria-hidden="true"></i></span><div><h3 class="fw-bold mb-1">Mi perfil</h3><p class="text-muted small mb-0">Actualiza tus datos personales y tu contraseña.</p></div></div>
    <form action="{{ route('perfil.update') }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        <div class="row g-4">
            <div class="col-lg-4">
                <section class="apartados-filtros h-100">
                    <div class="perfil-foto mb-3">
                        <img id="fotoPerfilPreview" src="{{ $user->profile_photo_path ? route('perfil.photo', ['v' => $user->updated_at?->timestamp]) : '' }}" alt="Foto de perfil" @if(!$user->profile_photo_path) hidden @endif>
                        <i id="fotoPerfilIcono" class="fas fa-user" aria-hidden="true" @if($user->profile_photo_path) hidden @endif></i>
                    </div>
                    <h5 class="text-center fw-bold">{{ $user->nombre_completo }}</h5>
                    <label for="foto" class="form-label mt-3">Foto de perfil</label>
                    <input type="file" class="form-control @error('foto') is-invalid @enderror" id="foto" name="foto" accept="image/jpeg,image/png,image/webp">
                    <small class="text-muted d-block mt-2">JPG, PNG o WebP. Máximo 2 MB.</small>
                    @error('foto')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    <label for="usuarioIngreso" class="form-label mt-4">Usuario de ingreso</label>
                    <input id="usuarioIngreso" class="form-control" type="text" value="{{ $user->email }}" readonly aria-describedby="usuarioIngresoAyuda">
                    <small id="usuarioIngresoAyuda" class="text-muted d-block mt-2"><i class="fas fa-lock me-1" aria-hidden="true"></i>Tu usuario de ingreso no se puede modificar.</small>
                </section>
            </div>
            <div class="col-lg-8">
                <section class="apartados-filtros mb-4">
                    <h5 class="perfil-seccion"><i class="fas fa-address-card me-2" aria-hidden="true"></i>Datos personales</h5>
                    <div class="row g-3">
                        @foreach(['name' => 'Nombre(s)', 'cPrimerApellido' => 'Primer apellido', 'cSegundoApellido' => 'Segundo apellido', 'cTelefono' => 'Teléfono'] as $campo => $label)
                            <div class="col-md-6"><label for="{{ $campo }}" class="form-label">{{ $label }}</label><input id="{{ $campo }}" name="{{ $campo }}" type="{{ $campo === 'cTelefono' ? 'tel' : 'text' }}" class="form-control @error($campo) is-invalid @enderror" value="{{ old($campo, $user->$campo) }}" maxlength="{{ $campo === 'cTelefono' ? 20 : 100 }}" @if(in_array($campo, ['name', 'cPrimerApellido'])) required @endif>@error($campo)<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                        @endforeach
                    </div>
                </section>
                <section class="apartados-filtros">
                    <h5 class="perfil-seccion"><i class="fas fa-key me-2" aria-hidden="true"></i>Cambiar contraseña</h5>
                    <p class="text-muted small">Deja estos campos vacíos para conservar tu contraseña actual.</p>
                    <div class="row g-3">
                        @foreach(['current_password' => 'Contraseña actual', 'password' => 'Nueva contraseña', 'password_confirmation' => 'Confirmar nueva contraseña'] as $campo => $label)
                            <div class="{{ $campo === 'current_password' ? 'col-12' : 'col-md-6' }}"><label for="{{ $campo }}" class="form-label">{{ $label }}</label><input id="{{ $campo }}" name="{{ $campo }}" type="password" class="form-control @error($campo) is-invalid @enderror" autocomplete="{{ $campo === 'current_password' ? 'current-password' : 'new-password' }}" @if($campo !== 'current_password') minlength="8" @endif>@error($campo)<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                        @endforeach
                    </div>
                    <small class="text-muted d-block mt-2">La nueva contraseña debe tener al menos 8 caracteres.</small>
                </section>
            </div>
        </div>
        <div class="d-flex justify-content-end gap-2 mt-4"><a href="{{ route('dashboard') }}" class="apartado-icono perfil-volver"><i class="fas fa-arrow-left" aria-hidden="true"></i>Volver</a><button class="apartado-icono nuevo-apartado" type="submit"><i class="fas fa-save" aria-hidden="true"></i>Guardar cambios</button></div>
    </form>
</div>
@include('agencia.partials.avisos')
@endsection
@push('styles')
<link rel="stylesheet" href="{{ asset('css/agencia-grid.css') }}?v={{ filemtime(public_path('css/agencia-grid.css')) }}">
@endpush
@push('scripts')
<script>
document.getElementById('foto').addEventListener('change', function () {
    const file = this.files[0];
    if (!file) return;
    if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type) || file.size > 2 * 1024 * 1024) {
        this.value = '';
        Swal.fire({icon: 'error', title: 'Revisa la foto', text: 'Selecciona una imagen JPG, PNG o WebP de hasta 2 MB.', confirmButtonColor: '#536fe8'});
        return;
    }
    const preview = document.getElementById('fotoPerfilPreview');
    const url = URL.createObjectURL(file);
    preview.onload = () => URL.revokeObjectURL(url);
    preview.src = url;
    preview.hidden = false;
    document.getElementById('fotoPerfilIcono').hidden = true;
});
</script>
@endpush
