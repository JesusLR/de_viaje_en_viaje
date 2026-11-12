# Mensajería e inicio de sesión WhatsApp

El sidebar incluye Mensajería → Inicio de sesión. Se pueden crear sesiones, iniciar su motor, escanear el QR, ver el estado, cerrar la vinculación y eliminar la sesión. Las acciones se encuentran en el menú de tres puntos. La búsqueda y el paginado no recargan la página; el estado se actualiza cada 15 segundos y el QR cada 5 mientras su ventana está abierta.

## Permisos

- `mensajeria.ver`: acceder y consultar sesiones del sistema.
- `mensajeria.crear`: dar de alta sesiones.
- `mensajeria.conectar`: iniciar, ver el QR y desvincular WhatsApp.
- `mensajeria.eliminar`: eliminar sesiones.

El rol Administrador tiene acceso automáticamente. Los demás roles se configuran en Roles y permisos. No se necesita seeder ni una tabla nueva en Laravel.

## Configurar ambos proyectos

Primero desplegar los cambios del proyecto `OpenWA`, incluyendo la migración `1786900000000-AddSessionSourceSystem`. El arranque existente de OpenWA ejecuta las migraciones o sincroniza la BD SQLite de desarrollo. Las sesiones anteriores se conservan, sin asignarlas automáticamente a un sistema.

1. Generar una clave aleatoria: `openssl rand -hex 32`.
2. En el entorno de **OpenWA**, agregar un objeto JSON que asocie cada sistema con su clave exclusiva:

```dotenv
INTEGRATION_SYSTEMS='{"de_viaje_en_viaje":"CLAVE_ALEATORIA_DEL_SISTEMA"}'
```

Para otros sistemas, agregar otra entrada con otra clave. No reutilizar la clave administrativa de OpenWA. El API de integración solo admite sus credenciales dedicadas.

3. En `.env.docker` de **De Viaje en Viaje**, configurar la misma clave:

```dotenv
OPENWA_URL=http://74.208.53.13:2886/api
OPENWA_SYSTEM_KEY=CLAVE_ALEATORIA_DEL_SISTEMA
```

En desarrollo local la URL normalmente es `http://127.0.0.1:2785/api` (puerto del backend Nest; el dashboard Vite puede usar el 2886). Dentro del contenedor Laravel, 127.0.0.1 apunta al propio contenedor, por lo que producción debe usar la dirección accesible de OpenWA. Para transportar credenciales y QR entre servidores públicos, configurar HTTPS en OpenWA o usar una red privada.

4. Reiniciar/reconstruir OpenWA con su configuración de despliegue actual. Para el despliegue existente con `.env.server`, después de subir los cambios al servidor de OpenWA:

```bash
sudo docker compose --env-file .env.server -f docker-compose.yml -f docker-compose.server.yml up -d --build --no-deps openwa-api
sudo docker compose --env-file .env.server -f docker-compose.yml -f docker-compose.server.yml logs --tail=80 openwa-api
```

Conservar el nombre de BD, credenciales, API_PORT=2886 y volumen existentes. No usar `down -v`.

5. Después de subir los cambios a De Viaje en Viaje, reconstruir el contenedor HTTPS:

```bash
sudo docker compose --env-file .env.docker -f compose.yaml -f compose.https.yaml up -d --build --no-deps app
sudo docker compose --env-file .env.docker -f compose.yaml -f compose.https.yaml exec app php artisan config:clear
```

## Cómo se separan las sesiones

Laravel llama desde el servidor a `/api/integrations/sessions` usando la clave configurada. La clave nunca se entrega al navegador. OpenWA deduce y guarda `sourceSystem=de_viaje_en_viaje` desde la credencial, no desde el nombre ni un campo enviado por el usuario.

El listado se filtra por ese origen y cada QR/acción comprueba id y origen antes de actuar. Las sesiones de otros sistemas y las antiguas sin origen devuelven 404. Laravel verifica también que el origen recibido corresponda a este sistema. En el panel administrativo `/sessions` de OpenWA se muestra el origen; el administrador global de OpenWA mantiene su acceso de administración.

