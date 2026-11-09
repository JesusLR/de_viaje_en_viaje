<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void { Schema::table('ventas_paquetes',function(Blueprint $t){$t->string('cTipoPromocionVenta',20)->nullable();$t->decimal('dValorPromocionVenta',14,2)->default(0);$t->decimal('dPromocionVenta',14,2)->default(0);}); }
 public function down(): void { Schema::table('ventas_paquetes',fn(Blueprint $t)=>$t->dropColumn(['cTipoPromocionVenta','dValorPromocionVenta','dPromocionVenta'])); }
};
