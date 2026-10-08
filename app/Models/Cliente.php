<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cliente extends Model
{
    use HasFactory;

    protected $table = 'clientes';
    protected $primaryKey = 'iID';

    protected $fillable = [
        'cNombre',
        'cPrimerApellido',
        'cSegundoApellido',
        'cTelefono',
        'cEmail',
        'cRFC',
        'cDireccion',
        'lActivo',
    ];

    protected $casts = [
        'lActivo' => 'boolean',
    ];

    /**
     * Accessor para obtener el nombre completo del cliente.
     */
    public function getNombreCompletoAttribute(): string
    {
        return trim("{$this->cNombre} {$this->cPrimerApellido} {$this->cSegundoApellido}");
    }
}