Los nombres remotos se guardan con prefijo (`de-viaje-en-viaje--recepcion`) para evitar conflictos entre sistemas; aquí se muestra `recepcion`. El QR corresponde a la misma sesión real de OpenWA, sin iframe ni contenido HTTP incrustado en la página HTTPS.

Escanear desde WhatsApp → Dispositivos vinculados → Vincular un dispositivo. Cerrar sesión desvincula WhatsApp; eliminar también borra el registro de sesión en OpenWA. El módulo no envía mensajes automáticamente.

## Chrome en desarrollo Windows

Si Puppeteer indica que no encuentra la versión de Chrome de su caché, se puede usar el Chrome instalado. En el `.env` **local de OpenWA**:

```dotenv
PUPPETEER_EXECUTABLE_PATH="C:/Program Files/Google/Chrome/Application/chrome.exe"
```

Reiniciar OpenWA después de cambiar esta configuración. Este valor es solo para desarrollo Windows; el contenedor Linux conserva su ruta de Chromium. Como alternativa, desde OpenWA se puede descargar el navegador esperado con `npx puppeteer browsers install chrome`.

## Enviar documentos desde Apartados

Junto al menú de tres puntos aparece un botón de WhatsApp con opciones para enviar el itinerario y el historial de abonos. Se utilizan los mismos PDF privados de las descargas, incluidos itinerarios cargados, generados o combinados.

Requiere `apartados.whatsapp` y `apartados.pdf`, además de acceso al apartado propio o `apartados.todos`. Para itinerarios también se requieren `itinerarios.ver` e `itinerarios.pdf`. El botón no aparece si no se tienen los permisos; el servidor vuelve a comprobarlos para cada consulta y envío.

La ventana obtiene el teléfono del cliente y permite modificarlo solo para ese envío. La sesión conectada se utiliza automáticamente y Confirmar envío inicia el envío directamente. El teléfono del cliente no se modifica en la BD.

Los números mexicanos de diez dígitos reciben 52; `+52` o `52` ya guardados no se duplican. Se aceptan espacios, paréntesis y guiones, y se adapta el formato mexicano antiguo `+521`. Para otro país se requiere el prefijo internacional explícito (`+` o `00`). Se rechazan números incompletos, extensiones y direcciones de grupos.

El PDF se envía por el backend al endpoint de integración de OpenWA, como documento adjunto, sin publicar un enlace del archivo. El máximo es 15 MiB. Se asigna un identificador de envío y se reutiliza la protección de OpenWA contra duplicados; no hay reintentos automáticos. Ante una interrupción o resultado incierto se debe revisar el chat antes de iniciar un nuevo envío. La respuesta de aceptación no garantiza que el dispositivo del destinatario ya lo haya recibido o leído.

Para desplegar esta funcionalidad se deben actualizar ambos proyectos. No agrega migraciones nuevas: se reutilizan el origen de sesiones, la tabla de mensajes y la protección de envíos de OpenWA existentes.


## Sesión única y envío directo

De Viaje en Viaje permite crear una sola sesión desde su módulo: el servidor comprueba su existencia bajo un bloqueo compartido y el botón de alta se deshabilita cuando ya hay una. La sesión se conserva al desconectarse y puede volver a vincularse por QR. No se eliminan sesiones anteriores automáticamente. La restricción de alta aplica a este sistema; OpenWA conserva su capacidad para otras integraciones.

El remitente se resuelve en el servidor entre las sesiones conectadas del sistema. No se solicita elegir una sesión. Al pulsar **Confirmar envío**, se envía inmediatamente al número indicado sin una segunda confirmación. El historial lleva el texto «Hola, aquí tienes tu historial de abonos» y el itinerario «Hola, aquí tienes tu itinerario de viaje», seguidos del agradecimiento de De Viaje en Viaje, como descripción del PDF. Se conserva la protección contra intentos duplicados y resultados inciertos.


## Envíos desde Ventas y nombres de archivo

Ventas incluye el botón de WhatsApp junto a las acciones, con el permiso `ventas.whatsapp`, además de `ventas.ver` y `ventas.pdf`. Para itinerarios requiere también los permisos de itinerarios. Respeta la consulta de ventas propias y no envía operaciones canceladas o eliminadas. El historial se ofrece cuando existen pagos vigentes. El remitente se utiliza automáticamente y no se muestra en el modal.

