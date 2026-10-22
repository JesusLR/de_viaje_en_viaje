<?php

namespace Database\Seeders;

use App\Models\{Itinerario, ServicioTuristico};
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ItinerariosPruebaSeeder extends Seeder
{
    public function run(): void
    {
        if (!app()->environment('local')) {
            throw new \RuntimeException('Los itinerarios de ejemplo solo se pueden cargar en el entorno local.');
        }

        $totales = DB::transaction(function () {
            $this->call(ServiciosPruebaSeeder::class);
            $creados = 0;
            $asignados = 0;
            $ejemplos = [
                ['Cancún familiar', 'Cancún', 'Zona arqueológica de El Rey', 'Playa Delfines', [1, 2, 21]],
                ['Riviera Maya y cenotes', 'Playa del Carmen', 'Zona arqueológica de Tulum', 'Cenote de demostración', [3, 4]],
                ['Puerto Vallarta y su bahía', 'Puerto Vallarta', 'Malecón y centro histórico', 'Bahía de Banderas', [5, 6]],
                ['Los Cabos y el mar', 'Los Cabos', 'Centro de San José del Cabo', 'El Arco de Cabo San Lucas', [7, 8]],
                ['Capital cultural', 'Ciudad de México', 'Museo Nacional de Antropología', 'Bosque de Chapultepec', [9, 10]],
                ['Oaxaca y sus sabores', 'Oaxaca', 'Zona arqueológica de Monte Albán', 'Taller de artesanías', [11, 12]],
                ['Mérida y sus cenotes', 'Mérida', 'Centro histórico de Mérida', 'Ruta de cenotes', [13, 14, 22]],
                ['Chiapas y pueblos mágicos', 'San Cristóbal de las Casas', 'Centro histórico y mercados', 'Pueblos de los Altos de Chiapas', [15, 16]],
                ['Guanajuato colonial', 'Guanajuato', 'Teatro Juárez y plazas', 'Mirador del Pípila', [17, 18]],
                ['Puebla y Cholula', 'Puebla', 'Centro histórico de Puebla', 'Zona arqueológica de Cholula', [19, 20, 23]],
            ];

            foreach ($ejemplos as [$nombre, $ciudad, $cultura, $naturaleza, $numeros]) {
                $itinerario = Itinerario::firstOrCreate(['nombre' => 'DEMO - '.$nombre.' (Prueba)']);
                if ($itinerario->wasRecentlyCreated) {
                    $creados++;
                    $dias = [
                        ['Llegada y bienvenida', 'Hotel en '.$ciudad, 'Recepción y traslado al hotel', 'Registro en el hotel', 'Reunión de bienvenida'],
                        ['Historia y cultura', $cultura, 'Desayuno en el hotel', 'Recorrido cultural con guía', 'Tiempo libre para fotografías'],
                        ['Naturaleza y experiencias', $naturaleza, 'Salida en transporte turístico', 'Visita y actividades del recorrido', 'Regreso al hotel y descanso'],
                        ['Sabores y tradiciones', 'Mercado y restaurantes de '.$ciudad, 'Desayuno en el hotel', 'Paseo gastronómico', 'Visita a comercios de artesanías'],
                        ['Día libre y convivencia', $ciudad, 'Desayuno en el hotel', 'Tiempo libre para actividades personales', 'Encuentro del grupo'],
                        ['Despedida y regreso', 'Hotel y punto de salida en '.$ciudad, 'Desayuno y preparación del equipaje', 'Entrega de habitaciones', 'Traslado al punto de regreso'],
                    ];
                    foreach ($dias as $index => [$titulo, $lugar, $primera, $segunda, $tercera]) {
                        $dia = $itinerario->dias()->create([
                            'numero' => $index + 1, 'titulo' => $titulo,
                            'descripcion' => 'Recorrido ficticio en '.$ciudad.' para conocer el funcionamiento del sistema. Horarios y actividades de demostración.',
                        ]);
                        foreach ([$primera, $segunda, $tercera] as $orden => $actividad) {
                            $dia->actividades()->create([
                                'orden' => $orden + 1, 'hora' => ['08:00', '11:00', '16:00'][$orden],
                                'titulo' => $actividad, 'lugar' => $lugar,
                                'descripcion' => 'Presentarse en el punto de encuentro con ropa cómoda y seguir las indicaciones del guía.',
                                'incluidos' => $index === 4 ? 'Acompañamiento del coordinador. Consumos personales por cuenta del viajero.' : 'Coordinación del grupo y transporte en las actividades programadas.',
                                'notas_internas' => 'Ejemplo local: confirmar horarios y proveedor antes de una operación real.',
                            ]);
                        }
                    }
                }

                $codigos = array_map(fn ($n) => 'DEMO-SERV-'.str_pad($n, 3, '0', STR_PAD_LEFT), $numeros);
                $asignados += ServicioTuristico::whereIn('cCodigo', $codigos)->where('cNombre', 'like', '%(Prueba)')
                    ->whereNull('iIDItinerario')->update(['iIDItinerario' => $itinerario->id, 'lPublicarItinerario' => true]);
            }

            return [$creados, $asignados];
        });

        $this->command?->info($totales[0].' itinerarios de ejemplo creados y '.$totales[1].' servicios de prueba asignados. Las asignaciones existentes se conservan.');
    }
}
