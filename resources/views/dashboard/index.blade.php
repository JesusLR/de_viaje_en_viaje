@extends('layouts.app')
@section('titulo', 'Dashboard')
@section('contenido')
<div class="apartados-panel dashboard-panel">
    <section class="dashboard-bienvenida mb-4">
        <div class="dashboard-bienvenida-contenido">
            <span class="dashboard-etiqueta">TU AGENCIA, DE UN VISTAZO</span>
            <h3>¡Bienvenido, {{ $user->name }}!</h3>
            <p>Sistema de gestion interna.</p>
            <a data-permission="vender.ver" href="{{ route('agencia.vender.index') }}" class="apartado-icono nuevo-apartado"><i class="fas fa-plus" aria-hidden="true"></i> Nueva operación</a>
        </div>
        <div class="dashboard-bienvenido-imagen" aria-hidden="true"><i class="fas fa-user-shield"></i><span><i class="fas fa-plane"></i></span></div>
    </section>

    @canany(array_keys(config('permissions.dashboard.actions')))
    <div class="dashboard-seccion mb-3">
        <div><h4>Resumen de la agencia</h4><p>{{ ucfirst($mes) }}</p></div>
        <span class="dashboard-periodo"><i class="fas fa-calendar-alt me-2" aria-hidden="true"></i>Del {{ \Carbon\Carbon::parse($fechaDesde)->format('d/m/Y') }} al {{ \Carbon\Carbon::parse($fechaHasta)->format('d/m/Y') }}</span>
    </div>
    @endcanany

    <div class="row g-4 mb-4">
        @can('dashboard.finanzas')
<div class="col-xl-8" data-permission="dashboard.finanzas">
            <section class="dashboard-tarjeta h-100">
                <div class="dashboard-seccion mb-4">
                    <div><h4>Finanzas del mes</h4><p>Ingresos, egresos y balance por moneda</p></div>
                    <a data-permission="finanzas.ver" class="apartado-icono" href="{{ route('agencia.finanzas.index', ['desde' => $fechaDesde, 'hasta' => $fechaHasta]) }}" title="Ver finanzas del mes" aria-label="Ver finanzas del mes"><i class="fas fa-arrow-right" aria-hidden="true"></i></a>
                </div>
                @foreach($finanzas as $moneda => $importes)
                    <div class="{{ !$loop->first ? 'mt-4 pt-4 border-top' : '' }}">
                        <span class="dashboard-moneda mb-3">{{ $moneda }}</span>
                        <div class="row g-3">
                            @foreach(['ingresos' => ['Ingresos', 'fa-arrow-down', 'ingreso'], 'egresos' => ['Egresos', 'fa-arrow-up', 'egreso'], 'balance' => ['Balance', 'fa-wallet', 'balance']] as $campo => $meta)
                            @if(($campo === 'ingresos' && $user->hasPermission('dashboard.ingresos')) || ($campo === 'egresos' && $user->hasPermission('dashboard.egresos')) || ($campo === 'balance' && $user->hasPermission('dashboard.ingresos') && $user->hasPermission('dashboard.egresos')))
                            <div class="col-sm-4" data-permission="{{ $campo === 'ingresos' ? 'dashboard.ingresos' : ($campo === 'egresos' ? 'dashboard.egresos' : 'dashboard.balance') }}">
                                <div class="dashboard-importe {{ $meta[2] }}">
                                    <span><i class="fas {{ $meta[1] }} me-2" aria-hidden="true"></i>{{ $meta[0] }}</span>
                                    <strong class="{{ $importes[$campo] < 0 ? 'text-danger' : '' }}">{{ number_format($importes[$campo], 2) }}</strong>
                                    <small>{{ $moneda }}</small>
                                </div>
                            </div>
                            @endif
                            @endforeach
                        </div>
                        @can('dashboard.origen')
<div class="dashboard-origen mt-4" data-permission="dashboard.origen">
                            <h5>Origen de los ingresos</h5>
                            @foreach(['ventas' => ['Ventas', 'fa-receipt'], 'abonos' => ['Abonos de apartados', 'fa-money-bill-wave'], 'manuales' => ['Ingresos manuales', 'fa-pen']] as $campo => $meta)
                                <div><span><i class="fas {{ $meta[1] }}" aria-hidden="true"></i>{{ $meta[0] }}</span><strong>{{ number_format($importes[$campo], 2) }} <small>{{ $moneda }}</small></strong></div>
                            @endforeach
                        </div>
@endcan
                    </div>
                @endforeach
            </section>
        </div>
@endcan
        @can('dashboard.accesos')
