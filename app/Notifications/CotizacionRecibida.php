<?php

namespace App\Notifications;

use App\Models\Cotizacion;
use Illuminate\Notifications\Notification;

class CotizacionRecibida extends Notification
{
    public function __construct(public Cotizacion $cotizacion) {}

    public function via(object $notifiable): array { return ['database']; }

    public function toArray(object $notifiable): array
    {
        return [
            'titulo' => 'Nueva cotización web',
            'mensaje' => $this->cotizacion->folio.' · Nueva solicitud de viaje a la Riviera Maya.',
            'ruta' => 'cotizaciones.show',
            'parametros' => ['cotizacion' => $this->cotizacion->id],
            'permiso' => 'cotizaciones.ver',
        ];
    }
}
