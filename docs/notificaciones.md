# Notificaciones internas

La campana utiliza el canal `database` de Laravel y la tabla compartida `notifications`.
Cada fila pertenece a un destinatario (`notifiable_type`, `notifiable_id`), identifica la
clase del evento (`type`) y guarda su estado de lectura en `read_at`.
No se envían correos ni mensajes de WhatsApp.

Para agregar otro evento, crea una clase en `app/Notifications` que extienda
`Illuminate\Notifications\Notification`, devuelva `['database']` en `via()` y este
formato de datos en `toArray()`:

```php
return [
    'titulo' => 'Título del aviso',
    'mensaje' => 'Descripción breve',
    'ruta' => 'nombre.de.ruta',          // Opcional: ruta interna registrada.
    'parametros' => ['registro' => 123], // Opcional: parámetros de esa ruta.
    'permiso' => 'modulo.ver',          // Opcional: permiso necesario para ver el aviso.
];
```

Envía el aviso con `$usuario->notify(new MiNotificacion(...))` o
`Notification::send($usuarios, new MiNotificacion(...))`. Selecciona destinatarios
activos y autorizados para el evento. Los avisos con `permiso` se ocultan cuando el
usuario pierde ese permiso; el destino debe validar sus propios permisos también.

`CotizacionRecibida` se envía al registrar una solicitud desde el formulario público,
dentro de la misma transacción. Reciben el aviso los administradores activos y los
usuarios activos con `cotizaciones.ver`. No se generan avisos retroactivos.

La campana consulta los 20 avisos más recientes y el total sin leer, cada 30 segundos
mientras la pestaña está visible y al abrir el menú. Leer un aviso o marcar todos
solo afecta a las notificaciones visibles del usuario de la sesión.