<div class="col-xl-4">
            <section class="dashboard-tarjeta h-100">
                <div class="dashboard-seccion mb-4"><div><h4>Accesos rápidos</h4><p>Continúa con tu siguiente operación</p></div></div>
                @foreach([
                    ['agencia.vender.index', 'fa-shopping-bag', 'Vender paquetes', 'Crea una venta o un nuevo apartado'],
                    ['agencia.apartados.index', 'fa-clipboard-list', 'Administrar apartados', 'Registra abonos y consulta saldos'],
                    ['agencia.ventas.index', 'fa-receipt', 'Consultar ventas', 'Revisa compras saldadas y sus PDF'],
                    ['catalogos.clientes.index', 'fa-address-book', 'Clientes', 'Consulta y registra tus clientes']
                ] as $acceso)
                    <a class="dashboard-acceso" data-permission="{{ ['agencia.vender.index' => 'vender.ver', 'agencia.apartados.index' => 'apartados.ver', 'agencia.ventas.index' => 'ventas.ver', 'catalogos.clientes.index' => 'clientes.ver'][$acceso[0]] }}" href="{{ route($acceso[0]) }}"><span class="dashboard-acceso-icono"><i class="fas {{ $acceso[1] }}" aria-hidden="true"></i></span><span><strong>{{ $acceso[2] }}</strong><small>{{ $acceso[3] }}</small></span><i class="fas fa-chevron-right dashboard-flecha" aria-hidden="true"></i></a>
                @endforeach
            </section>
        </div>
@endcan
    </div>




    <div class="row g-3 mb-4">
        @can('dashboard.apartados')
<div class="col-md-6">
            <a data-permission="dashboard.apartados" class="dashboard-indicador" href="{{ $user->hasPermission('apartados.ver') ? route('agencia.apartados.index', ['estado' => 'Abierto', 'desde' => $desdeApartados, 'hasta' => today()->toDateString()]) : '#' }}">
                <span class="dashboard-icono abiertos"><i class="fas fa-clipboard-list" aria-hidden="true"></i></span>
                <div class="flex-grow-1"><span class="dashboard-label">Apartados abiertos</span><strong class="dashboard-cantidad">{{ number_format($apartadosAbiertos) }}</strong><small>Con saldo pendiente · Todos los meses</small></div>
                <i class="fas fa-chevron-right dashboard-flecha" aria-hidden="true"></i>
            </a>
        </div>
@endcan
        @can('dashboard.servicios')
<div class="col-md-6">
            <a data-permission="dashboard.servicios" class="dashboard-indicador" href="{{ $user->hasPermission('servicios.ver') ? route('servicios.paquetes.index') : '#' }}">
                <span class="dashboard-icono servicios"><i class="fas fa-box-open" aria-hidden="true"></i></span>
                <div class="flex-grow-1"><span class="dashboard-label">Servicios activos</span><strong class="dashboard-cantidad">{{ number_format($serviciosActivos) }}</strong><small>Todos los servicios abiertos y vigentes</small></div>
                <i class="fas fa-chevron-right dashboard-flecha" aria-hidden="true"></i>
            </a>
        </div>
@endcan
    </div>

    @can('dashboard.viajes')
<section class="dashboard-tarjeta mb-4" data-permission="dashboard.viajes">
        <div class="dashboard-seccion mb-3">
            <div><h4><i class="fas fa-plane-departure me-2" aria-hidden="true"></i>Próximos viajes</h4><p>Las 5 salidas más cercanas, a partir de hoy</p></div>
            <a data-permission="servicios.ver" class="apartado-icono" href="{{ route('servicios.paquetes.index') }}" title="Ver servicios y paquetes" aria-label="Ver servicios y paquetes"><i class="fas fa-arrow-right" aria-hidden="true"></i></a>
        </div>
        <div class="dashboard-viajes">
            @forelse($proximosViajes as $viaje)
                <div class="dashboard-viaje">
                    <div class="dashboard-fecha-salida"><strong>{{ $viaje->dFechaInicioViaje->format('d') }}</strong><span>{{ $viaje->dFechaInicioViaje->locale('es')->translatedFormat('M Y') }}</span></div>
                    <div class="dashboard-viaje-nombre"><strong>{{ $viaje->cNombre }}</strong><small>{{ $viaje->destino?->cNombre ?? $viaje->cCodigo }}</small></div>
                    <div class="dashboard-viaje-fechas"><span><i class="fas fa-calendar-alt me-1" aria-hidden="true"></i>{{ $viaje->dFechaInicioViaje->format('d/m/Y') }} — {{ $viaje->dFechaFinViaje->format('d/m/Y') }}</span><small>Salida y regreso</small></div>
                    @php($dias = (int) today()->diffInDays($viaje->dFechaInicioViaje))
                    <span class="apartado-estado {{ $dias <= 7 ? 'abierto' : 'liquidado' }}">{{ $dias === 0 ? 'Sale hoy' : ($dias === 1 ? 'Sale mañana' : 'En '.$dias.' días') }}</span>
                </div>
            @empty
                <div class="apartados-vacio"><i class="fas fa-calendar-check mb-3" aria-hidden="true"></i><p class="text-muted small mb-0">No hay viajes activos con próximas fechas de salida.</p></div>
            @endforelse
        </div>
    </section>
@endcan


</div>
@endsection
@push('styles')
<link href="{{ asset('css/agencia-grid.css') }}?v={{ filemtime(public_path('css/agencia-grid.css')) }}" rel="stylesheet">
<link href="{{ asset('css/dashboard-module.css') }}?v={{ filemtime(public_path('css/dashboard-module.css')) }}" rel="stylesheet">
@endpush
