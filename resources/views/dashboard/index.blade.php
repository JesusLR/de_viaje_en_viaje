@extends('layouts.app')
@section('titulo', 'Dashboard')
@section('contenido')
<div class="apartados-panel dashboard-panel">
    <section class="dashboard-bienvenida mb-4">
        <div class="dashboard-bienvenida-contenido">
            <span class="dashboard-etiqueta">TU AGENCIA, DE UN VISTAZO</span>
            <h3>¡Bienvenido, {{ $user->name }}!</h3>
            <p>Consulta tus ingresos y continúa con las operaciones de tu agencia.</p>
            <a href="{{ route('agencia.vender.index') }}" class="apartado-icono nuevo-apartado"><i class="fas fa-plus" aria-hidden="true"></i> Nueva operación</a>
        </div>
        <div class="dashboard-bienvenido-imagen" aria-hidden="true"><i class="fas fa-user-shield"></i><span><i class="fas fa-plane"></i></span></div>
    </section>

    <div class="dashboard-seccion mb-3">
        <div><h4>Resumen de la agencia</h4><p>{{ ucfirst($mes) }}</p></div>
        <span class="dashboard-periodo"><i class="fas fa-calendar-alt me-2" aria-hidden="true"></i>{{ \Carbon\Carbon::parse($fechaDesde)->format('d/m/Y') }} — {{ \Carbon\Carbon::parse($fechaHasta)->format('d/m/Y') }}</span>
    </div>
    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <a class="dashboard-indicador" href="{{ route('agencia.apartados.index', ['estado' => 'Abierto', 'desde' => $desdeApartados, 'hasta' => today()->toDateString()]) }}">
                <span class="dashboard-icono abiertos"><i class="fas fa-clipboard-list" aria-hidden="true"></i></span>
                <div class="flex-grow-1"><span class="dashboard-label">Apartados abiertos</span><strong class="dashboard-cantidad">{{ number_format($apartadosAbiertos) }}</strong><small>Con saldo pendiente · Todos los meses</small></div>
                <i class="fas fa-chevron-right dashboard-flecha" aria-hidden="true"></i>
            </a>
        </div>
        <div class="col-md-6">
            <a class="dashboard-indicador" href="{{ route('servicios.paquetes.index') }}">
                <span class="dashboard-icono servicios"><i class="fas fa-box-open" aria-hidden="true"></i></span>
                <div class="flex-grow-1"><span class="dashboard-label">Servicios del mes</span><strong class="dashboard-cantidad">{{ number_format($serviciosMes) }}</strong><small>{{ $serviciosActivosMes }} habilitados · Registrados en {{ $mes }}</small></div>
                <i class="fas fa-chevron-right dashboard-flecha" aria-hidden="true"></i>
            </a>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-xl-8">
            <section class="dashboard-tarjeta h-100">
                <div class="dashboard-seccion mb-4">
                    <div><h4>Finanzas del mes</h4><p>Ingresos, egresos y balance por moneda</p></div>
                    <a class="apartado-icono" href="{{ route('agencia.finanzas.index', ['desde' => $fechaDesde, 'hasta' => $fechaHasta]) }}" title="Ver finanzas del mes" aria-label="Ver finanzas del mes"><i class="fas fa-arrow-right" aria-hidden="true"></i></a>
                </div>
                @foreach($finanzas as $moneda => $importes)
                    <div class="{{ !$loop->first ? 'mt-4 pt-4 border-top' : '' }}">
                        <span class="dashboard-moneda mb-3">{{ $moneda }}</span>
                        <div class="row g-3">
                            @foreach(['ingresos' => ['Ingresos', 'fa-arrow-down', 'ingreso'], 'egresos' => ['Egresos', 'fa-arrow-up', 'egreso'], 'balance' => ['Balance', 'fa-wallet', 'balance']] as $campo => $meta)
                            <div class="col-sm-4">
                                <div class="dashboard-importe {{ $meta[2] }}">
                                    <span><i class="fas {{ $meta[1] }} me-2" aria-hidden="true"></i>{{ $meta[0] }}</span>
                                    <strong class="{{ $importes[$campo] < 0 ? 'text-danger' : '' }}">{{ number_format($importes[$campo], 2) }}</strong>
                                    <small>{{ $moneda }}</small>
                                </div>
                            </div>
                            @endforeach
                        </div>
                        <div class="dashboard-origen mt-4">
                            <h5>Origen de los ingresos</h5>
                            @foreach(['ventas' => ['Ventas', 'fa-receipt'], 'abonos' => ['Abonos de apartados', 'fa-money-bill-wave'], 'manuales' => ['Ingresos manuales', 'fa-pen']] as $campo => $meta)
                                <div><span><i class="fas {{ $meta[1] }}" aria-hidden="true"></i>{{ $meta[0] }}</span><strong>{{ number_format($importes[$campo], 2) }} <small>{{ $moneda }}</small></strong></div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </section>
        </div>
        <div class="col-xl-4">
            <section class="dashboard-tarjeta h-100">
                <div class="dashboard-seccion mb-4"><div><h4>Accesos rápidos</h4><p>Continúa con tu siguiente operación</p></div></div>
                @foreach([
                    ['agencia.vender.index', 'fa-shopping-bag', 'Vender paquetes', 'Crea una venta o un nuevo apartado'],
                    ['agencia.apartados.index', 'fa-clipboard-list', 'Administrar apartados', 'Registra abonos y consulta saldos'],
                    ['agencia.ventas.index', 'fa-receipt', 'Consultar ventas', 'Revisa compras saldadas y sus PDF'],
                    ['catalogos.clientes.index', 'fa-address-book', 'Clientes', 'Consulta y registra tus clientes']
                ] as $acceso)
                    <a class="dashboard-acceso" href="{{ route($acceso[0]) }}"><span class="dashboard-acceso-icono"><i class="fas {{ $acceso[1] }}" aria-hidden="true"></i></span><span><strong>{{ $acceso[2] }}</strong><small>{{ $acceso[3] }}</small></span><i class="fas fa-chevron-right dashboard-flecha" aria-hidden="true"></i></a>
                @endforeach
            </section>
        </div>
    </div>
</div>
@endsection
@push('styles')
<link href="{{ asset('css/agencia-grid.css') }}?v={{ filemtime(public_path('css/agencia-grid.css')) }}" rel="stylesheet">
<link href="{{ asset('css/dashboard-module.css') }}?v={{ filemtime(public_path('css/dashboard-module.css')) }}" rel="stylesheet">
@endpush
