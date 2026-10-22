<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('itinerarios', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->timestamps();
        });
        Schema::table('servicio_turisticos', function (Blueprint $table) {
            $table->foreignId('iIDItinerario')->nullable()->constrained('itinerarios')->restrictOnDelete();
        });
        Schema::table('servicio_itinerario_dias', function (Blueprint $table) {
            $table->unsignedBigInteger('servicio_id')->nullable()->change();
            $table->foreignId('itinerario_id')->nullable()->constrained('itinerarios')->cascadeOnDelete();
            $table->unique(['itinerario_id', 'numero']);
        });
        DB::transaction(function () {
            foreach (DB::table('servicio_itinerario_dias')->whereNotNull('servicio_id')->distinct()->pluck('servicio_id') as $serviceId) {
                $service = DB::table('servicio_turisticos')->where('iID', $serviceId)->first();
                $id = DB::table('itinerarios')->insertGetId([
                    'nombre' => mb_substr($service->cNombre.' · '.$service->cCodigo, 0, 255),
                    'created_at' => now(), 'updated_at' => now(),
                ]);
                DB::table('servicio_itinerario_dias')->where('servicio_id', $serviceId)->update(['itinerario_id' => $id, 'servicio_id' => null]);
                DB::table('servicio_turisticos')->where('iID', $serviceId)->update(['iIDItinerario' => $id]);
            }
        });
    }

    public function down(): void
    {
        DB::transaction(function () {
            foreach (DB::table('servicio_turisticos')->whereNotNull('iIDItinerario')->get() as $service) {
                foreach (DB::table('servicio_itinerario_dias')->where('itinerario_id', $service->iIDItinerario)->get() as $day) {
                    $id = DB::table('servicio_itinerario_dias')->insertGetId([
                        'servicio_id' => $service->iID, 'numero' => $day->numero,
                        'titulo' => $day->titulo, 'descripcion' => $day->descripcion,
                    ]);
                    foreach (DB::table('servicio_itinerario_actividades')->where('dia_id', $day->id)->get() as $activity) {
                        $values = (array) $activity;
                        unset($values['id']);
                        $values['dia_id'] = $id;
                        DB::table('servicio_itinerario_actividades')->insert($values);
                    }
                }
            }
            DB::table('servicio_itinerario_dias')->whereNotNull('itinerario_id')->delete();
        });
        Schema::table('servicio_turisticos', fn (Blueprint $table) => $table->dropConstrainedForeignId('iIDItinerario'));
        Schema::table('servicio_itinerario_dias', function (Blueprint $table) {
            $table->dropUnique(['itinerario_id', 'numero']);
            $table->dropConstrainedForeignId('itinerario_id');
            $table->unsignedBigInteger('servicio_id')->nullable(false)->change();
        });
        Schema::dropIfExists('itinerarios');
    }
};
