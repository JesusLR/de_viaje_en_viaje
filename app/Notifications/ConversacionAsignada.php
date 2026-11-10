<?php
namespace App\Notifications;
use Illuminate\Notifications\Notification;
class ConversacionAsignada extends Notification
{
    public function __construct(public int $conversacionId, public string $nombre, public string $asignadoPor) {}
    public function via(object $notifiable): array { return ['database']; }
    public function toArray(object $notifiable): array {
        return ['titulo'=>'Conversación asignada','mensaje'=>$this->asignadoPor.' te asignó la conversación de '.$this->nombre.'.','ruta'=>'conversaciones.index','parametros'=>['conversacion'=>$this->conversacionId],'permiso'=>'conversaciones.ver','conversacion_id'=>$this->conversacionId];
    }
}
