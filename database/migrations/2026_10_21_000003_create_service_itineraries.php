<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void {
  Schema::table('servicio_turisticos', fn(Blueprint $t) => $t->boolean('lPublicarItinerario')->default(false));
  Schema::create('servicio_itinerario_dias', function(Blueprint $t) {
   $t->id(); $t->unsignedBigInteger('servicio_id');
   $t->foreign('servicio_id')->references('iID')->on('servicio_turisticos')->cascadeOnDelete();
   $t->unsignedSmallInteger('numero'); $t->string('titulo'); $t->text('descripcion')->nullable(); $t->unique(['servicio_id','numero']);
  });
  Schema::create('servicio_itinerario_actividades', function(Blueprint $t) {
   $t->id(); $t->foreignId('dia_id')->constrained('servicio_itinerario_dias')->cascadeOnDelete();
   $t->unsignedSmallInteger('orden'); $t->string('hora',5)->nullable(); $t->string('titulo');
   $t->text('descripcion')->nullable(); $t->string('lugar')->nullable(); $t->text('incluidos')->nullable(); $t->text('notas_internas')->nullable();
  });
  Schema::table('venta_paquete_detalles', fn(Blueprint $t) => $t->json('itinerario')->nullable());
 }
 public function down(): void {
  Schema::table('venta_paquete_detalles', fn(Blueprint $t) => $t->dropColumn('itinerario'));
  Schema::dropIfExists('servicio_itinerario_actividades'); Schema::dropIfExists('servicio_itinerario_dias');
  Schema::table('servicio_turisticos', fn(Blueprint $t) => $t->dropColumn('lPublicarItinerario'));
 }
};