Las descargas de itinerarios y sus adjuntos de WhatsApp usan `itinerario_nombre_del_servicio.pdf`; las cotizaciones usan `cotizacion_nombre_del_cliente_nombre_del_servicio.pdf`. Cuando hay varios servicios se incluyen sus nombres en el mismo archivo. Se normalizan los nombres para evitar caracteres no válidos.


## Cotizaciones

El grid de Cotizaciones incluye un botón de WhatsApp con la opción Enviar cotización PDF. Requiere `cotizaciones_servicios.whatsapp`, además de consultar cotizaciones y descargar su PDF. Utiliza el mismo documento y nombre de archivo de la descarga, con el texto «Hola, aquí tienes tu cotización de viaje». Precarga el teléfono para clientes registrados; para nombres genéricos se captura manualmente. El número se puede editar sin modificar al cliente. La sesión conectada se usa automáticamente y Confirmar envío envía directamente, con protección contra duplicados.


## Venta liquidada desde Vender

Al registrar una venta directa liquidada desde `/agencia/vender`, después de guardar la venta y su pago se envía automáticamente el comprobante de compra saldada al teléfono del cliente, usando la sesión conectada del sistema. Es parte del registro autorizado por `vender.venta`; no solicita una confirmación ni el permiso de envío manual de Ventas. Incluye un texto de agradecimiento. Los apartados no disparan este envío. Si falta el teléfono, la configuración, la sesión o falla el envío, la venta y el pago se conservan y aparece un aviso. No se hacen reintentos automáticos ante resultados inciertos.


El envío automático de ventas liquidadas se activa o desactiva desde Configuración → Variables de entorno → WhatsApp · Venta liquidada. Se guarda como `whatsapp_venta_automatica` en `variables_sistema` (1 activo, 0 inactivo), con el permiso `variables_entorno.editar`. Por defecto conserva el envío activo. Apagado no consulta OpenWA ni envía documentos al registrar ventas y no muestra avisos de fallo de mensajería. No afecta los envíos manuales. No requiere nuevas migraciones ni seeders.


## Comprobante de pago e historial

Las ventas directas (`cTipoOperacion=VENTA`) muestran el comprobante de pago; su pago único no se considera un abono. Las ventas originadas como apartado conservan su historial cuando tienen abonos vigentes. El detalle y el grid permiten enviar el comprobante de pago y, cuando corresponde, el historial mediante el modal de confirmación del número. Las rutas de historial también validan el origen. El comprobante completo se denomina Comprobante de pago en el PDF, la descarga y el envío manual o automático.


## Bandeja compartida de conversaciones

Mensajería → Conversaciones utiliza el mismo número de la agencia. Cada empleado entra con su cuenta de De Viaje en Viaje y no vincula un teléfono propio.

Permisos:
- `conversaciones.ver`: abrir la bandeja y consultar las conversaciones asignadas al usuario.
- `conversaciones.todos`: consultar también conversaciones ajenas y sin asignar (supervisor).
- `conversaciones.asignar`: asignar, transferir o dejar sin responsable las conversaciones que puede consultar.
- `conversaciones.responder`: enviar texto o adjuntar un PDF de hasta 15 MB.
- `conversaciones.atender`: marcar atendida o reabrir una conversación visible.
- `conversaciones.nota`: agregar notas internas, que nunca se envían a WhatsApp.

Para un supervisor asignar `ver`, `todos`, `asignar` y los permisos operativos que necesite. Para un asesor asignar `ver`, `responder`, `atender` y `nota`. Solo usuarios activos con permisos de consulta y respuesta aparecen como destinatarios de una asignación.

Las comprobaciones de responsable se realizan en Laravel antes de consultar o enviar. Las respuestas y transferencias bloquean el registro para que una transferencia no compita con un envío. OpenWA autentica el origen del sistema en todas las rutas nuevas de chat; las credenciales no salen al navegador. Las notas y transferencias se guardan en tablas locales separadas del historial de mensajes de OpenWA.

La bandeja actualiza las novedades cada 15 segundos mientras está visible, compartiendo un bloqueo y un intervalo de sincronización entre empleados. No requiere configurar un webhook público. Las conversaciones atendidas se reabren al detectar un nuevo mensaje entrante y conservan su responsable. Se muestran conversaciones individuales, no grupos, estados ni canales.

