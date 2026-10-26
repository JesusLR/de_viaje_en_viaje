# Acciones de los grids

Todas las acciones de filas se muestran en un menú de tres puntos, con el estilo de
Apartados. Las plantillas internas cargan `apartados-acciones.js` y su CSS compartido.

El componente detecta columnas cuyo encabezado es `Acciones`, agrupa sus enlaces,
botones y formularios y conserva atributos, permisos y destinos. Para una cuadrícula
de tarjetas se puede marcar el contenedor de acciones con `data-grid-actions`.
Cada botón de icono debe tener `title` o `aria-label`, que se usa como texto del menú.
Los menús existentes con `.apartado-mas` se conservan.

Los menús se mueven temporalmente al `body` al abrirse para evitar recortes por el
scroll de las tablas. Para manejar acciones AJAX, delegar los eventos en `document`
y contemplar tanto la tabla como el menú identificado por su atributo de origen:

```js
$(document).on('click', '#tablaEjemplo .btn-editar, [data-grid-table="tablaEjemplo"] .btn-editar', function () {
    // Los atributos data-id y el elemento original se conservan.
});
```

Se inicializan automáticamente los resultados AJAX y DataTables, y se cierran los
menús antes de paginar o reemplazar filas. Los botones generales de alta, búsqueda,
filtros o navegación no son acciones de fila.

Prueba de navegador (requiere Playwright y Chromium):
`node --test tests/js/grid-actions.test.cjs`.
Se puede indicar un navegador instalado con `GRID_TEST_BROWSER`.
