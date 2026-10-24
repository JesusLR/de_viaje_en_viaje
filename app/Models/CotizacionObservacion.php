<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CotizacionObservacion extends Model
{
    protected $table = 'cotizacion_observaciones';
    protected $guarded = ['id'];
    public function usuario() { return $this->belongsTo(User::class); }
}
