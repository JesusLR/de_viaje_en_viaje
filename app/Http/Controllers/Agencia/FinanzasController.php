<?php

namespace App\Http\Controllers\Agencia;

use App\Http\Controllers\Controller;
use App\Models\MovimientoFinanciero;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class FinanzasController extends Controller
{
    private function filtros(Request $request): array
    {
        $data = $request->validate([
            'desde' => 'nullable|date_format:Y-m-d',
            'hasta' => 'nullable|date_format:Y-m-d|after_or_equal:desde',
            'moneda' => 'nullable|string|max:10',
        ]);
        $desde = $data['desde'] ?? today()->startOfMonth()->toDateString();
        $hasta = $data['hasta'] ?? today()->endOfMonth()->toDateString();
        if ($hasta < $desde) {
            throw ValidationException::withMessages(['hasta' => 'La fecha final debe ser posterior a la inicial.']);
        }

        return [$desde, $hasta, $data['moneda'] ?? 'MXN'];
    }

    private function movimientos(string $desde, string $hasta, string $moneda)
    {
        $pagos = DB::table('pagos_ventas as p')->join('ventas_paquetes as v', 'v.iID', '=', 'p.iIDVenta')
            ->join('clientes as c', 'c.iID', '=', 'v.iIDCliente')
            ->whereNull('p.anulado_at')->where('v.cMoneda', $moneda)
            ->whereBetween('p.dFechaPago', [$desde, $hasta])
            ->selectRaw("p.iID as id, v.iID as venta_id, p.dFechaPago as fecha, 'INGRESO' as tipo, case when (v.cTipoOperacion = 'VENTA' or v.finalizada_at is not null) then 'Venta' else 'Abono' end as origen, v.cPaquete as concepto, c.cNombre as detalle, p.cMetodo as metodo, p.dImporte as importe, v.cMoneda as moneda, 0 as manual");
        $manuales = DB::table('movimientos_financieros as m')->leftJoin('users as u', 'u.id', '=', 'm.iIDUsuario')
            ->whereNull('m.anulado_at')->where('m.cMoneda', $moneda)->whereBetween('m.dFecha', [$desde, $hasta])
            ->selectRaw("m.iID as id, null as venta_id, m.dFecha as fecha, m.cTipo as tipo, 'Manual' as origen, m.cConcepto as concepto, m.cObservaciones as detalle, m.cMetodo as metodo, m.dImporte as importe, m.cMoneda as moneda, 1 as manual");

        $tipos = [];
        if (auth()->user()->hasPermission('finanzas.ingresos')) $tipos[] = 'INGRESO';
        if (auth()->user()->hasPermission('finanzas.egresos')) $tipos[] = 'EGRESO';
        return DB::query()->fromSub($pagos->unionAll($manuales), 'movimientos')->whereIn('tipo', $tipos);
    }

    public function index(Request $request)
    {
        [$desde, $hasta, $moneda] = $this->filtros($request);
        $query = $this->movimientos($desde, $hasta, $moneda);
        $totales = (clone $query)->selectRaw("coalesce(sum(case when tipo = 'INGRESO' then importe else 0 end), 0) as ingresos, coalesce(sum(case when tipo = 'EGRESO' then importe else 0 end), 0) as egresos")->first();
        $metodos = (clone $query)->where('tipo', 'INGRESO')->selectRaw('metodo, sum(importe) as total')->groupBy('metodo')->get();
        $origenes = (clone $query)->where('tipo', 'INGRESO')->selectRaw('origen, sum(importe) as total')->groupBy('origen')->pluck('total', 'origen');
        $ingresos = (clone $query)->where('tipo', 'INGRESO')->orderByDesc('fecha')->orderByDesc('id')->paginate(10, ['*'], 'ingresos_page')->withQueryString();
        $egresos = (clone $query)->where('tipo', 'EGRESO')->orderByDesc('fecha')->orderByDesc('id')->paginate(10, ['*'], 'egresos_page')->withQueryString();
        $monedas = DB::table('servicio_turisticos')->pluck('cMoneda')->merge(DB::table('movimientos_financieros')->pluck('cMoneda'))->merge(['MXN', 'USD', 'EUR', $moneda])->unique()->sort()->values();

        return view('agencia.finanzas.index', compact('desde', 'hasta', 'moneda', 'totales', 'metodos', 'ingresos', 'egresos', 'monedas', 'origenes'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'cTipo' => ['required', Rule::in(['INGRESO', 'EGRESO'])],
            'cConcepto' => 'required|string|max:150', 'dImporte' => 'required|numeric|decimal:0,2|min:0.01|max:9999999999.99',
            'cMoneda' => 'required|string|regex:/^[A-Z]{3}$/',
            'cMetodo' => ['required', Rule::in(['Efectivo', 'Transferencia', 'Tarjeta'])],
            'dFecha' => 'required|date_format:Y-m-d|before_or_equal:today', 'cObservaciones' => 'nullable|string|max:2000',
        ]);
        MovimientoFinanciero::create($data + ['iIDUsuario' => $request->user()->id]);

        return redirect()->route('agencia.finanzas.index', ['moneda' => $data['cMoneda'], 'desde' => substr($data['dFecha'], 0, 7).'-01', 'hasta' => $data['dFecha']])->with('success', 'Movimiento registrado.');
    }

    public function anular(Request $request, MovimientoFinanciero $movimiento)
    {
        if (! $movimiento->anulado_at) {
            $movimiento->update(['anulado_at' => now(), 'iIDUsuarioAnula' => $request->user()->id]);
        }

        return back()->with('success', 'Movimiento anulado.');
    }

    public function exportar(Request $request)
    {
        [$desde, $hasta, $moneda] = $this->filtros($request);
        $query = $this->movimientos($desde, $hasta, $moneda)->orderBy('fecha')->orderBy('id');

        return response()->streamDownload(function () use ($query) {
            $archivo = fopen('php://output', 'w');
            fwrite($archivo, "\xEF\xBB\xBF");
            fputcsv($archivo, ['Fecha', 'Tipo', 'Origen', 'Folio', 'Concepto', 'Detalle', 'Método', 'Importe', 'Moneda']);
            foreach ($query->cursor() as $fila) {
                $valores = [$fila->fecha, $fila->tipo, $fila->origen, $fila->venta_id ? 'VTA-'.str_pad($fila->venta_id, 6, '0', STR_PAD_LEFT) : '', $fila->concepto, $fila->detalle, $fila->metodo, $fila->importe, $fila->moneda];
                // Mantener los textos como texto al abrir el CSV en Excel.
                fputcsv($archivo, array_map(fn ($valor) => preg_match('/^[\s]*[=+@-]/u', (string) $valor) ? "'".$valor : $valor, $valores));
            }
            fclose($archivo);
        }, 'finanzas-'.$desde.'-'.$hasta.'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
