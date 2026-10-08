@extends('layouts.app')

@section('titulo', 'Panel Principal')

@section('contenido')
<div class="row mb-4">
    <div class="col-12">
        <div class="card border-0 shadow-sm rounded-4 bg-white p-4">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="bg-primary bg-opacity-10 text-primary rounded-circle p-3 d-flex align-items-center justify-content-center" style="width: 64px; height: 64px;">
                        <i class="fas fa-user-shield fs-2"></i>
                    </div>
                    <div>
                        <h3 class="fw-bold mb-1">¡Bienvenido, {{ $user->name }}!</h3>
                        <p class="text-muted mb-0">Has iniciado sesión correctamente en la plataforma.</p>
                    </div>
                </div>
                <div>
                    <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2 rounded-pill fs-6">
                        <i class="fas fa-check-circle me-1"></i> Sesión Activa
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Card Info 1 -->
    <div class="col-md-4">
        <div class="card border-0 shadow-sm rounded-4 p-3 h-100">
            <div class="card-body">
                <div class="text-secondary mb-2"><i class="fas fa-envelope me-1"></i> Correo Electrónico</div>
                <h5 class="fw-semibold mb-0">{{ $user->email }}</h5>
            </div>
        </div>
    </div>

    <!-- Card Info 2 -->
    <div class="col-md-4">
        <div class="card border-0 shadow-sm rounded-4 p-3 h-100">
            <div class="card-body">
                <div class="text-secondary mb-2"><i class="fas fa-id-badge me-1"></i> ID de Usuario</div>
                <h5 class="fw-semibold mb-0">#{{ $user->id }}</h5>
            </div>
        </div>
    </div>

    <!-- Card Info 3 -->
    <div class="col-md-4">
        <div class="card border-0 shadow-sm rounded-4 p-3 h-100">
            <div class="card-body">
                <div class="text-secondary mb-2"><i class="fas fa-user-tag me-1"></i> Estado Spatie Roles</div>
                <h5 class="fw-semibold text-primary mb-0">Preparado (Instalar Spatie)</h5>
            </div>
        </div>
    </div>
</div>
@endsection
