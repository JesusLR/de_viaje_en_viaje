<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

class RecordatorioAbono extends Notification
{
    public function __construct(private object $apartado, private int $dias) {}
    public function via(object $notifiable): array { return ['database']; }
    public function toArray(object $notifiable): array
    {
        $folio = 'VTA-'.str_pad($this->apartado->iID, 6, '0', STR_PAD_LEFT);
        return [
            'titulo' => 'Apartado sin abonos recientes',
            'mensaje' => $folio.' · '.$this->apartado->cliente.' lleva '.$this->dias.' días sin abonar y tiene saldo pendiente.',
            'ruta' => 'agencia.ventas.show', 'parametros' => ['venta' => $this->apartado->iID, 'seccion' => 'detalle'],
            'permiso' => 'apartados.recordatorios',
            'apartado_usuario_id' => (int) ($this->apartado->iIDUsuario ?? 0),
            'venta_id' => $this->apartado->iID,
            'ultimo_abono' => $this->apartado->ultima_fecha,
        ];
    }
}
