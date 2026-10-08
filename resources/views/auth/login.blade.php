@extends('layouts.auth')

@section('titulo', 'Iniciar Sesión')

@section('contenido')
<div class="auth-card">
    <div class="auth-header">
        <div class="brand-icon p-0 border border-2 border-white shadow-sm overflow-hidden">
            <img src="{{ asset('img/logo.jpg') }}" alt="Logo" style="width: 100%; height: 100%; object-fit: cover;">
        </div>
        <h4 class="fw-bold mb-1">Bienvenido de nuevo</h4>
        <p class="text-white-50 small mb-0">Ingresa tus credenciales para acceder</p>
    </div>

    <div class="auth-body">
        <!-- Alerta dinámicas de error (para AJAX y SSR) -->
        <div id="alertContainer">
            @if ($errors->any())
                <div class="alert alert-danger alert-dismissible fade show rounded-3 small mb-4" role="alert">
                    <i class="fas fa-exclamation-circle me-1"></i>
                    <strong>Error de Autenticación:</strong>
                    <ul class="mb-0 mt-1 ps-3">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif
        </div>

        <form id="formLogin" action="{{ route('login') }}" method="POST" novalidate>
            @csrf

            <!-- Email -->
            <div class="mb-3">
                <label for="email" class="form-label fw-semibold text-secondary small">Correo Electrónico</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                    <input type="email" 
                           class="form-control @error('email') is-invalid @enderror" 
                           id="email" 
                           name="email" 
                           value="{{ old('email') }}" 
                           placeholder="nombre@ejemplo.com" 
                           required 
                           autofocus>
                </div>
                <div class="invalid-feedback id-error-email">
                    @error('email') {{ $message }} @enderror
                </div>
            </div>

            <!-- Contraseña -->
            <div class="mb-3">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <label for="password" class="form-label fw-semibold text-secondary small mb-0">Contraseña</label>
                </div>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-key"></i></span>
                    <input type="password" 
                           class="form-control @error('password') is-invalid @enderror" 
                           id="password" 
                           name="password" 
                           placeholder="••••••••" 
                           required>
                    <button class="btn btn-outline-secondary input-group-text px-3" type="button" id="togglePassword">
                        <i class="fas fa-eye" id="eyeIcon"></i>
                    </button>
                </div>
                <div class="invalid-feedback id-error-password">
                    @error('password') {{ $message }} @enderror
                </div>
            </div>

            <!-- Recordarme -->
            <div class="form-check mb-4">
                <input class="form-check-input" type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>
                <label class="form-check-label small text-secondary" for="remember">
                    Recordar mi sesión en este equipo
                </label>
            </div>

            <!-- Botón Ingresar -->
            <button type="submit" class="btn btn-primary-custom" id="btnLogin">
                <span id="btnText"><i class="fas fa-sign-in-alt me-2"></i>Iniciar Sesión</span>
                <span id="btnSpinner" class="d-none">
                    <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                    Autenticando...
                </span>
            </button>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/auth/login.js') }}"></script>
@endpush
