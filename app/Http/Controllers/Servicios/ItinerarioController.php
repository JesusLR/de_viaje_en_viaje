<?php

namespace App\Http\Controllers\Servicios;

use App\Http\Controllers\Controller;
use App\Models\Itinerario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Arr;

class ItinerarioController extends Controller
{
    public function index(Request $request)
    {
        $request->validate(['buscar' => 'nullable|string|max:150']);
        $itinerarios = Itinerario::withCount(['dias', 'servicios'])
            ->when($request->filled('buscar'), fn ($q) => $q->where('nombre', 'like', '%'.$request->buscar.'%'))
            ->orderBy('nombre')->paginate(10)->withQueryString();
        return view('servicios.itinerario.index', compact('itinerarios'));
    }

    public function create(Request $request)
    {
        abort_unless($request->user()->hasPermission('itinerarios.editar'), 403);
        return $this->editor($request, new Itinerario());
    }

    public function edit(Request $request, Itinerario $itinerario)
    {
        return $this->editor($request, $itinerario);
    }

    private function editor(Request $request, Itinerario $itinerario)
    {
        $request->validate(['copiar' => 'nullable|integer|exists:itinerarios,id']);
        $origen = $itinerario;
        if ($request->filled('copiar')) {
            abort_unless($request->user()->hasPermission('itinerarios.editar'), 403);
            $origen = Itinerario::findOrFail($request->copiar);
        }
        $dias = $origen->exists ? $origen->snapshot() : [];
        $opciones = Itinerario::when($itinerario->exists, fn ($q) => $q->where('id', '!=', $itinerario->id))->orderBy('nombre')->get(['id', 'nombre']);
        $servicios = $itinerario->exists ? $itinerario->servicios()->orderBy('cNombre')->get(['iID', 'cCodigo', 'cNombre']) : collect();
        return view('servicios.itinerario.edit', compact('itinerario', 'dias', 'opciones', 'servicios'));
    }

    public function store(Request $request)
    {
        abort_unless($request->user()->hasPermission('itinerarios.editar'), 403);
        return $this->save($request, new Itinerario());
    }

    public function update(Request $request, Itinerario $itinerario)
    {
        return $this->save($request, $itinerario);
    }

    private function save(Request $request, Itinerario $itinerario)
    {
        $request->validate(['nombre' => 'required|string|max:255', 'dias_json' => 'required|json']);
        $request->merge(['dias' => json_decode($request->dias_json, true)]);
        $data = $request->validate([
            'dias' => 'required|array|list|min:1|max:60',
            'dias.*.titulo' => 'required|string|max:255',
            'dias.*.descripcion' => 'nullable|string|max:5000',
            'dias.*.actividades' => 'present|array|list|max:100',
            'dias.*.actividades.*.titulo' => 'required|string|max:255',
            'dias.*.actividades.*.hora' => 'nullable|date_format:H:i',
            'dias.*.actividades.*.descripcion' => 'nullable|string|max:5000',
            'dias.*.actividades.*.lugar' => 'nullable|string|max:255',
            'dias.*.actividades.*.incluidos' => 'nullable|string|max:5000',
            'dias.*.actividades.*.notas_internas' => 'nullable|string|max:5000',
        ]);
        $itinerario = DB::transaction(function () use ($request, $itinerario, $data) {
            if ($itinerario->exists) $itinerario = Itinerario::whereKey($itinerario->id)->lockForUpdate()->firstOrFail();
            $itinerario->fill(['nombre' => trim($request->nombre)])->save();
            $itinerario->dias()->delete();
            foreach ($data['dias'] as $index => $dia) {
                $registro = $itinerario->dias()->create([
                    'numero' => $index + 1, 'titulo' => $dia['titulo'], 'descripcion' => $dia['descripcion'] ?? null,
                ]);
                foreach ($dia['actividades'] as $orden => $actividad) {
                    $registro->actividades()->create(Arr::only($actividad, ['hora', 'titulo', 'descripcion', 'lugar', 'incluidos', 'notas_internas']) + ['orden' => $orden + 1]);
                }
            }
            return $itinerario;
        });
        return redirect()->route('itinerarios.edit', $itinerario)->with('success', 'Itinerario guardado correctamente.');
    }
}
