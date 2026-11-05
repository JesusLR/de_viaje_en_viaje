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
