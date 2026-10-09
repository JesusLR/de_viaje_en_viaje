<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Proveedor extends Model
{
    use HasFactory;

    protected $table = 'proveedores';
    protected $primaryKey = 'iID';

    protected $fillable = [
        'cNombre',
        'cDescripcion',
        'lActivo',
        'iIDUsuario',
    ];

    protected $casts = [
        'lActivo' => 'boolean',
    ];

    public function servicios(): HasMany
    {
        return $this->hasMany(ServicioTuristico::class, 'iIDProveedor', 'iID');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'iIDUsuario');
    }
}
