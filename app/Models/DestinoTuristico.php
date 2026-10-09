<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DestinoTuristico extends Model
{
    use HasFactory;

    protected $table = 'destino_turisticos';
    protected $primaryKey = 'iID';

    protected $fillable = [
        'cNombre',
        'cPais',
        'cEstado',
        'cCiudad',
        'cDescripcion',
        'cImagen',
        'lActivo',
        'iIDUsuario',
    ];

    protected $casts = [
        'lActivo' => 'boolean',
    ];

    /**
     * Usuario que registró el destino.
     */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'iIDUsuario');
    }

    /**
     * Servicios asociados a este destino.
     */
    public function servicios(): HasMany
    {
        return $this->hasMany(ServicioTuristico::class, 'iIDDestino', 'iID');
    }

    /**
     * Accessor para la ubicación formateada.
     */
    public function getUbicacionCompletaAttribute(): string
    {
        $aUbicacion = array_filter([$this->cCiudad, $this->cEstado, $this->cPais]);
        return count($aUbicacion) > 0 ? implode(', ', $aUbicacion) : $this->cNombre;
    }
}
