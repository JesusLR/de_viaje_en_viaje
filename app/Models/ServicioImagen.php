<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServicioImagen extends Model
{
    use HasFactory;

    protected $table = 'servicio_imagenes';
    protected $primaryKey = 'iID';

    protected $fillable = [
        'iIDServicio',
        'cRutaImagen',
        'iOrden',
    ];

    public function servicio(): BelongsTo
    {
        return $this->belongsTo(ServicioTuristico::class, 'iIDServicio', 'iID');
    }
}
