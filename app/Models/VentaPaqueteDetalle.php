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

    protected $casts = [
        'lUsaVigencia' => 'boolean',
        'dPrecioUnitario' => 'decimal:2', 'dSubtotal' => 'decimal:2',
        'dPrecioRegular' => 'decimal:2', 'dDescuentoUnitario' => 'decimal:2',
        'dValorDescuento' => 'decimal:2', 'dFechaInicio' => 'date', 'dFechaFin' => 'date',
    ];
}
