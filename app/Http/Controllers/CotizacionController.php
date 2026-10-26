<?php

namespace App\Http\Controllers;

use App\Models\{Cotizacion, LandingSetting};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\{Rule, ValidationException};

class CotizacionController extends Controller
{
    public function formulario()
    {
        $settings = LandingSetting::current();
        $content = $settings->values();
        $whatsappUrl = $content['whatsapp'] ? 'https://wa.me/'.$content['whatsapp'] : null;
        return view('publico.cotizacion', compact('settings', 'content', 'whatsappUrl'));
    }

    public function enviar(Request $request)
    {
        $data = $request->validate([
            'nombre' => 'required|string|max:150',
            'whatsapp' => ['required', 'string', 'max:30', 'regex:/^\+?[0-9 ()-]{7,30}$/'],
            'fechas' => 'required|string|max:255',
            'dias' => 'required|integer|min:1|max:365', 'noches' => 'required|integer|min:0|max:365|lte:dias',
            'personas' => 'required|integer|min:1|max:500',
            'adultos' => 'required|integer|min:1|max:500', 'menores' => 'required|integer|min:0|max:100',
            'edades' => 'nullable|string|max:500', 'hotel' => 'nullable|string|max:255',
            'tipo_hotel' => 'required|string|max:255',
            'ambiente' => ['required', Rule::in(['Familiar', 'Solo adultos', 'Ambos'])],
            'presupuesto' => 'required|numeric|decimal:0,2|min:0.01|max:9999999999.99',
            'moneda' => ['required', Rule::in(['MXN', 'USD', 'EUR'])],
        ], [], ['nombre' => 'nombre completo', 'whatsapp' => 'número de WhatsApp', 'fechas' => 'fechas de viaje', 'tipo_hotel' => 'tipo de hotel']);
        if ((int) $data['personas'] !== (int) $data['adultos'] + (int) $data['menores']) {
            throw ValidationException::withMessages(['personas' => 'El total debe coincidir con la suma de adultos y menores.']);
        }
        $edades = trim($data['edades'] ?? '') === '' ? [] : array_map('trim', explode(',', $data['edades']));
        if (count($edades) !== (int) $data['menores'] || collect($edades)->contains(fn ($edad) => !ctype_digit($edad) || (int) $edad > 17)) {
            throw ValidationException::withMessages(['edades' => 'Indica una edad de 0 a 17 años por cada menor, separadas por comas. Si no viajan menores, deja este campo vacío.']);
        }
        unset($data['edades']);
        DB::transaction(function () use ($data, $edades) {
            $cotizacion = Cotizacion::create($data + ['edades_menores' => array_map('intval', $edades), 'estado' => 'NUEVA']);
            \App\Models\User::where('lActivo', true)->whereHas('roles', fn ($roles) => $roles
                ->where('slug', 'administrador')->orWhereJsonContains('permissions', 'cotizaciones.ver'))
                ->chunkById(100, function ($usuarios) use ($cotizacion) {
                    \Illuminate\Support\Facades\Notification::send($usuarios, new \App\Notifications\CotizacionRecibida($cotizacion));
                });
        });
        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => '¡Tu cotización fue enviada con éxito! Pronto nos pondremos en contacto contigo.'], 201);
        }
        return redirect()->route('cotizacion.formulario')->with('cotizacion_enviada', true);
    }

    public function index(Request $request)
    {
        $request->merge([
            'desde' => $request->input('desde') ?: today()->startOfMonth()->toDateString(),
            'hasta' => $request->input('hasta') ?: today()->endOfMonth()->toDateString(),
        ]);
        $request->validate([
            'buscar' => 'nullable|string|max:150',
            'estado' => ['nullable', Rule::in(array_keys(Cotizacion::ESTADOS))],
            'desde' => 'nullable|date_format:Y-m-d',
            'hasta' => ['nullable', 'date_format:Y-m-d', ...($request->filled('desde') ? ['after_or_equal:desde'] : [])],
        ]);
        $cotizaciones = Cotizacion::with('responsable')
            ->when($request->filled('desde'), fn ($q) => $q->whereDate('created_at', '>=', $request->desde))
            ->when($request->filled('hasta'), fn ($q) => $q->whereDate('created_at', '<=', $request->hasta))
            ->when($request->filled('estado'), fn ($q) => $q->where('estado', $request->estado))
            ->when($request->filled('buscar'), fn ($q) => $q->where(fn ($q) => $q->where('nombre', 'like', '%'.$request->buscar.'%')->orWhere('whatsapp', 'like', '%'.$request->buscar.'%')))
            ->orderByDesc('id')->paginate(10)->withQueryString();
        return view('cotizaciones.index', compact('cotizaciones'));
    }

    public function show(Cotizacion $cotizacion)
    {
        $cotizacion->load(['responsable', 'observaciones.usuario']);
        return view('cotizaciones.show', compact('cotizacion'));
    }

    public function seguimiento(Request $request, Cotizacion $cotizacion)
    {
        $data = $request->validate([
            'estado' => ['required', Rule::in(['EN_ATENCION', 'CANCELADO', 'ATENDIDO'])],
            'observacion' => 'nullable|string|max:5000',
        ]);
        DB::transaction(function () use ($request, $cotizacion, $data) {
            $actual = Cotizacion::lockForUpdate()->findOrFail($cotizacion->id);
            if (in_array($actual->estado, ['ATENDIDO', 'CANCELADO'])) {
                throw ValidationException::withMessages(['estado' => 'Esta cotización ya está cerrada.']);
            }
            $nota = trim($data['observacion'] ?? '');
            if ($data['estado'] === 'ATENDIDO' && ($nota === '' || $actual->estado !== 'EN_ATENCION')) {
                throw ValidationException::withMessages(['observacion' => 'Primero inicia la atención y escribe una observación final para marcarla como atendida.']);
            }
            $cambios = ['estado' => $data['estado']];
            if (!$actual->atendida_por && $data['estado'] === 'EN_ATENCION') $cambios['atendida_por'] = $request->user()->id;
            if ($data['estado'] === 'ATENDIDO') $cambios += ['observacion_final' => $nota, 'atendida_at' => now()];
            $actual->update($cambios);
            $actual->observaciones()->create(['usuario_id' => $request->user()->id, 'estado' => $data['estado'], 'observacion' => $nota ?: null]);
        });
        return redirect()->route('cotizaciones.show', $cotizacion)->with('success', 'Seguimiento guardado correctamente.');
    }
}
