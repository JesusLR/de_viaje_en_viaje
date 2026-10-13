<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MovimientoFinanciero extends Model
{
    protected $table = 'movimientos_financieros';

    protected $primaryKey = 'iID';

    protected $guarded = ['iID'];

    protected $casts = ['dImporte' => 'decimal:2', 'dFecha' => 'date', 'anulado_at' => 'datetime'];

    public function usuario()
    {
        return $this->belongsTo(User::class, 'iIDUsuario');
    }
}
