<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cotizacion extends Model
{
    protected $table = 'cotizaciones';
    protected $guarded = ['id'];
    protected $casts = ['edades_menores' => 'array', 'presupuesto' => 'decimal:2', 'atendida_at' => 'datetime'];

    public const ESTADOS = ['NUEVA' => 'Nueva', 'EN_ATENCION' => 'En atención', 'CANCELADO' => 'Cancelado', 'ATENDIDO' => 'Atendido'];

    public function responsable() { return $this->belongsTo(User::class, 'atendida_por'); }
    public function observaciones() { return $this->hasMany(CotizacionObservacion::class)->orderBy('id'); }
    public function getFolioAttribute(): string { return 'COT-'.str_pad($this->id, 6, '0', STR_PAD_LEFT); }
}
