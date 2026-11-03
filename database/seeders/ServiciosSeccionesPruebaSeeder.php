<?php

namespace Database\Seeders;

use App\Models\ServicioTuristico;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

// Ejecución manual: php artisan db:seed --class=ServiciosSeccionesPruebaSeeder
// No agregar a DatabaseSeeder ni al arranque de Docker.
class ServiciosSeccionesPruebaSeeder extends Seeder
{
    public function run(): void
    {
        if (!app()->environment('local')) {
            throw new \RuntimeException('Los servicios por secciones de ejemplo solo se pueden cargar en el entorno local.');
        }

        DB::transaction(function () {
            $ejemplos = [
                ['Riviera Maya familiar', 8900,
                    'Disfruta cuatro días de playa y descanso en familia. Conoce el Caribe mexicano y comparte una experiencia tranquila con actividades para todas las edades.',
                    "Duración: 4 días y 3 noches.\nHospedaje: habitación estándar en hotel familiar.\nRegistro: 15:00 h. Salida: 12:00 h.\nPresentarse 30 minutos antes del traslado.\nLlevar identificación, traje de baño y protector solar.",
                    "Hasta 15 días antes: solicitar revisión de cancelación con la agencia.\nEntre 14 y 7 días: pueden aplicar cargos del proveedor.\nCon menos de 7 días o por no presentarse: no se garantiza reembolso.\nCondiciones ficticias de demostración.",
                    "3 noches de hospedaje.\nDesayuno diario.\nTraslado de llegada y salida.\nUna visita guiada a un cenote.\nAsistencia de la agencia.",
                    "Vuelos.\nImpuestos locales del hotel.\nPropinas y gastos personales.\nActividades opcionales y alimentos no indicados."],
                ['Oaxaca cultural y sabores', 5400,
                    'Una escapada para descubrir la cultura, gastronomía y artesanías de Oaxaca. Recorre sus calles y conoce las tradiciones de sus comunidades.',
                    "Duración: 3 días y 2 noches.\nPunto de encuentro: centro de Oaxaca, 08:00 h.\nNivel de actividad: caminata moderada.\nSe recomienda calzado cómodo y sombrero.\nInformar alergias alimentarias antes del viaje.",
                    "Las solicitudes deben enviarse por escrito a la agencia.\nLos cambios de fecha están sujetos a disponibilidad.\nLas entradas y experiencias confirmadas pueden tener cargos no reembolsables.\nCondiciones ficticias de demostración.",
                    "2 noches de hospedaje.\nRecorrido por el centro histórico.\nVisita a un taller de artesanías.\nTransporte para los recorridos indicados.\nGuía durante las visitas.",
                    "Transporte desde la ciudad de origen.\nComidas y bebidas.\nCompras de artesanías.\nPropinas y servicios adicionales."],
                ['Aventura entre cenotes', 1950,
                    'Pasa un día en contacto con la naturaleza y disfruta de aguas cristalinas. Una experiencia para quienes buscan explorar y relajarse fuera de la ciudad.',
                    "Duración: excursión de un día, de 08:00 a 18:00 h.\nSalida desde el punto de reunión indicado por la agencia.\nEdad sugerida: a partir de 8 años, con acompañante adulto para menores.\nLlevar traje de baño, toalla y cambio de ropa.\nLas actividades pueden ajustarse por condiciones meteorológicas.",
                    "Avisar con al menos 48 horas de anticipación para solicitar un cambio.\nLas cancelaciones fuera de plazo pueden generar cargos.\nSi el operador suspende por clima, consultar las alternativas de reprogramación.\nCondiciones ficticias de demostración.",
                    "Transporte de ida y regreso desde el punto de reunión.\nEntrada a dos cenotes.\nChaleco salvavidas.\nGuía del recorrido.\nUna botella de agua.",
                    "Hospedaje.\nAlimentos.\nRenta de equipo adicional.\nFotografías profesionales.\nGastos personales y propinas."],
            ];
            foreach ($ejemplos as $indice => [$nombre, $precio, $descripcion, $detalles, $cancelaciones, $incluye, $noIncluye]) {
                $servicio = ServicioTuristico::firstOrCreate(['cCodigo' => 'DEMO-SECCIONES-'.str_pad($indice + 1, 3, '0', STR_PAD_LEFT)], [
                    'cNombre' => $nombre.' (Muestra local)',
                    'cDescripcionCorta' => 'Servicio ficticio para visualizar las nuevas secciones del catálogo.',
                    'cDescripcionCompleta' => $descripcion,
                    'cDetalles' => $detalles, 'cCancelaciones' => $cancelaciones, 'cIncluye' => $incluye, 'cNoIncluye' => $noIncluye,
                    'dPrecioCompra' => round($precio * 0.65, 2), 'dPrecioVenta' => $precio, 'cMoneda' => 'MXN',
                    'lPrecioMenorActivo' => $indice === 0, 'dPrecioMenor' => $indice === 0 ? 4500 : 0,
                    'lActivo' => true, 'lUsaVigencia' => false,
                    'dFechaInicioViaje' => today()->addDays(30 + $indice), 'dFechaFinViaje' => today()->addDays(30 + $indice + [3, 2, 0][$indice]),
                ]);
                $this->command?->info($servicio->cNombre.' · '.route('catalogo.publico.show', $servicio->iID));
            }
        });
    }
}
