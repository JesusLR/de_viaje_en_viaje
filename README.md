# ✈️ Sistema de Gestión para Agencia de Viajes (De Viaje)

Sistema web monolítico integral diseñado para la administración y comercialización de servicios turísticos, paquetes vacacionales, tours, excursiones y viajes nacionales e internacionales.

Instalación en servidor con Docker: [guía de instalación y respaldos](docs/DOCKER.md).

Integración de WhatsApp, permisos y despliegue de ambos proyectos: [guía de mensajería](docs/mensajeria.md).

---

## 🏛️ Arquitectura del Sistema

El proyecto sigue una arquitectura **Monolítica MVC (Modelo - Vista - Controlador)** reforzada con una **Capa de Servicios (Service Pattern)** para aislar la lógica de negocio compleja.

```
[ Cliente / Navegador ]
       │  ▲
  AJAX / JSON  │  Blade Views (HTML5 + Bootstrap 5 + jQuery)
       ▼  │
[ Laravel 12 Controllers ]
       │
[ Service Layer (ServicioTuristicoService) ]
       │
[ Eloquent ORM Models ] ──► [ Base de Datos MySQL ]
       │
[ Laravel Storage ] ──► [ Public Assets / Imágenes ]
```

### Principios Arquitectónicos
- **Desacoplamiento Operativo**: Lógica financiera (simulación de márgenes, utilidad y promociones) aislada en servicios independientes.
- **Interactividad Asíncrona (SPA-like)**: Operaciones CRUD, filtrado dinámico y previsualizaciones financieras procesadas mediante peticiones **AJAX** con respuestas estándar JSON.
- **Convención de Nomenclatura (Notación Húngara)**:
  - `iID`: Enteros (IDs, llaves foráneas).
  - `cNombre`, `cCodigo`, `cDescripcion`: Cadenas de texto.
  - `dPrecioCompra`, `dPrecioVenta`, `dValorDescuento`: Números decimales/flotantes.
  - `dFechaInicioVigencia`, `dFechaFinVigencia`: Fechas (`YYYY-MM-DD`).
  - `lActivo`, `lTienePromocion`, `lSuccess`: Valores booleanos (`1`/`0` o `true`/`false`).
  - `oServicio`, `oProveedor`: Instancias de Objetos / Modelos.
  - `aCategorias`, `aDestinos`: Arreglos / Arrays.

---

## 🛠️ Herramientas y Tecnologías Utilizadas

### 🔹 Backend
- **Laravel 12** (PHP 8.2+) — Framework principal de desarrollo.
- **Eloquent ORM** — Mapeo objeto-relacional para interacción con base de datos.
- **Carbon** — Gestión y validación estricta de fechas de vigencia y promociones.
- **PHPUnit 11** — Suite de pruebas automatizadas de integración y características (Feature Tests).

### 🔹 Frontend
- **Blade Templating Engine** — Motor de plantillas servidor.
- **Bootstrap 5.3** — Framework UI para diseño adaptativo (Responsive), componentes Flexbox, modales de diálogo desplazables y pestañas.
- **FontAwesome 6** — Iconografía vectorial profesional.

### 🔹 JavaScript & Librerías Cliente
- **jQuery 3.7+** — Manipulación DOM y arquitectura AJAX.
- **DataTables 1.13+** — Renderizado dinámico de listados masivos, ordenamiento, filtrado en tiempo real e internacionalización en español.
- **SweetAlert2** — Notificaciones emergentes, diálogos de confirmación y alertas de validación.

### 🔹 Base de Datos y Almacenamiento
- **MySQL** — Motor relacional de almacenamiento.
- **Laravel Public Storage (`storage:link`)** — Gestión de imágenes principales y galerías fotográficas asociadas a los servicios.

---

## 📦 Módulos del Sistema

### 1. Servicios y Paquetes Turísticos
- Registro, edición y desactivación lógica de paquetes y excursiones.
- Generación automática de código correlativo único (`SERV-2026-0001`).
- Simulador financiero en tiempo real: cálculo de costo, precio venta público, porcentaje de utilidad y ganancia estimadas.
- Módulo de Promociones (Descuento porcentual o importe fijo con vigencia).
- Carga de imagen principal y galería fotográfica múltiple.

### 2. Catálogos Generales (Submódulo)
- **Proveedores Comercial**: Registro de socios y proveedores turísticos.
- **Categorías Turísticas**: Clasificación general de los servicios (ej. Excursiones, Cruceros, Paquetes Completos).
- **Destinos Turísticos**: Ubicaciones geográficas por ciudad, estado y país.

### 3. Ventas y Apartados

- Registro de ventas directas liquidadas y apartados con seguimiento de abonos.
- Consulta de operaciones propias o de todos los vendedores según permisos.
- Comprobante de pago, historial de abonos e itinerarios en PDF.
- Las ventas directas no muestran historial de abonos; las provenientes de apartados lo conservan cuando corresponde.
- Envío de documentos por WhatsApp con confirmación del número, sin modificar el teléfono del cliente.
- Envío automático del comprobante al registrar una venta liquidada, configurable desde Variables de entorno.

### 4. Atención al Cliente

- **Cotizaciones web**: recepción del formulario público, seguimiento por responsable, estados y observaciones.
- **Cotizaciones de servicios**: propuestas para clientes registrados o nombres genéricos, con precios y descuentos guardados.
- Consulta, descarga y envío por WhatsApp del PDF de cotización.
- Filtros por fechas y notificaciones de solicitudes recibidas desde la página pública.

