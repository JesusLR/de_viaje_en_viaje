<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VariableSistema extends Model
{
    protected $table = 'variables_sistema';
    protected $primaryKey = 'clave';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['clave', 'valor', 'actualizada_por'];
    protected $casts = ['valor' => 'integer'];

    public static function envioAutomaticoVentaWhatsApp(): bool
    {
        return (static::find('whatsapp_venta_automatica')?->valor ?? 1) === 1;
    }

    public static function diasSinAbonos(): ?int
    {
        return static::find('dias_sin_abonos')?->valor;
    }
}
