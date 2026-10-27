<?php

namespace App\Http\Controllers;

use App\Models\{ServicioTuristico, User};
use App\Services\EstadisticasService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class EstadisticasController extends Controller
{
    public function index(Request $request, EstadisticasService $estadisticas)
    {
        $puedeTodos = $request->user()->hasPermission('estadisticas.todos');
        $historico = $request->input('periodo') === 'historico';
        $request->merge([
            'periodo' => $request->input('periodo') ?: 'rango',
            'desde' => $historico ? today()->startOfMonth()->toDateString() : ($request->input('desde') ?: today()->startOfMonth()->toDateString()),
            'hasta' => $historico ? today()->endOfMonth()->toDateString() : ($request->input('hasta') ?: today()->endOfMonth()->toDateString()),
            'moneda' => $request->input('moneda') ?: 'MXN',
            'usuario' => $puedeTodos ? $request->input('usuario') : $request->user()->id,
        ]);
        $filtros = $request->validate([
            'periodo' => 'required|in:rango,historico',
            'desde' => 'required|date_format:Y-m-d', 'hasta' => 'required|date_format:Y-m-d|after_or_equal:desde',
            'moneda' => 'required|string|regex:/^[A-Z]{3}$/',
            'usuario' => 'nullable|integer|exists:users,id', 'servicio' => 'nullable|integer|exists:servicio_turisticos,iID',
        ]);
        if (!$historico && Carbon::parse($filtros['hasta'])->startOfMonth()->diffInMonths(Carbon::parse($filtros['desde'])->startOfMonth(), true) >= 24) {
            throw ValidationException::withMessages(['hasta' => 'Selecciona un periodo de hasta 24 meses.']);
        }
        $verFinanzas = $request->user()->hasPermission('estadisticas.finanzas');
        $verCotizaciones = $request->user()->hasPermission('estadisticas.cotizaciones');
        if ($historico) {
            $filtros = array_merge($filtros, $estadisticas->rangoHistorico($filtros, $verFinanzas, $verCotizaciones));
            $request->merge(['desde' => $filtros['desde'], 'hasta' => $filtros['hasta']]);
        }
        $datos = $estadisticas->resumen($filtros, $verFinanzas, $verCotizaciones);
        $usuarios = $puedeTodos ? User::where('lDev', false)->orderBy('name')->get() : collect([$request->user()]);
        $servicios = ServicioTuristico::orderBy('cNombre')->get(['iID', 'cNombre', 'cCodigo']);
        $monedas = collect(['MXN', 'USD', 'EUR', $filtros['moneda']])->merge(ServicioTuristico::distinct()->pluck('cMoneda'))->unique()->sort();
        return view('estadisticas.index', compact('datos', 'filtros', 'puedeTodos', 'usuarios', 'servicios', 'monedas', 'verFinanzas', 'verCotizaciones'));
    }
}
