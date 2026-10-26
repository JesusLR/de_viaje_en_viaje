const { test } = require('node:test');
const assert = require('node:assert/strict');
const path = require('node:path');
const { chromium } = require('playwright');

test('grid menus preserve delegated actions, forms, permissions and AJAX redraws', async () => {
    const browser = await chromium.launch({ headless: true, executablePath: process.env.GRID_TEST_BROWSER || undefined });
    try {
        const page = await browser.newPage();
        await page.setContent(`<table id="tablaUsuarios"><thead><tr><th>Usuario</th><th>Acciones</th></tr></thead><tbody><tr><td>Ana</td><td>
            <button class="apartado-icono btn-editar" data-id="7" title="Editar usuario"><i></i></button>
            <button class="apartado-icono btn-eliminar" data-permission="usuarios.activar" title="Desactivar"><i></i></button>
            <form method="post" action="/prueba"><input type="hidden" name="_token" value="token"><button class="btn btn-outline-danger" type="submit">Anular</button></form>
        </td></tr></tbody></table>`);
        const vendor = 'vendor/phpunit/php-code-coverage/src/Report/Html/Renderer/Template/js/';
        await page.addScriptTag({path: path.resolve(vendor, 'jquery.min.js')});
        await page.addScriptTag({path: path.resolve(vendor, 'bootstrap.bundle.min.js')});
        await page.evaluate(() => {
            window.userPermissions = ['usuarios.editar'];
            window.edited = null; window.submitted = false;
            $(document).on('click', '#tablaUsuarios .btn-editar, [data-grid-table="tablaUsuarios"] .btn-editar', function () { window.edited = $(this).data('id'); });
            document.addEventListener('submit', event => { event.preventDefault(); window.submitted = new FormData(event.target).get('_token'); });
        });
        await page.addScriptTag({path: path.resolve('public/js/apartados-acciones.js')});
        await page.waitForSelector('#tablaUsuarios .apartado-mas');
        assert.equal(await page.locator('#tablaUsuarios .apartado-mas').count(), 1);
        assert.equal(await page.locator('.btn-eliminar').count(), 0);
        await page.locator('#tablaUsuarios [data-bs-toggle="dropdown"]').click();
        await page.waitForSelector('body > .apartado-menu.show');
        await page.locator('body > .apartado-menu .btn-editar').click();
        assert.equal(await page.evaluate(() => window.edited), 7);
        await page.locator('#tablaUsuarios [data-bs-toggle="dropdown"]').click();
        await page.locator('body > .apartado-menu button[type="submit"]').click();
        assert.equal(await page.evaluate(() => window.submitted), 'token');
        await page.evaluate(() => {
            document.dispatchEvent(new CustomEvent('grid:beforeupdate'));
            document.querySelector('#tablaUsuarios tbody').innerHTML = '<tr><td>Luis</td><td><button class="apartado-icono btn-editar" data-id="8" title="Editar usuario"><i></i></button></td></tr>';
            document.dispatchEvent(new CustomEvent('grid:updated'));
        });
        await page.waitForSelector('#tablaUsuarios .apartado-mas');
        assert.equal(await page.locator('body > .apartado-menu').count(), 0);
        await page.locator('#tablaUsuarios [data-bs-toggle="dropdown"]').click();
        await page.locator('body > .apartado-menu .btn-editar').click();
        assert.equal(await page.evaluate(() => window.edited), 8);
        assert.equal(await page.locator('#tablaUsuarios .apartado-mas').count(), 1);
    } finally { await browser.close(); }
});
