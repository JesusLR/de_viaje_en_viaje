<?php

namespace Tests\Feature;

use App\Models\{CategoriaTuristica, Cliente, DestinoTuristico, Proveedor, ServicioTuristico};
use Database\Seeders\ServiciosPruebaSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ServiciosPruebaSeederTest extends TestCase
{
    use DatabaseTransactions;

    public function test_demo_data_is_local_only_and_does_not_duplicate_records(): void
    {
        $environment = $this->app['env'];
        try {
            $this->app['env'] = 'production';
            try {
                app(ServiciosPruebaSeeder::class)->run();
                $this->fail('La carga de ejemplos debe estar bloqueada en producción.');
            } catch (\RuntimeException $exception) {
                $this->assertStringContainsString('solo se pueden cargar en el entorno local', $exception->getMessage());
            }

            $this->app['env'] = 'local';
            app(ServiciosPruebaSeeder::class)->run();
            $counts = $this->counts();
            app(ServiciosPruebaSeeder::class)->run();
            $this->assertSame($counts, $this->counts());
            $this->assertSame(23, ServicioTuristico::where('cCodigo', 'like', 'DEMO-SERV-%')->count());
            $free = ServicioTuristico::where('cCodigo', 'DEMO-SERV-021')->firstOrFail();
            $this->assertTrue($free->lPrecioMenorActivo);
            $this->assertEquals(0, $free->dPrecioMenor);
            $paid = ServicioTuristico::where('cCodigo', 'DEMO-SERV-022')->firstOrFail();
            $this->assertTrue($paid->lPrecioMenorActivo);
            $this->assertEquals(850, $paid->dPrecioMenor);
            $this->assertFalse(ServicioTuristico::where('cCodigo', 'DEMO-SERV-023')->firstOrFail()->lPrecioMenorActivo);
        } finally {
            $this->app['env'] = $environment;
        }
    }

    private function counts(): array
    {
        return [ServicioTuristico::count(), CategoriaTuristica::count(), DestinoTuristico::count(), Proveedor::count(), Cliente::count()];
    }
}
