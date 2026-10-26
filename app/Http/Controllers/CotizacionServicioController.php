<?php

namespace App\Http\Controllers;

use App\Models\{Cliente, CotizacionServicio, LandingSetting, ServicioTuristico};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\{Rule, ValidationException};
use Barryvdh\DomPDF\Facade\Pdf;

class CotizacionServicioController extends Controller
{
    public function index(Request $request)
    {
        $request->merge([
            'desde' => $request->input('desde') ?: today()->startOfMonth()->toDateString(),
            'hasta' => $request->input('hasta') ?: today()->endOfMonth()->toDateString(),
        ]);
        $request->validate([
            'buscar' => 'nullable|string|max:150',
            'desde' => 'required|date_format:Y-m-d',
            'hasta' => 'required|date_format:Y-m-d|after_or_equal:desde',
        ]);
        $cotizaciones = CotizacionServicio::with('usuario')->whereDate('created_at', '>=', $request->desde)->whereDate('created_at', '<=', $request->hasta)->when($request->filled('buscar'), fn ($q) => $q->where(fn ($q) => $q
            ->where('cliente_nombre', 'like', '%'.$request->buscar.'%')->orWhere('nombre', 'like', '%'.$request->buscar.'%')))
            ->orderByDesc('id')->paginate(10)->withQueryString();
        return view('atencion.cotizaciones.index', compact('cotizaciones'));
    }

    public function create()
    {
        $clientes = Cliente::where('lActivo', true)->orderBy('cNombre')->get();
        $paquetes = ServicioTuristico::publicados()->orderBy('cNombre')->get();
        return view('atencion.cotizaciones.create', compact('clientes', 'paquetes'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'tipo_cliente' => ['required', Rule::in(['registrado', 'generico'])],
            'iIDCliente' => ['exclude_unless:tipo_cliente,registrado', 'required', 'integer', Rule::exists('clientes', 'iID')->where('lActivo', true)],
            'cliente_nombre' => 'exclude_unless:tipo_cliente,generico|required|string|max:255',
            'cNombre' => 'nullable|string|max:150', 'cObservaciones' => 'nullable|string|max:2000',
            'items' => 'required|array|min:1|max:30',
            'items.*.iIDServicio' => 'required|integer|distinct|exists:servicio_turisticos,iID',
            'items.*.iCantidad' => 'required|integer|min:1|max:1000',
            'items.*.iCantidadMenores' => 'sometimes|integer|min:0|max:1000',
        ]);
        $cotizacion = DB::transaction(function () use ($data, $request) {
            $cliente = $data['tipo_cliente'] === 'registrado' ? Cliente::where('lActivo', true)->lockForUpdate()->findOrFail($data['iIDCliente']) : null;
            $partidas = []; $moneda = null; $total = 0;
            foreach (collect($data['items'])->sortBy('iIDServicio') as $item) {
                $servicio = ServicioTuristico::lockForUpdate()->findOrFail($item['iIDServicio']);
                if ($servicio->c_estatus_disponibilidad !== 'ACTIVO') throw ValidationException::withMessages(['items' => 'El servicio '.$servicio->cNombre.' ya no está activo.']);
                if ($moneda !== null && $moneda !== $servicio->cMoneda) throw ValidationException::withMessages(['items' => 'Los servicios deben tener la misma moneda.']);
                $moneda = $servicio->cMoneda;
                $adultos = (int) $item['iCantidad']; $menores = (int) ($item['iCantidadMenores'] ?? 0);
                if ($menores && !$servicio->lPrecioMenorActivo) throw ValidationException::withMessages(['items' => 'El servicio '.$servicio->cNombre.' no admite tarifa para menores.']);
                $precio = (int) round($servicio->d_precio_final * 100);
                $regular = (int) round($servicio->dPrecioVenta * 100);
                $precioMenor = $servicio->lPrecioMenorActivo ? (int) round($servicio->dPrecioMenor * 100) : null;
                $subtotal = $precio * $adultos + ($precioMenor ?? 0) * $menores;
                $total += $subtotal;
                $partidas[] = ['servicio_id' => $servicio->iID, 'nombre' => $servicio->cNombre,
                    'inicio' => $servicio->dFechaInicioViaje?->format('d/m/Y'), 'fin' => $servicio->dFechaFinViaje?->format('d/m/Y'),
                    'adultos' => $adultos, 'menores' => $menores, 'precio' => $precio / 100, 'precio_menor' => $precioMenor === null ? null : $precioMenor / 100,
                    'regular' => $regular / 100, 'descuento' => max(0, $regular - $precio) / 100,
                    'promocion' => $servicio->l_promocion_vigente ? ($servicio->cNombrePromocion ?: 'Promoción vigente') : null, 'subtotal' => $subtotal / 100];
            }
            if ($total > 999999999999) throw ValidationException::withMessages(['items' => 'El total excede el importe permitido.']);
            return CotizacionServicio::create(['usuario_id' => $request->user()->id, 'cliente_id' => $cliente?->iID,
                'cliente_nombre' => $cliente?->nombre_completo ?? $data['cliente_nombre'], 'nombre' => $data['cNombre'] ?? null,
                'observaciones' => $data['cObservaciones'] ?? null, 'moneda' => $moneda, 'total' => $total / 100, 'partidas' => $partidas]);
        });
        return redirect()->route('atencion.cotizaciones.show', $cotizacion)->with('success', 'Cotización guardada correctamente.');
    }

    public function show(CotizacionServicio $cotizacion)
    {
        $cotizacion->load('usuario');
        return view('atencion.cotizaciones.show', compact('cotizacion'));
    }

    public function pdf(CotizacionServicio $cotizacion)
    {
        $cotizacion->load('usuario');
        $settings = LandingSetting::current(); $marca = $settings->values()['brand'];
        $ruta = $settings->logoFilePath();
        $logo = is_file($ruta) ? 'data:'.mime_content_type($ruta).';base64,'.base64_encode(file_get_contents($ruta)) : null;
        return Pdf::loadView('atencion.cotizaciones.pdf', compact('cotizacion', 'marca', 'logo'))
            ->setPaper('a4')->setOption(['defaultFont' => 'DejaVu Sans', 'isRemoteEnabled' => false])
            ->download('cotizacion-'.$cotizacion->folio.'.pdf');
    }
}
