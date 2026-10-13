<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PagoVenta extends Model
{
    protected $table = 'pagos_ventas';

    protected $primaryKey = 'iID';

    protected $guarded = ['iID'];

    protected $casts = ['dImporte' => 'decimal:2', 'dFechaPago' => 'date', 'anulado_at' => 'datetime'];

    public function venta()
    {
        return $this->belongsTo(VentaPaquete::class, 'iIDVenta', 'iID');
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'iIDUsuario');
    }
}
