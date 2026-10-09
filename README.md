# ✈️ Sistema de Gestión para Agencia de Viajes (De Viaje)

Sistema web monolítico integral diseñado para la administración y comercialización de servicios turísticos, paquetes vacacionales, tours, excursiones y viajes nacionales e internacionales.

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
El sistema incluye cobertura de pruebas de integración para verificar la consistencia de los endpoints API y la lógica financiera:

```bash
vendor/bin/phpunit tests/Feature/ProveedorTest.php tests/Feature/ServicioTuristicoTest.php
```
