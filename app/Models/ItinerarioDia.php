<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class ItinerarioDia extends Model {
 protected $table = 'servicio_itinerario_dias';
 public $timestamps = false;
 protected $guarded = ['id'];
 public function actividades() { return $this->hasMany(ItinerarioActividad::class, 'dia_id')->orderBy('orden'); }
}
