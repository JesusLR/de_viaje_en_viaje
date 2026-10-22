@if($paquete->lPrecioMenorActivo)
    <div class="small mt-2 precio-menor">Precio para menor: <span class="fw-bold">{{ $paquete->dPrecioMenor > 0 ? number_format($paquete->dPrecioMenor, 2).' '.$paquete->cMoneda : 'Gratis' }}</span></div>
@endif
