<?php

namespace Tests\Feature;

use App\Models\{CategoriaTuristica, DestinoTuristico, ServicioTuristico};
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class PublicCatalogTest extends TestCase
{
    use DatabaseTransactions;

    private function service(array $changes = []): ServicioTuristico
    {
        return ServicioTuristico::create(array_merge(['cCodigo' => uniqid('PUB'), 'cNombre' => uniqid('Catálogo prueba '), 'dPrecioCompra' => 4321.98, 'dPrecioVenta' => 1000, 'cMoneda' => 'MXN', 'lActivo' => true, 'lUsaVigencia' => false, 'dFechaInicioViaje' => today()->addMonth(), 'dFechaFinViaje' => today()->addMonth()->addWeek()], $changes));
    }

    public function test_catalogo_y_detalles_son_publicos_y_excluyen_servicios_no_publicados(): void
    {
        $visible = $this->service();
        $inicio = $this->service(['lUsaVigencia' => true, 'dFechaInicioVigencia' => today(), 'dFechaFinVigencia' => today()]);
        $inactivo = $this->service(['lActivo' => false]);
        $vencido = $this->service(['lUsaVigencia' => true, 'dFechaInicioVigencia' => today()->subMonth(), 'dFechaFinVigencia' => today()->subDay()]);
        $programado = $this->service(['lUsaVigencia' => true, 'dFechaInicioVigencia' => today()->addDay(), 'dFechaFinVigencia' => today()->addMonth()]);
        $this->get(route('catalogo.publico.index'))->assertOk()->assertDontSee('Acceso al sistema')->assertDontSee('4,321.98')->assertViewHas('paquetes', fn ($rows) => $rows->total() >= 2 && $rows->perPage() === 12);
        foreach ([$visible, $inicio] as $service) $this->get(route('catalogo.publico.show', $service->iID))->assertOk()->assertSee($service->cNombre)->assertSee($service->dFechaInicioViaje->format('d/m/Y'))->assertDontSee('4,321.98')->assertDontSee('dPrecioCompra');
        foreach ([$inactivo, $vencido, $programado] as $service) $this->get(route('catalogo.publico.show', $service->iID))->assertNotFound();
        $this->get(route('landing'))->assertOk()->assertSee(route('catalogo.publico.index'), false);
        $this->assertGuest();
    }

    public function test_filtros_destino_categoria_fechas_y_precios_incluyen_promociones(): void
    {
        $destino = DestinoTuristico::create(['cNombre' => uniqid('Destino público ')]);
        $categoria = CategoriaTuristica::create(['cNombre' => uniqid('Categoría pública ')]);
        $promo = $this->service(['iIDDestino' => $destino->iID, 'iIDCategoria' => $categoria->iID, 'lTienePromocion' => true, 'lPromocionActiva' => true, 'cTipoDescuento' => 'PORCENTAJE', 'dValorDescuento' => 20, 'dFechaInicioPromocion' => now()->subDay(), 'dFechaFinPromocion' => now()->addDay()]);
        $this->service(['iIDDestino' => $destino->iID, 'iIDCategoria' => $categoria->iID]);
        $filters = ['destino' => $destino->iID, 'categoria' => $categoria->iID, 'desde' => today()->addMonth()->toDateString(), 'hasta' => today()->addMonth()->toDateString(), 'moneda' => 'MXN', 'minimo' => 790, 'maximo' => 810, 'promocion' => 1, 'orden' => 'precio_asc'];
        $this->get(route('catalogo.publico.index', $filters))->assertOk()->assertSee('800.00')->assertViewHas('paquetes', fn ($rows) => $rows->pluck('iID')->all() === [$promo->iID]);
        $promo->update(['dFechaFinPromocion' => now()->subMinute()]);
        $this->get(route('catalogo.publico.index', $filters))->assertViewHas('paquetes', fn ($rows) => $rows->total() === 0);
    }

    public function test_busqueda_paginacion_validacion_y_limites_independientes(): void
    {
        $keyword = uniqid('PublicSearch');
        foreach (range(1, 13) as $i) $this->service(['cNombre' => $keyword.' '.$i]);
        $this->get(route('catalogo.publico.index', ['buscar' => $keyword, 'page' => 2]))->assertOk()->assertViewHas('paquetes', fn ($rows) => $rows->total() === 13 && $rows->count() === 1)->assertSee('buscar='.$keyword, false);
        $this->get(route('catalogo.publico.index', ['moneda' => 'MXN', 'maximo' => 1500, 'hasta' => today()->addYear()->toDateString()]))->assertOk();
        $this->get(route('catalogo.publico.index', ['minimo' => 50]))->assertSessionHasErrors('moneda');
        $this->get(route('catalogo.publico.index', ['moneda' => 'MXN', 'minimo' => 900, 'maximo' => 100]))->assertSessionHasErrors('maximo');
        $this->get(route('catalogo.publico.index', ['orden' => 'sql']))->assertSessionHasErrors('orden');
    }
}
