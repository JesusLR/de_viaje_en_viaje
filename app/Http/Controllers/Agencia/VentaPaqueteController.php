<?php

namespace App\Http\Controllers\Agencia;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\PagoVenta;
use App\Models\ServicioTuristico;
use App\Models\User;
use App\Models\VentaPaquete;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class VentaPaqueteController extends Controller
{
    public function vender()
    {
        $clientes = Cliente::where('lActivo', 1)->orderBy('cNombre')->get();
        $paquetes = $this->paquetesDisponibles();

        return view('agencia.ventas.vender', compact('clientes', 'paquetes'));
    }

    private function paquetesDisponibles()
    {
        return ServicioTuristico::where('lActivo', 1)->whereDate('dFechaInicioVigencia', '<=', today())->whereDate('dFechaFinVigencia', '>=', today())->orderBy('cNombre')->get();
    }

    public function index(Request $request)
    {
        return $this->listado($request, false);
    }

    public function apartados(Request $request)
    {
        return $this->listado($request, true);
    }

    private function listado(Request $request, bool $apartados)
    {
        $request->merge([
            'desde' => $request->input('desde') ?: today()->toDateString(),
            'hasta' => $request->input('hasta') ?: today()->toDateString(),
        ]);
        $request->validate([
            'estado' => ['nullable', Rule::in(['Abierto', 'Liquidado', 'Cancelado'])],
            'cliente' => 'nullable|integer|exists:clientes,iID', 'usuario' => 'nullable|integer|exists:users,id',
            'desde' => 'nullable|date_format:Y-m-d', 'hasta' => 'nullable|date_format:Y-m-d|after_or_equal:desde',
            'buscar' => 'nullable|string|max:150',
        ]);
        $query = VentaPaquete::with(['cliente', 'pagos', 'usuario', 'detalles'])->orderByDesc('iID');
        if ($apartados) {
            $query->where('cTipoOperacion', 'APARTADO')->whereNull('finalizada_at');
            if ($request->estado === 'Cancelado') {
                $query->whereNotNull('cancelada_at');
            } elseif (in_array($request->estado, ['Abierto', 'Liquidado'])) {
                $query->whereNull('cancelada_at')->whereRaw('dTotal '.($request->estado === 'Liquidado' ? '=' : '>').' (select coalesce(sum(dImporte), 0) from pagos_ventas where pagos_ventas.iIDVenta = ventas_paquetes.iID and anulado_at is null)');
            }
        } else {
            $query->whereNull('cancelada_at')->whereNotNull('finalizada_at');
        }
        if ($request->filled('cliente')) {
            $query->where('iIDCliente', $request->cliente);
        }
        if ($request->filled('usuario')) {
            $query->where('iIDUsuario', $request->usuario);
        }
        $fecha = $apartados ? 'created_at' : 'finalizada_at';
        if ($request->filled('desde')) {
            $query->whereDate($fecha, '>=', $request->desde);
        }
        if ($request->filled('hasta')) {
            $query->whereDate($fecha, '<=', $request->hasta);
        }
        if ($request->filled('buscar')) {
            $buscar = $request->buscar;
            $query->where(fn ($q) => $q->where('cNombre', 'like', '%'.$buscar.'%')->orWhere('cPaquete', 'like', '%'.$buscar.'%')->orWhere('iID', (int) str_replace('VTA-', '', $buscar)));
        }
        $ventas = $query->paginate(15)->withQueryString();
        $clientes = Cliente::orderBy('cNombre')->get();
        $usuarios = User::orderBy('name')->get();

        return view($apartados ? 'agencia.ventas.apartados' : 'agencia.ventas.listado', compact('ventas', 'clientes', 'usuarios', 'apartados'));
    }

    public function store(Request $request)
    {
        // Compatibilidad con el formulario anterior, que registraba un solo paquete.
        if (! $request->has('items') && $request->filled('iIDServicio')) {
            $request->merge(['items' => [['iIDServicio' => $request->iIDServicio, 'iCantidad' => $request->iCantidad]]]);
        }
        $data = $request->validate([
            'iIDCliente' => ['required', Rule::exists('clientes', 'iID')->where('lActivo', 1)],
            'items' => 'required|array|min:1|max:30',
            'items.*.iIDServicio' => 'required|integer|distinct|exists:servicio_turisticos,iID',
            'items.*.iCantidad' => 'required|integer|min:1|max:1000',
            'accion' => ['nullable', Rule::in(['vender', 'apartar'])],
            'cNombre' => 'nullable|string|max:150',
            'cMetodo' => ['required_if:accion,vender', 'nullable', Rule::in(['Efectivo', 'Transferencia', 'Tarjeta'])],
            'cReferencia' => 'nullable|string|max:150',
            'cObservaciones' => 'nullable|string|max:2000',
        ]);
        $venta = DB::transaction(function () use ($data, $request) {
            $detalles = [];
            $total = 0;
            $moneda = null;
            // Orden estable para evitar bloqueos cruzados al vender varios paquetes.
            $items = collect($data['items'])->sortBy('iIDServicio');
            foreach ($items as $item) {
                $paquete = ServicioTuristico::lockForUpdate()->findOrFail($item['iIDServicio']);
                if ($paquete->c_estatus_disponibilidad !== 'ACTIVO') {
                    throw ValidationException::withMessages(['items' => 'El paquete '.$paquete->cNombre.' ya no está disponible.']);
                }
                if ($moneda !== null && $moneda !== $paquete->cMoneda) {
                    throw ValidationException::withMessages(['items' => 'Los paquetes de una operación deben tener la misma moneda.']);
                }
                $moneda = $paquete->cMoneda;
                $detalle = $this->detalleServicio($paquete, $item['iCantidad']);
                $total += (int) round($detalle['dSubtotal'] * 100);
                $detalles[] = $detalle;
            }
            if ($total > 999999999999) {
                throw ValidationException::withMessages(['items' => 'El total excede el importe permitido.']);
            }
            $directa = ($data['accion'] ?? 'apartar') === 'vender';
            $venta = VentaPaquete::create([
                'iIDCliente' => $data['iIDCliente'], 'iIDUsuario' => $request->user()->id,
                'iIDServicio' => $detalles[0]['iIDServicio'], 'cPaquete' => $detalles[0]['cPaquete'],
                'iCantidad' => array_sum(array_column($detalles, 'iCantidad')), 'dPrecioUnitario' => $detalles[0]['dPrecioUnitario'],
                'cNombre' => $data['cNombre'] ?? null, 'dFechaViaje' => $detalles[0]['dFechaInicio'],
                'cObservaciones' => $data['cObservaciones'] ?? null, 'cMoneda' => $moneda, 'dTotal' => $total / 100,
                'cTipoOperacion' => $directa ? 'VENTA' : 'APARTADO', 'finalizada_at' => $directa ? now() : null,
            ]);
            $venta->detalles()->createMany($detalles);
            if ($directa && $total > 0) {
                $venta->pagos()->create(['dImporte' => $total / 100, 'cMetodo' => $data['cMetodo'], 'cReferencia' => $data['cReferencia'] ?? null, 'dFechaPago' => today(), 'iIDUsuario' => $request->user()->id]);
            }

            return $venta;
        });

        return redirect()->route('agencia.ventas.show', $venta)->with('success', $venta->finalizada_at ? 'Venta liquidada registrada.' : 'Apartado registrado correctamente.');
    }

    public function show(Request $request, VentaPaquete $venta)
    {
        $request->validate(['seccion' => ['nullable', Rule::in(['detalle', 'historial', 'abono', 'paquetes', 'nombre'])]]);
        $seccion = $request->input('seccion');
        $venta->load(['cliente', 'pagos.usuario', 'detalles', 'usuario']);
        $paquetes = $this->paquetesDisponibles()->where('cMoneda', $venta->cMoneda);

        return view('agencia.ventas.show', compact('venta', 'paquetes', 'seccion'));
    }

    public function pago(Request $request, VentaPaquete $venta)
    {
        $data = $request->validate([
            'dImporte' => 'required|numeric|decimal:0,2|min:0.01|max:9999999999.99',
            'cMetodo' => ['required', Rule::in(['Efectivo', 'Transferencia', 'Tarjeta'])],
            'cReferencia' => 'nullable|string|max:150',
            'dFechaPago' => 'nullable|date_format:Y-m-d|before_or_equal:today',
        ]);
        DB::transaction(function () use ($venta, $data, $request) {
            $locked = VentaPaquete::lockForUpdate()->findOrFail($venta->iID);
            if ($locked->cancelada_at || $locked->finalizada_at || (int) round($data['dImporte'] * 100) > $locked->saldo_centavos) {
                throw ValidationException::withMessages(['dImporte' => 'La venta está cancelada o el abono excede el saldo pendiente.']);
            }
            $locked->pagos()->create(array_merge($data, ['dFechaPago' => $data['dFechaPago'] ?? today(), 'iIDUsuario' => $request->user()->id]));
        });

        return back()->with('success', 'Abono registrado correctamente.');
    }

    public function cancelar(VentaPaquete $venta)
    {
        DB::transaction(function () use ($venta) {
            $locked = VentaPaquete::lockForUpdate()->findOrFail($venta->iID);
            if ($locked->finalizada_at) {
                throw ValidationException::withMessages(['venta' => 'No se puede cancelar una venta finalizada.']);
            }
            if (! $locked->cancelada_at) {
                $locked->update(['cancelada_at' => now()]);
            }
        });

        return back()->with('success', 'Venta cancelada.');
    }

    public function finalizar(VentaPaquete $venta)
    {
        DB::transaction(function () use ($venta) {
            $locked = VentaPaquete::lockForUpdate()->findOrFail($venta->iID);
            if ($locked->cancelada_at || $locked->finalizada_at || $locked->saldo_centavos !== 0) {
                throw ValidationException::withMessages(['venta' => 'Solo puedes finalizar un apartado liquidado y activo.']);
            }
            $locked->update(['finalizada_at' => now()]);
        });

        return redirect()->route('agencia.ventas.show', $venta)->with('success', 'Apartado finalizado. Ya aparece en Ventas.');
    }

    public function nombre(Request $request, VentaPaquete $venta)
    {
        $data = $request->validate(['cNombre' => 'nullable|string|max:150']);
        $venta->update($data);

        return back()->with('success', 'Nombre actualizado.');
    }

    public function anularPago(Request $request, VentaPaquete $venta, PagoVenta $pago)
    {
        DB::transaction(function () use ($venta, $pago, $request) {
            $locked = VentaPaquete::lockForUpdate()->findOrFail($venta->iID);
            $abono = $locked->pagos()->findOrFail($pago->iID);
            if ($locked->finalizada_at) {
                throw ValidationException::withMessages(['venta' => 'No se pueden anular abonos de una venta finalizada.']);
            }
            if (! $abono->anulado_at) {
                $abono->update(['anulado_at' => now(), 'iIDUsuarioAnula' => $request->user()->id]);
            }
        });

        return back()->with('success', 'Abono anulado. El registro se conserva en el historial.');
    }

    public function fechaPago(Request $request, VentaPaquete $venta, PagoVenta $pago)
    {
        $data = $request->validate(['dFechaPago' => 'required|date_format:Y-m-d|before_or_equal:today']);
        DB::transaction(function () use ($venta, $pago, $data) {
            $locked = VentaPaquete::lockForUpdate()->findOrFail($venta->iID);
            $abono = $locked->pagos()->findOrFail($pago->iID);
            if ($abono->anulado_at || $locked->finalizada_at || $locked->cancelada_at) {
                throw ValidationException::withMessages(['venta' => 'No se puede editar la fecha de este abono.']);
            }
            $abono->update($data);
        });

        return back()->with('success', 'Fecha de abono actualizada.');
    }

    public function agregarPaquete(Request $request, VentaPaquete $venta)
    {
        $data = $request->validate(['iIDServicio' => 'required|integer|exists:servicio_turisticos,iID', 'iCantidad' => 'required|integer|min:1|max:1000']);
        DB::transaction(function () use ($venta, $data) {
            $locked = VentaPaquete::lockForUpdate()->findOrFail($venta->iID);
            if ($locked->cancelada_at || $locked->finalizada_at) {
                throw ValidationException::withMessages(['venta' => 'Solo puedes agregar paquetes a un apartado activo.']);
            }
            $paquete = ServicioTuristico::lockForUpdate()->findOrFail($data['iIDServicio']);
            if ($paquete->c_estatus_disponibilidad !== 'ACTIVO' || $paquete->cMoneda !== $locked->cMoneda) {
                throw ValidationException::withMessages(['iIDServicio' => 'Selecciona un paquete vigente de la misma moneda.']);
            }
            $detalle = $this->detalleServicio($paquete, $data['iCantidad']);
            $subtotal = (int) round($detalle['dSubtotal'] * 100);
            $total = (int) round((float) $locked->dTotal * 100) + $subtotal;
            if ($total > 999999999999) {
                throw ValidationException::withMessages(['iCantidad' => 'El total excede el importe permitido.']);
            }
            $locked->detalles()->create($detalle);
            $locked->update(['dTotal' => $total / 100, 'iCantidad' => $locked->iCantidad + $data['iCantidad']]);
        });

        return back()->with('success', 'Paquete agregado al apartado.');
    }

    private function detalleServicio(ServicioTuristico $paquete, int $cantidad): array
    {
        $regular = (int) round($paquete->dPrecioVenta * 100);
        $final = (int) round($paquete->d_precio_final * 100);
        $promocion = $paquete->l_promocion_vigente;

        return [
            'iIDServicio' => $paquete->iID, 'cPaquete' => $paquete->cNombre, 'iCantidad' => $cantidad,
            'dPrecioUnitario' => $final / 100, 'dSubtotal' => $final * $cantidad / 100,
            'dFechaInicio' => $paquete->dFechaInicioVigencia->toDateString(),
            'dFechaFin' => $paquete->dFechaFinVigencia->toDateString(),
            'dPrecioRegular' => $regular / 100, 'dDescuentoUnitario' => max(0, $regular - $final) / 100,
            'cPromocion' => $promocion ? ($paquete->cNombrePromocion ?: 'Promoción vigente') : null,
            'cTipoDescuento' => $promocion ? $paquete->cTipoDescuento : null,
            'dValorDescuento' => $promocion ? $paquete->dValorDescuento : 0,
        ];
    }
}
