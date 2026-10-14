<?php

namespace App\Http\Controllers;

use App\Models\{CategoriaTuristica, DestinoTuristico, LandingSetting, ServicioTuristico};
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PublicCatalogController extends Controller
{
    private function branding(): array
    {
        $settings = LandingSetting::current();
        $content = $settings->values();
        return ['settings' => $settings, 'content' => $content, 'whatsappUrl' => $content['whatsapp'] ? 'https://wa.me/'.$content['whatsapp'] : null];
    }

    public function index(Request $request)
    {
        $monedas = ServicioTuristico::publicados()->distinct()->orderBy('cMoneda')->pluck('cMoneda');
        $filters = $request->validate([
            'buscar' => 'nullable|string|max:150',
            'destino' => 'nullable|integer|exists:destino_turisticos,iID',
            'categoria' => 'nullable|integer|exists:categoria_turisticas,iID',
            'desde' => 'nullable|date_format:Y-m-d',
            'hasta' => ['nullable', 'date_format:Y-m-d', ...($request->filled('desde') ? ['after_or_equal:desde'] : [])],
            'moneda' => ['nullable', Rule::in($monedas->all()), Rule::requiredIf($request->filled('minimo') || $request->filled('maximo') || in_array($request->orden, ['precio_asc', 'precio_desc']))],
            'minimo' => 'nullable|numeric|min:0|max:9999999999.99',
            'maximo' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99', ...($request->filled('minimo') ? ['gte:minimo'] : [])],
            'promocion' => 'nullable|boolean',
            'orden' => ['nullable', Rule::in(['proximos', 'recientes', 'precio_asc', 'precio_desc'])],
            'page' => 'nullable|integer|min:1',
        ], [
            'moneda.required' => 'Selecciona una moneda para filtrar u ordenar por precio.',
            'maximo.gte' => 'El precio máximo no puede ser menor que el mínimo.',
            'hasta.after_or_equal' => 'La fecha final no puede ser anterior a la inicial.',
        ]);
        $query = ServicioTuristico::with(['destino', 'categoria'])->publicados();
        if ($request->filled('buscar')) {
            $term = '%'.trim($filters['buscar']).'%';
            $query->where(fn ($q) => $q->where('cNombre', 'like', $term)->orWhere('cDescripcionCorta', 'like', $term)->orWhereHas('destino', fn ($d) => $d->where('cNombre', 'like', $term)->orWhere('cPais', 'like', $term)->orWhere('cCiudad', 'like', $term)));
        }
        foreach (['destino' => 'iIDDestino', 'categoria' => 'iIDCategoria', 'moneda' => 'cMoneda'] as $filter => $field) {
            if ($request->filled($filter)) $query->where($field, $filters[$filter]);
        }
        if ($request->filled('desde')) $query->whereDate('dFechaInicioViaje', '>=', $filters['desde']);
        if ($request->filled('hasta')) $query->whereDate('dFechaInicioViaje', '<=', $filters['hasta']);
        if ($request->boolean('promocion')) $query->where('lTienePromocion', true)->where('lPromocionActiva', true)->where('dFechaInicioPromocion', '<=', now())->where('dFechaFinPromocion', '>=', now());
        [$price, $bindings] = ServicioTuristico::publicPriceExpression();
        if ($request->filled('minimo')) $query->whereRaw($price.' >= ?', [...$bindings, $filters['minimo']]);
        if ($request->filled('maximo')) $query->whereRaw($price.' <= ?', [...$bindings, $filters['maximo']]);
        $order = $filters['orden'] ?? 'proximos';
        if (in_array($order, ['precio_asc', 'precio_desc'])) $query->orderByRaw($price.($order === 'precio_asc' ? ' asc' : ' desc'), $bindings);
        elseif ($order === 'recientes') $query->orderByDesc('created_at');
        else $query->orderByRaw('dFechaInicioViaje IS NULL')->orderBy('dFechaInicioViaje');
        $paquetes = $query->orderBy('iID')->paginate(12)->withQueryString();
        $destinos = DestinoTuristico::whereHas('servicios', fn ($q) => $q->publicados())->orderBy('cNombre')->get();
        $categorias = CategoriaTuristica::whereHas('servicios', fn ($q) => $q->publicados())->orderBy('cNombre')->get();
        return view('publico.catalogo', $this->branding() + compact('paquetes', 'destinos', 'categorias', 'monedas', 'filters'));
    }

    public function show(int $servicio)
    {
        $paquete = ServicioTuristico::with(['destino', 'categoria', 'imagenes'])->publicados()->findOrFail($servicio);
        return view('publico.servicio', $this->branding() + compact('paquete'));
    }
}
