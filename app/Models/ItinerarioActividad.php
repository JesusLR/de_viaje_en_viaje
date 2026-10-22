<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class ItinerarioActividad extends Model {
 protected $table = 'servicio_itinerario_actividades';
 public $timestamps = false;
 protected $guarded = ['id'];
}
