<?php

namespace App\Support;

use Illuminate\Support\Str;

class NombreDocumento
{
    public static function parte(?string $nombre, string $alternativo = 'servicio'): string
    {
        return substr(Str::slug($nombre ?? '', '_') ?: $alternativo, 0, 80);
    }
}
