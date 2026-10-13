@extends('layouts.app')
@section('titulo', 'Dashboard')
@section('contenido')
<div class="apartados-panel dashboard-panel">
    <div class="dashboard-tarjeta mb-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div class="d-flex align-items-center gap-3">
                <div class="dashboard-bienvenido-imagen"><i class="fas fa-user-shield" aria-hidden="true"></i></div>
                <div><h3 class="fw-bold mb-1">¡Bienvenido, {{ $user->name }}!</h3><p class="text-muted small mb-0">Has iniciado sesión correctamente en la plataforma.</p></div>
            </div>
            <div class="d-flex flex-wrap align-items-center gap-2">
                <span class="apartado-estado liquidado"><i class="fas fa-check-circle" aria-hidden="true"></i>Sesión activa</span>
                <span class="dashboard-periodo"><i class="fas fa-calendar-days me-2" aria-hidden="true"></i>{{ ucfirst($mes) }}</span>
            </div>
        </div>
    </div>
    <div class="row g-4">
        <div class="col-lg-6"><section class="dashboard-tarjeta h-100">
            <div class="d-flex align-items-center justify-content-between mb-4"><h5 class="dashboard-titulo"><i class="fas fa-wallet" aria-hidden="true"></i>Finanzas del mes</h5><a class="apartado-icono" href="{{ route('agencia.finanzas.index', ['desde' => $fechaDesde, 'hasta' => $fechaHasta]) }}" title="Ver finanzas del mes" aria-label="Ver finanzas del mes"><i class="fas fa-arrow-up-right-from-square" aria-hidden="true"></i></a></div>
            @foreach($finanzas as $moneda => $importes)
            <div class="dashboard-finanzas {{ !$loop->first ? 'mt-3 pt-3 border-top' : '' }}"><span class="dashboard-moneda">{{ $moneda }}</span><div class="row g-3 mt-1"><div class="col-sm-4"><small>Ingresos</small><strong class="text-success">{{ number_format($importes['ingresos'], 2) }}</strong></div><div class="col-sm-4"><small>Egresos</small><strong class="text-danger">{{ number_format($importes['egresos'], 2) }}</strong></div><div class="col-sm-4"><small>Balance</small><strong class="{{ $importes['balance'] < 0 ? 'text-danger' : 'dashboard-balance' }}">{{ number_format($importes['balance'], 2) }}</strong></div></div></div>
            @endforeach
            <div class="mt-4 pt-3 border-top">
                @foreach($finanzas as $moneda => $importes)
                <div class="dashboard-origen {{ !$loop->first ? 'mt-3 pt-3 border-top' : '' }}">
                    <span class="dashboard-moneda mb-2">Origen de ingresos · {{ $moneda }}</span>
                    <div class="d-flex justify-content-between gap-3 mb-2"><span><i class="fas fa-receipt me-2" aria-hidden="true"></i>Ventas</span><strong>{{ number_format($importes['ventas'], 2) }}</strong></div>
                    <div class="d-flex justify-content-between gap-3 mb-2"><span><i class="fas fa-money-bill-wave me-2" aria-hidden="true"></i>Abonos de apartados</span><strong class="text-success">{{ number_format($importes['abonos'], 2) }}</strong></div>
                    <div class="d-flex justify-content-between gap-3"><span><i class="fas fa-pen me-2" aria-hidden="true"></i>Ingresos manuales</span><strong>{{ number_format($importes['manuales'], 2) }}</strong></div>
                </div>
                @endforeach
            </div>
            <p class="dashboard-nota mb-0 mt-4">Del {{ \Carbon\Carbon::parse($fechaDesde)->format('d/m/Y') }} al {{ \Carbon\Carbon::parse($fechaHasta)->format('d/m/Y') }}</p>
        </section></div>
        <div class="col-md-6 col-lg-3"><section class="dashboard-tarjeta h-100">
            <div class="d-flex align-items-center justify-content-between mb-4"><span class="dashboard-icono abiertos"><i class="fas fa-clipboard-list" aria-hidden="true"></i></span><a class="apartado-icono" href="{{ route('agencia.apartados.index', ['estado' => 'Abierto', 'desde' => $desdeApartados, 'hasta' => today()->toDateString()]) }}" title="Ver apartados abiertos" aria-label="Ver apartados abiertos"><i class="fas fa-arrow-up-right-from-square" aria-hidden="true"></i></a></div>
            <h5 class="dashboard-titulo">Apartados abiertos</h5><div class="dashboard-cantidad">{{ number_format($apartadosAbiertos) }}</div><p class="dashboard-nota mb-0">Apartados activos con saldo pendiente, de cualquier mes.</p>
        </section></div>
        <div class="col-md-6 col-lg-3"><section class="dashboard-tarjeta h-100">
            <div class="d-flex align-items-center justify-content-between mb-4"><span class="dashboard-icono servicios"><i class="fas fa-box-open" aria-hidden="true"></i></span><a class="apartado-icono" href="{{ route('servicios.paquetes.index') }}" title="Ver servicios y paquetes" aria-label="Ver servicios y paquetes"><i class="fas fa-arrow-up-right-from-square" aria-hidden="true"></i></a></div>
            <h5 class="dashboard-titulo">Servicios del mes</h5><div class="dashboard-cantidad">{{ number_format($serviciosMes) }}</div><p class="dashboard-nota mb-2">Servicios dados de alta en {{ $mes }}.</p><span class="apartado-estado liquidado"><span class="estado-punto" aria-hidden="true"></span>{{ $serviciosActivosMes }} habilitados</span>
        </section></div>
    </div>
</div>
@endsection
@push('styles')
<link href="{{ asset('css/agencia-grid.css') }}" rel="stylesheet">
<style>
.dashboard-bienvenido-imagen{width:64px;height:64px;flex-shrink:0;border-radius:50%;display:grid;place-items:center;background:#edf1ff;color:#536fe8;font-size:30px}
.dashboard-origen{font-size:12px;color:#64748b}.dashboard-origen strong{font-variant-numeric:tabular-nums;color:#25324b}
.dashboard-tarjeta{padding:24px;background:#fff;border:1px solid #e8edf4;border-radius:18px;box-shadow:0 4px 24px rgba(30,41,59,.035)}.dashboard-periodo{background:#edf1ff;color:#6274b9;padding:9px 14px;border-radius:10px;font-size:12px;font-weight:600}.dashboard-titulo{display:flex;align-items:center;gap:10px;margin:0;font-size:14px;font-weight:650;color:#25324b}.dashboard-titulo>i{color:#536fe8}.dashboard-finanzas small{display:block;color:#8793a5;font-size:11px;margin-bottom:7px}.dashboard-finanzas strong{display:block;font-size:19px;font-variant-numeric:tabular-nums;overflow-wrap:anywhere}.dashboard-moneda{display:inline-block;background:#f3f5fa;color:#79869b;border-radius:7px;padding:4px 8px;font-size:10px;font-weight:650}.dashboard-balance{color:#536fe8}.dashboard-nota{color:#8793a5;font-size:11px;line-height:1.7}.dashboard-icono{width:42px;height:42px;border-radius:13px;display:grid;place-items:center;font-size:17px}.dashboard-icono.abiertos{color:#b37a19;background:#fff6e5}.dashboard-icono.servicios{color:#6274b9;background:#edf1ff}.dashboard-cantidad{font-size:42px;font-weight:650;color:#25324b;line-height:1.5;margin:8px 0;font-variant-numeric:tabular-nums}@media(max-width:575px){.dashboard-tarjeta{padding:20px}.dashboard-finanzas strong{font-size:18px}}
</style>
@endpush