El historial se consulta en páginas de 100 mensajes y comprende los mensajes que OpenWA conserva. No importa automáticamente todo el historial antiguo del teléfono. Por ahora el chat muestra texto y un indicador para mensajes de otros tipos; no reproduce audio ni muestra imágenes recibidas. Permite subir el PDF ya descargado de ventas, itinerarios o cotizaciones. No hay distribución automática ni selección directa de documentos de esos módulos dentro del chat.

Si el teléfono puede identificarse, se muestra el cliente relacionado y enlaces a sus ventas, apartados y cotizaciones, respetando permisos y filtros del usuario. Los identificadores internos LID pueden no proporcionar un teléfono para esta relación.

### Despliegue

1. Subir ambos proyectos y actualizar primero OpenWA: el controlador `integration-conversation.controller.ts` y su registro en `message.module.ts` agregan las rutas protegidas `/api/integrations/sessions/:sessionId/conversations`, `/messages`, `/text` y `/pdf`.
2. Reconstruir OpenWA usando los comandos de despliegue documentados arriba y conservando la clave de integración y el volumen existentes.
3. Actualizar De Viaje en Viaje y reconstruir `app`. El entrypoint ejecuta la migración `2026_11_06_000001_create_whatsapp_conversations`, que crea `whatsapp_conversations` y `whatsapp_conversation_events`. No requiere seeders. En un entorno sin ese entrypoint ejecutar `php artisan migrate --force`.
4. Asignar permisos a los roles y abrir Mensajería → Conversaciones. Un supervisor verá las conversaciones sin asignar y podrá repartirlas.

No se han enviado mensajes reales durante las pruebas. La operación final requiere una sesión conectada de OpenWA y comprobar recepción real después del despliegue.


Al marcar una conversación como atendida se libera su responsable y se mantiene el historial. Un nuevo mensaje la devuelve a Sin asignar. Cada asignación a un responsable diferente genera una notificación de campana para ese usuario, con acceso a la conversación. Repetir la misma asignación no duplica el aviso. La campana permite marcar todas las notificaciones visibles del usuario como leídas.


### Historial combinado de conversaciones

La bandeja consulta los mensajes guardados y, al abrir la primera página, hasta 100 mensajes recientes disponibles directamente en WhatsApp, como `/chats` de OpenWA. Combina por identificador de WhatsApp y da prioridad a la copia guardada para conservar estados y adjuntos. La lectura en vivo usa una caché de 60 segundos, acotada a 100 conversaciones, sin descargar medios. Las páginas anteriores siguen recorriendo los mensajes guardados. El historial completo del teléfono puede no estar disponible: se muestra un aviso si la consulta falla y un estado vacío explícito si no hay mensajes. Este cambio requiere actualizar OpenWA y Laravel.


El clip de conversaciones admite archivos generales de hasta 15 MB. JPEG, PNG y WebP se envían como imágenes; los demás formatos como documentos adjuntos. La compatibilidad final depende de WhatsApp. Se conserva el nombre normalizado y la extensión. El archivo se selecciona antes de pulsar el icono de envío. Requiere actualizar OpenWA con el endpoint de integración `/conversations/files` y Laravel; no requiere migraciones nuevas.


### Imágenes y mensajes sin leer

Las imágenes JPEG, PNG, WebP y GIF conservadas por OpenWA se muestran dentro de la conversación mediante una ruta privada con comprobación del responsable. Si OpenWA no conserva los bytes aparece Imagen no disponible. Los identificadores internos y las credenciales no se exponen como enlaces a OpenWA. La bolita verde usa el contador de no leídos de WhatsApp, sincronizado con la bandeja, y al abrir el chat se solicita el recibo de lectura a WhatsApp. Es un estado compartido de la cuenta, no un contador independiente por empleado. Requiere actualizar ambos proyectos y ejecutar la migración `2026_11_11_000001_add_chat_unread_count`.


## Webhooks de OpenWA

