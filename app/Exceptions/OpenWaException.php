<?php

namespace App\Exceptions;

class OpenWaException extends \RuntimeException
{
    public function __construct(string $mensaje, public readonly int $status = 503)
    {
        parent::__construct($mensaje);
    }
}
