<?php

namespace Tests\Feature;

use App\Models\{Cliente, ServicioTuristico, User, VentaPaquete};
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ChildrenSaleTest extends TestCase
{
    use DatabaseTransactions;

    private function setupSale(bool $active = true, float $price = 250): array
    {
        $this->actingAs(User::factory()->create(['lCambiarPassword' => false]));
        $client = Cliente::create(['cNombre' => 'Familia', 'cPrimerApellido' => 'Prueba', 'lActivo' => true]);
        $service = ServicioTuristico::create(['cCodigo' => uniqid('MEN'), 'cNombre' => 'Paquete familiar',
            'dPrecioVenta' => 1000, 'lUsaVigencia' => false, 'lActivo' => true,
            'lPrecioMenorActivo' => $active, 'dPrecioMenor' => $price]);
        return [$client, $service];
    }

    public function test_sale_charges_adults_and_children_and_keeps_original_child_price(): void
    {
        [$client, $service] = $this->setupSale();
        $this->get(route('agencia.vender.index'))->assertOk()->assertSee('data-menores-activo="1"', false)->assertSee('iCantidadMenores', false);
        $this->post(route('agencia.ventas.store'), ['iIDCliente' => $client->iID, 'accion' => 'vender', 'cMetodo' => 'Efectivo',
            'items' => [['iIDServicio' => $service->iID, 'iCantidad' => 2, 'iCantidadMenores' => 3, 'dPrecioMenor' => 1]]])->assertSessionHasNoErrors();
        $sale = VentaPaquete::latest('iID')->firstOrFail();
        $detail = $sale->detalles()->firstOrFail();
        $this->assertEquals(2750, $sale->dTotal);
        $this->assertEquals(2750, $sale->pagos()->sum('dImporte'));
        $this->assertEquals(5, $sale->iCantidad);
        $this->assertEquals(2, $detail->iCantidad);
        $this->assertSame(3, $detail->iCantidadMenores);
        $this->assertEquals(250, $detail->dPrecioMenor);
        $service->update(['dPrecioMenor' => 999]);
        $this->assertEquals(250, $detail->fresh()->dPrecioMenor);
        $this->get(route('agencia.ventas.show', $sale))->assertOk()->assertSee('3 menores')->assertSee('Menor: 250.00 MXN');
        $this->get(route('agencia.ventas.comprobanteCompra', $sale))->assertOk()->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_free_children_can_be_added_to_an_apartado(): void
    {
        [$client, $service] = $this->setupSale(true, 0);
        $this->post(route('agencia.ventas.store'), ['iIDCliente' => $client->iID, 'accion' => 'apartar',
            'items' => [['iIDServicio' => $service->iID, 'iCantidad' => 1, 'iCantidadMenores' => 2]]])->assertSessionHasNoErrors();
        $sale = VentaPaquete::latest('iID')->firstOrFail();
        $this->assertEquals(1000, $sale->dTotal);
        $this->assertEquals(0, $sale->detalles()->firstOrFail()->dPrecioMenor);
        $this->post(route('agencia.ventas.agregarPaquete', $sale), ['iIDServicio' => $service->iID, 'iCantidad' => 1, 'iCantidadMenores' => 3])->assertSessionHasNoErrors();
        $this->assertEquals(2000, $sale->fresh()->dTotal);
        $this->assertEquals(7, $sale->fresh()->iCantidad);
        $this->get(route('agencia.ventas.show', $sale))->assertOk()->assertSee('Gratis')->assertSee('3 menores');
    }

    public function test_disabled_child_rate_and_invalid_quantities_are_rejected_atomically(): void
    {
        [$client, $service] = $this->setupSale(false);
        $before = VentaPaquete::count();
        foreach ([1, -1, 1.5, 1001] as $children) {
            $this->postJson(route('agencia.ventas.store'), ['iIDCliente' => $client->iID, 'accion' => 'apartar',
                'items' => [['iIDServicio' => $service->iID, 'iCantidad' => 1, 'iCantidadMenores' => $children]]])->assertStatus(422);
        }
        $this->assertSame($before, VentaPaquete::count());
    }
}
