<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Itinerario extends Model
{
    protected $table = 'itinerarios';
    protected $fillable = ['nombre'];

    public function dias()
    {
        return $this->hasMany(ItinerarioDia::class)->orderBy('numero');
    }

    public function servicios()
    {
        return $this->hasMany(ServicioTuristico::class, 'iIDItinerario');
    }

    public function snapshot(): array
    {
        return $this->dias()->with('actividades')->get()->map(fn ($dia) => [
            'numero' => $dia->numero, 'titulo' => $dia->titulo, 'descripcion' => $dia->descripcion,
            'actividades' => $dia->actividades->map(fn ($a) => $a->only(['hora', 'titulo', 'descripcion', 'lugar', 'incluidos', 'notas_internas']))->all(),
        ])->all();
    }
}