### 5. Itinerarios y Catálogo Público

- Itinerarios reutilizables asignados a servicios o cargados como PDF.
- Descarga y envío de itinerarios desde las operaciones comerciales.
- Catálogo con descripción siempre visible y secciones desplegables de detalles, cancelaciones, qué incluye y qué no incluye, cuando tienen contenido.
- Tarifas de menores opcionales por servicio.

### 6. Mensajería de WhatsApp

La conexión utiliza **OpenWA como servicio externo**, con una credencial exclusiva para `de_viaje_en_viaje`. Cada empleado accede con su usuario de este sistema; las respuestas salen del mismo número de la agencia.

- **Inicio de sesión**: creación de una sesión desde el módulo, vinculación por QR y consulta de su estado.
- **Conversaciones**: bandeja compartida con lista lateral, historial paginado, respuestas de texto y documentos PDF de hasta 15 MB.
- Asignación y transferencia manual entre empleados activos, notas internas e historial de responsables.
- Estados **Sin asignar**, **En atención** y **Atendida**; reapertura al detectar nuevos mensajes entrantes.
- Solo se permite enviar cuando la conversación está asignada y en atención.
- Los documentos se identifican como adjuntos con nombre y tipo cuando OpenWA conserva esos datos.
- Identificación del cliente por teléfono y enlaces a operaciones según los permisos del usuario.
- Actualización periódica cada 15 segundos mientras la bandeja está visible, sin recargar toda la página.

#### Permisos de conversaciones

| Permiso | Alcance |
| --- | --- |
| `conversaciones.ver` | Consultar conversaciones asignadas al usuario. |
| `conversaciones.todos` | Consultar también conversaciones ajenas y sin asignar. |
| `conversaciones.asignar` | Asignar o transferir conversaciones visibles. |
| `conversaciones.responder` | Enviar texto y PDF. |
| `conversaciones.atender` | Cerrar o reabrir la atención. |
| `conversaciones.nota` | Agregar notas internas que no se envían al cliente. |

Un supervisor necesita `ver`, `todos` y `asignar`, además de los permisos operativos que utilice. Un asesor necesita `ver`, `responder` y, según sus funciones, `atender` y `nota`. Las restricciones se verifican en el servidor para lecturas y envíos, no solo en la interfaz.

**Alcance actual:** conversaciones individuales; no grupos ni canales. El historial depende de los mensajes conservados por OpenWA y no importa automáticamente todo el historial antiguo del teléfono. Audios e imágenes recibidos aparecen como indicadores, sin reproducción o vista previa. Los PDF se adjuntan seleccionando un archivo; la tarjeta de documento todavía no ofrece previsualización. No hay distribución automática de conversaciones.

### 7. Finanzas y Estadísticas

- Consulta de ingresos y egresos y exportación a Excel.
- Distinción entre ventas directas y operaciones originadas como apartados.
- Estadísticas por fechas o periodo histórico.

### 8. Administración y Configuración

- Usuarios activos e inactivos, roles y permisos por módulo.
- Variables operativas: días sin abonos para recordatorios y activación del envío automático de comprobantes.
- Notificaciones de cotizaciones web y recordatorios de abonos para usuarios autorizados.

## Despliegue de Mensajería

Primero actualizar **OpenWA** con los controladores de integración y después esta aplicación. Ambos proyectos deben usar la misma clave de integración; no compartir la clave administrativa de OpenWA ni publicarla en el repositorio.

```dotenv
# En el entorno de De Viaje en Viaje:
OPENWA_URL=https://TU_SERVIDOR_OPENWA/api
OPENWA_SYSTEM_KEY=CLAVE_EXCLUSIVA_DEL_SISTEMA
```

La configuración del origen, la clave en OpenWA y los comandos de Docker se detallan en [docs/mensajeria.md](docs/mensajeria.md). La URL debe ser accesible desde el contenedor de Laravel.

La bandeja requiere la migración `2026_11_06_000001_create_whatsapp_conversations`, que crea las tablas de conversaciones y eventos internos. En desarrollo:

```bash
php artisan migrate
```

En el despliegue Docker de este proyecto, el entrypoint ejecuta las migraciones al arrancar `app`. Asignar después los permisos a los roles. No se requieren seeders de muestra para habilitar la bandeja.

Un fallo de WhatsApp al registrar una venta no revierte la venta ni su pago. Ante un envío de resultado incierto, revisar el chat antes de repetirlo.

---

## 🚀 Comandos Principales de Desarrollo

```bash
# Ejecutar migraciones de base de datos
php artisan migrate

# Crear enlace simbólico para imágenes de almacenamiento
php artisan storage:link

# Ejecutar la suite completa de pruebas automatizadas
vendor/bin/phpunit

# Iniciar servidor de desarrollo local
php artisan serve
```

---

## 🧪 Pruebas Automatizadas (PHPUnit)
El sistema incluye pruebas de integración para la lógica comercial, el catálogo, los permisos y la mensajería. Las pruebas de WhatsApp utilizan respuestas simuladas, sin enviar mensajes reales:

```bash
vendor/bin/phpunit tests/Feature/ProveedorTest.php tests/Feature/ServicioTuristicoTest.php

# Conversaciones, sesiones y documentos de WhatsApp
vendor/bin/phpunit tests/Feature/ConversacionesTest.php tests/Feature/MensajeriaTest.php tests/Feature/ApartadoWhatsAppTest.php
```
