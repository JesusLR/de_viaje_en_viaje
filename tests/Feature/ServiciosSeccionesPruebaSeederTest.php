<?php

namespace Tests\Feature;

use App\Models\ServicioTuristico;
use Database\Seeders\ServiciosSeccionesPruebaSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ServiciosSeccionesPruebaSeederTest extends TestCase
{
    use DatabaseTransactions;

    public function test_muestras_solo_locales_no_se_duplican_ni_sobrescriben_ediciones(): void
    {
        $entorno = $this->app['env'];
        try {
            $this->app['env'] = 'production';
            try {
                app(ServiciosSeccionesPruebaSeeder::class)->run();
                $this->fail('No debe permitir datos de muestra en producción.');
            } catch (\RuntimeException $exception) {
                $this->assertStringContainsString('solo se pueden cargar en el entorno local', $exception->getMessage());
            }
            $this->app['env'] = 'local';
            app(ServiciosSeccionesPruebaSeeder::class)->run();
            $muestras = ServicioTuristico::where('cCodigo', 'like', 'DEMO-SECCIONES-%')->get();
            $this->assertCount(3, $muestras);
            foreach ($muestras as $muestra) {
                foreach (['cDescripcionCompleta', 'cDetalles', 'cCancelaciones', 'cIncluye', 'cNoIncluye'] as $campo) {
                    $this->assertNotEmpty($muestra->{$campo});
                }
                $this->assertTrue($muestra->lActivo);
                $this->assertFalse($muestra->lUsaVigencia);
            }
            $muestras->first()->update(['cDetalles' => 'Edición local del usuario']);
            app(ServiciosSeccionesPruebaSeeder::class)->run();
            $this->assertSame(3, ServicioTuristico::where('cCodigo', 'like', 'DEMO-SECCIONES-%')->count());
            $this->assertSame('Edición local del usuario', $muestras->first()->fresh()->cDetalles);
        } finally {
            $this->app['env'] = $entorno;
        }
    }
}