La bandeja admite avisos firmados de mensajes recibidos/enviados, confirmaciones, cambios o eliminación de mensajes y estado de sesión. El receptor es `POST /webhooks/openwa`. Solo esa ruta está exenta de CSRF; exige `X-OpenWA-Signature: sha256=...`, calculada sobre el cuerpo original con la clave exclusiva del receptor. Rechaza sesiones no registradas, claves ausentes, cuerpos mayores de 1 MiB y eventos no admitidos. No guarda el texto ni los archivos del evento: actualiza metadatos de conversación, no leídos y un indicador de cambios. La tabla de recibos evita procesar nuevamente los reintentos con la misma clave de idempotencia.

OpenWA conserva sus validaciones de URL, restricciones de red y mecanismo de reintentos. El registro usa la credencial de integración del sistema: `POST /api/integrations/sessions/:sessionId/webhook`. Actualiza un webhook con la misma URL o crea uno si falta; no elimina otros. No devuelve la clave de firma.

### Activación en producción

1. Subir los cambios de ambos proyectos y actualizar primero OpenWA, con los comandos de reconstrucción indicados arriba.
2. Generar una clave de firma independiente de las credenciales de MySQL y de `OPENWA_SYSTEM_KEY`:

```bash
openssl rand -hex 32
```

3. En `.env.docker` de De Viaje en Viaje, configurar:

```dotenv
OPENWA_WEBHOOK_URL=https://de-viaje-en-viaje.com/webhooks/openwa
OPENWA_WEBHOOK_SECRET=CLAVE_ALEATORIA_GENERADA
```

No publicar la clave ni colocarla en el navegador. OpenWA recibe esa misma clave a través del comando de registro; no necesita otra variable de entorno de firma.

4. Reconstruir `app`. El entrypoint ejecuta la migración `2026_11_11_000002_create_openwa_webhook_receipts`, que crea las tablas de sesiones registradas y recibos, sin eliminar datos existentes:

```bash
sudo docker compose --env-file .env.docker -f compose.yaml -f compose.https.yaml up -d --build --no-deps app
sudo docker compose --env-file .env.docker -f compose.yaml -f compose.https.yaml exec app php artisan openwa:webhook
```

La URL debe ser HTTPS en producción y accesible desde OpenWA. El comando registra las sesiones existentes del sistema y puede repetirse para actualizar la clave o la URL. Después de crear otra sesión, volver a ejecutarlo. No requiere seeders, Redis ni un worker adicional en Laravel.

### Actualización de la pantalla y recuperación

El navegador consulta un indicador pequeño cada 5 segundos mientras la pestaña está visible y recarga los datos cuando cambia. Se mantiene la consulta de respaldo cada 15 segundos. No es WebSocket: un aviso se refleja normalmente en la siguiente consulta del indicador. Los mensajes y archivos se siguen consultando en OpenWA con los mismos permisos y rutas privadas.

Si OpenWA no entrega un aviso o el receptor no está disponible, sus reintentos y la sincronización periódica mantienen una vía de recuperación. La firma no arregla desconexiones de WhatsApp ni recupera historial que el motor no tenga disponible. Para probar la activación, usar un mensaje autorizado de prueba y comprobar recepción, bolita verde y lectura; nunca ejecutar mensajes reales como parte de la suite automática.

Los recibos contienen hashes, sesión, evento y fecha, sin cuerpos de mensajes. Actualmente se conservan sin depuración automática para mantener la deduplicación de reintentos antiguos.


### Desarrollo local con Laragon

Para proyectos cuyos dominios `.test` contienen guiones bajos, la validación de URL puede rechazarlos. En la instalación local de Laragon se comprobó el receptor `http://127.0.0.1/de_viaje_en_viaje/public/webhooks/openwa`. Configurarlo en `OPENWA_WEBHOOK_URL` y agregar solo `127.0.0.1` a `SSRF_ALLOWED_HOSTS` en el `.env` privado de OpenWA, manteniendo las entradas existentes y la protección SSRF activa. No copiar esta excepción de desarrollo al servidor. Recargar el backend local al cambiar esa variable.

`openwa:webhook` ahora comprueba también una entrega HTTP con el evento `test` del servicio nativo de OpenWA. El receptor acepta esa prueba firmada sin crear conversaciones ni enviar mensajes de WhatsApp. Si la entrega falla, el comando termina indicando que el registro existe pero el receptor no respondió correctamente.
