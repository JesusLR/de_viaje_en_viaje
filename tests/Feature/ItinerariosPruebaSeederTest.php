<?php

namespace Tests\Feature;

use App\Models\{Itinerario, ItinerarioActividad, ItinerarioDia, ServicioTuristico};
use Database\Seeders\ItinerariosPruebaSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ItinerariosPruebaSeederTest extends TestCase
{
    use DatabaseTransactions;

    public function test_examples_are_local_only_reusable_and_preserve_existing_assignments(): void
    {
        $environment = $this->app['env'];
        try {
            $this->app['env'] = 'production';
            try {
                app(ItinerariosPruebaSeeder::class)->run();
                $this->fail('La carga de ejemplos debe rechazarse en producción.');
            } catch (\RuntimeException $exception) {
                $this->assertStringContainsString('solo se pueden cargar en el entorno local', $exception->getMessage());
            }

            $this->app['env'] = 'local';
            app(ItinerariosPruebaSeeder::class)->run();
            $this->assertSame(10, Itinerario::where('nombre', 'like', 'DEMO - % (Prueba)')->count());
            $demo = ServicioTuristico::where('cCodigo', 'DEMO-SERV-001')->firstOrFail();
            $this->assertNotNull($demo->iIDItinerario);
            $this->assertCount(6, $demo->itinerarioSnapshot());
            $this->assertCount(3, $demo->itinerarioSnapshot()[0]['actividades']);
            $this->assertGreaterThanOrEqual(2, $demo->itinerario->servicios()->count());

            $custom = Itinerario::create(['nombre' => 'Recorrido personalizado']);
            $demo->update(['iIDItinerario' => $custom->id, 'lPublicarItinerario' => false]);
            $counts = [Itinerario::count(), ItinerarioDia::count(), ItinerarioActividad::count()];
            app(ItinerariosPruebaSeeder::class)->run();
            $this->assertSame($counts, [Itinerario::count(), ItinerarioDia::count(), ItinerarioActividad::count()]);
            $this->assertSame($custom->id, $demo->fresh()->iIDItinerario);
            $this->assertFalse($demo->fresh()->lPublicarItinerario);
        } finally {
            $this->app['env'] = $environment;
        }
    }
}
