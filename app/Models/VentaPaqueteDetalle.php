<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VentaPaqueteDetalle extends Model
{
    protected $table = 'venta_paquete_detalles';

    protected $primaryKey = 'iID';

    protected $guarded = ['iID'];
    protected $attributes = ['lUsaVigencia' => true];

    public function servicio()
    {
        return $this->belongsTo(ServicioTuristico::class, 'iIDServicio', 'iID');
    }

    public function getTieneItinerarioAttribute(): bool
    {
        return !empty($this->itinerario) || !empty($this->cItinerarioPdf) || (bool) $this->servicio?->tiene_itinerario;
    }

    public function getRutaItinerarioPdfAttribute(): ?string
    {
        if (!empty($this->itinerario)) return null;
        return $this->cItinerarioPdf ?: ($this->servicio?->cTipoItinerario === 'PDF' ? $this->servicio->cItinerarioPdf : null);
    }

    protected $casts = [
        'iCantidadMenores' => 'integer',
        'dPrecioMenor' => 'decimal:2',
        'itinerario' => 'array',
        'lUsaVigencia' => 'boolean',
        'dPrecioUnitario' => 'decimal:2', 'dSubtotal' => 'decimal:2',
        'dPrecioRegular' => 'decimal:2', 'dDescuentoUnitario' => 'decimal:2',
        'dValorDescuento' => 'decimal:2', 'dFechaInicio' => 'date', 'dFechaFin' => 'date',
    ];
}
