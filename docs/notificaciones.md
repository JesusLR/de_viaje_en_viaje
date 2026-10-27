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

## Recordatorios por días sin abonos

El umbral se guarda en `variables_sistema` con clave `dias_sin_abonos`, desde
Configuración → Variables de entorno. Es un parámetro de la base de datos; no
modifica el archivo `.env`. Hasta que se configure no genera recordatorios.

`RecordatoriosAbonosService` se ejecuta al consultar la campana. No requiere cron,
colas ni mensajes externos. Considera apartados con saldo pendiente, sin cancelar,
finalizar ni eliminar, y con al menos un abono válido. Se usa la fecha del último
abono no anulado; los registros antiguos sin fecha de pago usan su fecha de alta.
Los días son días de calendario en la zona horaria de la aplicación.

Permisos: `variables_entorno.ver`, `variables_entorno.editar` y
`apartados.recordatorios`. El destinatario también necesita `apartados.ver`.
Sin `apartados.todos` solo recibe y consulta avisos de operaciones propias,
incluso si antes tenía acceso global.

El UUID determinista identifica usuario, apartado y ciclo de abonos, evitando
duplicados entre consultas concurrentes. Al cambiar los abonos o cerrar el apartado,
los avisos que ya no corresponden quedan leídos como historial. Un nuevo atraso
después de un abono nuevo puede generar otro aviso; leer uno no genera copias.
