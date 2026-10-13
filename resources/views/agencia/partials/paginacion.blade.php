@php
    $paginas = collect(range(max(1, $paginador->currentPage() - 2), min($paginador->lastPage(), $paginador->currentPage() + 2)))
        ->push(1, $paginador->lastPage())->unique()->sort()->values();
    $anterior = 0;
@endphp
<div class="agencia-paginacion px-4 py-3 border-top d-flex flex-wrap align-items-center justify-content-between gap-3">
    <small class="text-muted">Mostrando {{ $paginador->firstItem() ?? 0 }}–{{ $paginador->lastItem() ?? 0 }} de {{ $paginador->total() }} apartados</small>
    <nav aria-label="Paginación de apartados">
        <ul class="pagination pagination-sm mb-0">
            <li class="page-item {{ $paginador->onFirstPage() ? 'disabled' : '' }}">
                @if($paginador->onFirstPage())
                    <span class="page-link" aria-disabled="true">Anterior</span>
                @else
                    <a class="page-link" href="{{ $paginador->previousPageUrl() }}" rel="prev">Anterior</a>
                @endif
            </li>
            @foreach($paginas as $pagina)
                @if($anterior && $pagina > $anterior + 1)
                    <li class="page-item disabled"><span class="page-link">…</span></li>
                @endif
                <li class="page-item {{ $pagina === $paginador->currentPage() ? 'active' : '' }}">
                    @if($pagina === $paginador->currentPage())
                        <span class="page-link" aria-current="page">{{ $pagina }}</span>
                    @else
                        <a class="page-link" href="{{ $paginador->url($pagina) }}" aria-label="Ir a página {{ $pagina }}">{{ $pagina }}</a>
                    @endif
                </li>
                @php $anterior = $pagina; @endphp
            @endforeach
            <li class="page-item {{ $paginador->hasMorePages() ? '' : 'disabled' }}">
                @if($paginador->hasMorePages())
                    <a class="page-link" href="{{ $paginador->nextPageUrl() }}" rel="next">Siguiente</a>
                @else
                    <span class="page-link" aria-disabled="true">Siguiente</span>
                @endif
            </li>
        </ul>
    </nav>
</div>
