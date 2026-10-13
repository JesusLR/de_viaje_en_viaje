<?php

namespace Database\Seeders;

use App\Models\CategoriaTuristica;
use App\Models\DestinoTuristico;
use App\Models\Proveedor;
use App\Models\ServicioTuristico;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ServiciosPruebaSeeder extends Seeder
{
    public function run(): void
    {
        $creados = DB::transaction(function () {
            $categorias = collect(['Playa', 'Cultura', 'Aventura', 'Escapadas'])->mapWithKeys(function ($nombre) {
                return [$nombre => CategoriaTuristica::firstOrCreate(['cNombre' => $nombre.' (Prueba)'], [
                    'cDescripcion' => 'Categoría de demostración para servicios de prueba.', 'lActivo' => true,
                ])];
            });
            $proveedores = collect(['Operadora Costa', 'Rutas y Experiencias', 'Viajes de Fin de Semana'])->map(function ($nombre) {
                return Proveedor::firstOrCreate(['cNombre' => $nombre.' (Prueba)'], [
                    'cDescripcion' => 'Proveedor ficticio para pruebas del sistema.', 'lActivo' => true,
                ]);
            })->values();
            $destinos = [];
            foreach ([
                ['Cancún', 'Quintana Roo'], ['Playa del Carmen', 'Quintana Roo'], ['Puerto Vallarta', 'Jalisco'],
                ['Los Cabos', 'Baja California Sur'], ['Ciudad de México', 'Ciudad de México'],
                ['Oaxaca', 'Oaxaca'], ['Mérida', 'Yucatán'], ['San Cristóbal de las Casas', 'Chiapas'],
                ['Guanajuato', 'Guanajuato'], ['Puebla', 'Puebla'],
            ] as [$ciudad, $estado]) {
                $destinos[$ciudad] = DestinoTuristico::firstOrCreate([
                    'cNombre' => $ciudad.' (Prueba)', 'cPais' => 'México', 'cEstado' => $estado, 'cCiudad' => $ciudad,
                ], ['cDescripcion' => 'Destino de demostración.', 'lActivo' => true]);
            }
            $servicios = [
                ['Cancún todo incluido', 'Playa', 'Cancún'], ['Cancún familiar', 'Playa', 'Cancún'],
                ['Riviera Maya y cenotes', 'Aventura', 'Playa del Carmen'], ['Escapada a Playa del Carmen', 'Playa', 'Playa del Carmen'],
                ['Puerto Vallarta romántico', 'Playa', 'Puerto Vallarta'], ['Aventura en Bahía de Banderas', 'Aventura', 'Puerto Vallarta'],
                ['Los Cabos premium', 'Playa', 'Los Cabos'], ['Paseo al Arco de Cabo San Lucas', 'Aventura', 'Los Cabos'],
                ['Ciudad de México cultural', 'Cultura', 'Ciudad de México'], ['Museos y sabores de la capital', 'Cultura', 'Ciudad de México'],
                ['Oaxaca gastronómico', 'Cultura', 'Oaxaca'], ['Monte Albán y artesanías', 'Cultura', 'Oaxaca'],
                ['Mérida colonial', 'Escapadas', 'Mérida'], ['Ruta de cenotes en Yucatán', 'Aventura', 'Mérida'],
                ['San Cristóbal mágico', 'Escapadas', 'San Cristóbal de las Casas'], ['Naturaleza y pueblos de Chiapas', 'Aventura', 'San Cristóbal de las Casas'],
                ['Guanajuato de fin de semana', 'Escapadas', 'Guanajuato'], ['Historia y callejones de Guanajuato', 'Cultura', 'Guanajuato'],
                ['Puebla y Cholula', 'Escapadas', 'Puebla'], ['Sabores y tradiciones de Puebla', 'Cultura', 'Puebla'],
            ];
            $cantidad = 0;
            foreach ($servicios as $indice => [$nombre, $categoria, $destino]) {
                $numero = $indice + 1;
                $precio = 3000 + $numero * 450;
                $promocion = $numero % 3 !== 0;
                $porcentaje = $numero % 2 === 0;
                $servicio = ServicioTuristico::firstOrCreate([
                    'cCodigo' => 'DEMO-SERV-'.str_pad($numero, 3, '0', STR_PAD_LEFT),
                ], [
                    'cNombre' => $nombre.' (Prueba)',
                    'cDescripcionCorta' => 'Servicio ficticio para probar ventas, apartados y promociones.',
                    'cDescripcionCompleta' => 'Datos de demostración. Incluye alojamiento, traslados y actividades simuladas. No representa una oferta comercial real.',
                    'iIDCategoria' => $categorias[$categoria]->iID, 'iIDDestino' => $destinos[$destino]->iID,
                    'iIDProveedor' => $proveedores[$indice % 3]->iID,
                    'dPrecioCompra' => round($precio * 0.65, 2), 'dPrecioVenta' => $precio, 'cMoneda' => 'MXN',
                    'lTienePromocion' => $promocion, 'lPromocionActiva' => $promocion,
                    'cNombrePromocion' => $promocion ? ($porcentaje ? 'Descuento del 10% (Prueba)' : 'Descuento de $500 (Prueba)') : null,
                    'cDescripcionPromocion' => $promocion ? 'Promoción ficticia de demostración.' : null,
                    'cTipoDescuento' => $promocion ? ($porcentaje ? 'PORCENTAJE' : 'IMPORTE') : null,
                    'dValorDescuento' => $promocion ? ($porcentaje ? 10 : 500) : 0,
                    'dFechaInicioPromocion' => $promocion ? today()->startOfDay() : null,
                    'dFechaFinPromocion' => $promocion ? today()->addDays(30)->endOfDay() : null,
                    'dFechaInicioVigencia' => today(), 'dFechaFinVigencia' => today()->addDays(30 + $numero),
                    'lActivo' => true,
                ]);
                if ($servicio->wasRecentlyCreated) $cantidad++;
            }

            return $cantidad;
        });
        $this->command?->info($creados.' servicios de prueba creados. Los registros existentes se conservan.');
    }
}
