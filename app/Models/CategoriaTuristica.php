<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CategoriaTuristica extends Model
{
    use HasFactory;

    protected $table = 'categoria_turisticas';
    protected $primaryKey = 'iID';

    protected $fillable = [
        'cNombre',
        'cDescripcion',
        'cImagen',
        'lActivo',
        'iIDUsuario',
    ];

    protected $casts = [
        'lActivo' => 'boolean',
    ];

    /**
     * Usuario que registró la categoría.
     */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'iIDUsuario');
    }

    /**
     * Servicios turísticos asociados a esta categoría.
     */
    public function servicios(): HasMany
    {
        return $this->hasMany(ServicioTuristico::class, 'iIDCategoria', 'iID');
    }
}
