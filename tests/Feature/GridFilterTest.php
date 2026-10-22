<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class GridFilterTest extends TestCase
{
    use DatabaseTransactions;

    public function test_filtered_pages_return_replaceable_results_without_swallowing_forms_or_modals(): void
    {
        $this->actingAs(User::factory()->create(['lCambiarPassword' => false]));
        foreach ([
            ['agencia.apartados.index', 'operaciones', 1],
            ['agencia.ventas.index', 'operaciones', 1],
            ['agencia.finanzas.index', 'finanzas', 2],
            ['itinerarios.index', 'itinerarios', 1],
            ['admin.permisos.index', 'permisos', 2],
            ['catalogo.publico.index', 'catalogo', 2],
        ] as [$route, $scope, $count]) {
            $response = $this->withHeaders(['Accept' => 'application/json'])->get(route($route))->assertOk();
            $html = $response->getContent();
            $this->assertStringContainsString('grid-filtros.js', $html);
            $dom = new \DOMDocument();
            @$dom->loadHTML('<?xml encoding="UTF-8">'.$html);
            $xpath = new \DOMXPath($dom);
            $this->assertSame(1, $xpath->query('//form[@data-grid-filter="'.$scope.'"]')->length, $route);
            $this->assertSame($count, $xpath->query('//*[@data-grid-results="'.$scope.'"]')->length, $route);
            $this->assertSame(0, $xpath->query('//*[@data-grid-results]//footer')->length, $route);
            $this->assertSame(0, $xpath->query('//*[@data-grid-results]//*[@id="nuevoMovimiento" or @id="accionApartado"]')->length, $route);
        }
    }
}
