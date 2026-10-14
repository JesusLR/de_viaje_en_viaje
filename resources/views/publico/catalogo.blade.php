@extends('layouts.publico')
@section('titulo', 'Catálogo de servicios y paquetes')
@section('contenido')
<header class="catalogo-cabecera"><div class="container"><span class="landing-eyebrow">ENCUENTRA TU PRÓXIMA EXPERIENCIA</span><h1>Catálogo de servicios y paquetes</h1><p>Explora nuestros viajes, compara opciones y consulta con la agencia.</p></div></header>
<section class="container catalogo-contenido">
    <form method="GET" action="{{ route('catalogo.publico.index') }}" class="catalogo-filtros mb-4">
        <div class="row g-3 align-items-end">
            <div class="col-lg-5"><label for="buscar" class="form-label">¿Qué viaje tienes en mente?</label><div class="catalogo-busqueda"><i class="fas fa-search" aria-hidden="true"></i><input id="buscar" name="buscar" type="search" maxlength="150" value="{{ old('buscar', request('buscar')) }}" class="form-control" placeholder="Nombre, destino o experiencia"></div></div>
            <div class="col-md-6 col-lg-3"><label for="destino" class="form-label">Destino</label><select id="destino" name="destino" class="form-select"><option value="">Todos los destinos</option>@foreach($destinos as $destino)<option value="{{ $destino->iID }}" @selected(request('destino') == $destino->iID)>{{ $destino->cNombre }}</option>@endforeach</select></div>
            <div class="col-md-6 col-lg-3"><label for="categoria" class="form-label">Categoría</label><select id="categoria" name="categoria" class="form-select"><option value="">Todas las categorías</option>@foreach($categorias as $categoria)<option value="{{ $categoria->iID }}" @selected(request('categoria') == $categoria->iID)>{{ $categoria->cNombre }}</option>@endforeach</select></div>
            <div class="col-lg-1"><button type="submit" class="landing-button catalogo-filtrar w-100" title="Buscar servicios" aria-label="Buscar servicios"><i class="fas fa-search" aria-hidden="true"></i></button></div>
        </div>
        <div class="row g-3 pt-3 mt-3 border-top">
            <div class="col-md-6 col-xl-2"><label for="desde" class="form-label">Salida desde</label><input id="desde" name="desde" type="date" value="{{ request('desde') }}" class="form-control" data-fecha-opcional></div>
            <div class="col-md-6 col-xl-2"><label for="hasta" class="form-label">Salida hasta</label><input id="hasta" name="hasta" type="date" value="{{ request('hasta') }}" class="form-control" data-fecha-opcional></div>
            <div class="col-md-4 col-xl-2"><label for="moneda" class="form-label">Moneda</label><select id="moneda" name="moneda" class="form-select"><option value="">Todas</option>@foreach($monedas as $moneda)<option value="{{ $moneda }}" @selected(request('moneda') === $moneda)>{{ $moneda }}</option>@endforeach</select></div>
            <div class="col-md-4 col-xl-2"><label for="minimo" class="form-label">Precio mínimo</label><input id="minimo" name="minimo" type="number" min="0" step="0.01" value="{{ request('minimo') }}" class="form-control" placeholder="Desde"></div>
            <div class="col-md-4 col-xl-2"><label for="maximo" class="form-label">Precio máximo</label><input id="maximo" name="maximo" type="number" min="0" step="0.01" value="{{ request('maximo') }}" class="form-control" placeholder="Hasta"></div>
            <div class="col-xl-2 d-flex align-items-end"><div class="form-check mb-2"><input class="form-check-input" id="promocion" type="checkbox" name="promocion" value="1" @checked(request()->boolean('promocion'))><label class="form-check-label" for="promocion">Solo promociones</label></div></div>
        </div>
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mt-3">
            <small class="text-muted">Los filtros de precio utilizan el precio final con la promoción vigente. Selecciona una moneda para comparar importes.</small>
            <a class="catalogo-limpiar" href="{{ route('catalogo.publico.index') }}"><i class="fas fa-rotate-left me-1" aria-hidden="true"></i>Limpiar filtros</a>
        </div>
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mt-4 pt-3 border-top">
            <span class="catalogo-total">{{ number_format($paquetes->total()) }} {{ $paquetes->total() === 1 ? 'opción disponible' : 'opciones disponibles' }}</span>
            <div class="catalogo-orden"><label for="orden" class="form-label mb-0">Ordenar por</label><select id="orden" name="orden" class="form-select"><option value="proximos" @selected(request('orden', 'proximos') === 'proximos')>Próximas salidas</option><option value="recientes" @selected(request('orden') === 'recientes')>Más recientes</option><option value="precio_asc" @selected(request('orden') === 'precio_asc')>Menor precio</option><option value="precio_desc" @selected(request('orden') === 'precio_desc')>Mayor precio</option></select><button class="catalogo-aplicar" type="submit">Aplicar</button></div>
        </div>
    </form>
    <div class="row g-4">
        @forelse($paquetes as $paquete)
            <div class="col-md-6 col-xl-4">@include('publico.partials.paquete-card')</div>
        @empty
            <div class="col-12"><div class="landing-empty"><i class="fas fa-search mb-3" aria-hidden="true"></i><h3>No encontramos opciones con estos filtros</h3><p>Prueba otro destino, amplía las fechas o consulta con nuestro equipo.</p><a class="landing-button" href="{{ route('catalogo.publico.index') }}">Ver todo el catálogo</a></div></div>
        @endforelse
    </div>
    <div class="catalogo-paginacion mt-4">@include('agencia.partials.paginacion', ['paginador' => $paquetes, 'etiqueta' => 'servicios'])</div>
</section>
@endsection
