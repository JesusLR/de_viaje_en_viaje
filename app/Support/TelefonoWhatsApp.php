<?php

namespace App\Support;

use Illuminate\Validation\ValidationException;

class TelefonoWhatsApp
{
    /** Número internacional en dígitos; los números mexicanos locales reciben 52 una sola vez. */
    public static function normalizar(string $telefono): string
    {
        $telefono = trim($telefono);
        if (!preg_match('/\A\+?[0-9\s().-]+\z/u', $telefono)) self::invalido();
        $internacional = str_starts_with($telefono, '+') || str_starts_with($telefono, '00');
        $numero = preg_replace('/\D/', '', $telefono);
        if (str_starts_with($numero, '00')) $numero = substr($numero, 2);
        // Compatibilidad con números móviles mexicanos almacenados con el prefijo antiguo +521.
        if (strlen($numero) === 13 && str_starts_with($numero, '521')) $numero = '52'.substr($numero, 3);
        if (!$internacional && strlen($numero) === 10) $numero = '52'.$numero;
        elseif (!$internacional && !(strlen($numero) === 12 && str_starts_with($numero, '52'))) self::invalido();
        if (!preg_match('/\A[1-9][0-9]{7,14}\z/', $numero)) self::invalido();
        if (str_starts_with($numero, '52') && !preg_match('/\A52[1-9][0-9]{9}\z/', $numero)) self::invalido();

        return $numero;
    }

    private static function invalido(): never
    {
        throw ValidationException::withMessages(['telefono' => 'Ingresa un número de México de 10 dígitos, con o sin +52. Para otro país, incluye + y su código de país.']);
    }
}
