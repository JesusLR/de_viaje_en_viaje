<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CotizacionServicio extends Model
{
    protected $table = 'cotizaciones_servicios';
    protected $guarded = ['id'];
    protected $casts = ['partidas' => 'array', 'total' => 'decimal:2'];
    public function usuario() { return $this->belongsTo(User::class); }
    public function getFolioAttribute(): string { return 'COT-S-'.str_pad($this->id, 6, '0', STR_PAD_LEFT); }

    public function getDescuentoTotalAttribute(): float
    {
        return collect($this->partidas)->sum(fn ($partida) => (int) round(($partida['descuento'] ?? 0) * 100) * $partida['adultos']) / 100;
    }
}
