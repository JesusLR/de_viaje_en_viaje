<?php

namespace App\Http\Controllers;

use App\Models\ServicioTuristico;
use App\Models\VentaPaquete;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Muestra la vista del panel principal (Dashboard).
     */
    public function index()
    {
        $user = Auth::user();
        $proximosViajes = ServicioTuristico::with('destino')->where('lActivo', true)
            ->whereNotNull('dFechaFinViaje')
            ->whereDate('dFechaInicioViaje', '>=', today())
            ->whereColumn('dFechaFinViaje', '>=', 'dFechaInicioViaje')
            ->orderBy('dFechaInicioViaje')->orderBy('iID')->limit(5)->get();
        $desde = today()->startOfMonth();
        $hasta = $desde->copy()->addMonth();
        $mes = $desde->locale('es')->translatedFormat('F Y');
        $serviciosActivos = ServicioTuristico::publicados()->count();
        $apartadosAbiertos = VentaPaquete::where('cTipoOperacion', 'APARTADO')->whereNull('cancelada_at')->whereNull('finalizada_at')
            ->whereRaw('dTotal > (select coalesce(sum(dImporte), 0) from pagos_ventas where pagos_ventas.iIDVenta = ventas_paquetes.iID and anulado_at is null)')->count();
        $cobros = DB::table('pagos_ventas as p')->join('ventas_paquetes as v', 'v.iID', '=', 'p.iIDVenta')
            ->whereNull('v.eliminada_at')->whereNull('p.anulado_at')->where('p.dFechaPago', '>=', $desde->toDateString())->where('p.dFechaPago', '<', $hasta->toDateString())
            ->selectRaw("v.cMoneda as moneda, sum(p.dImporte) as ingresos, sum(case when (v.cTipoOperacion = 'VENTA' or v.finalizada_at is not null) then p.dImporte else 0 end) as ventas, sum(case when v.cTipoOperacion = 'APARTADO' and v.finalizada_at is null then p.dImporte else 0 end) as abonos")->groupBy('v.cMoneda')->get()->keyBy('moneda');
        $manuales = DB::table('movimientos_financieros')->whereNull('anulado_at')
            ->where('dFecha', '>=', $desde->toDateString())->where('dFecha', '<', $hasta->toDateString())
            ->selectRaw("cMoneda as moneda, sum(case when cTipo = 'INGRESO' then dImporte else 0 end) as ingresos, sum(case when cTipo = 'EGRESO' then dImporte else 0 end) as egresos")
            ->groupBy('cMoneda')->get()->keyBy('moneda');
        $finanzas = $cobros->keys()->merge($manuales->keys())->push('MXN')->unique()->sort()->mapWithKeys(function ($moneda) use ($cobros, $manuales) {
            $ingresos = (int) round((float) ($cobros->get($moneda)->ingresos ?? 0) * 100) + (int) round((float) ($manuales->get($moneda)->ingresos ?? 0) * 100);
            $egresos = (int) round((float) ($manuales->get($moneda)->egresos ?? 0) * 100);
            return [$moneda => [
                'ingresos' => $ingresos / 100, 'egresos' => $egresos / 100, 'balance' => ($ingresos - $egresos) / 100,
                'ventas' => (float) ($cobros->get($moneda)->ventas ?? 0),
                'abonos' => (float) ($cobros->get($moneda)->abonos ?? 0),
                'manuales' => (float) ($manuales->get($moneda)->ingresos ?? 0),
            ]];
        });
        $fechaDesde = $desde->toDateString();
        $fechaHasta = $hasta->copy()->subDay()->toDateString();
        $desdeApartados = substr(VentaPaquete::where('cTipoOperacion', 'APARTADO')->min('created_at') ?? $fechaDesde, 0, 10);
        if (!$user->hasPermission('dashboard.finanzas')) $finanzas = collect();
        else $finanzas = $finanzas->map(function ($importes) use ($user) {
            if (!$user->hasPermission('dashboard.ingresos')) $importes['ingresos'] = 0;
            if (!$user->hasPermission('dashboard.origen')) foreach (['ventas', 'abonos', 'manuales'] as $field) $importes[$field] = 0;
            if (!$user->hasPermission('dashboard.egresos')) $importes['egresos'] = 0;
            if (!$user->hasPermission('dashboard.ingresos') || !$user->hasPermission('dashboard.egresos')) $importes['balance'] = 0;
            return $importes;
        });
        if (!$user->hasPermission('dashboard.apartados')) $apartadosAbiertos = 0;
        elseif (!$user->hasPermission('apartados.todos')) $apartadosAbiertos = VentaPaquete::where('iIDUsuario', $user->id)->where('cTipoOperacion', 'APARTADO')->whereNull('cancelada_at')->whereNull('finalizada_at')->whereRaw('dTotal > (select coalesce(sum(dImporte), 0) from pagos_ventas where pagos_ventas.iIDVenta = ventas_paquetes.iID and anulado_at is null)')->count();
        if (!$user->hasPermission('dashboard.servicios')) $serviciosActivos = 0;
        if (!$user->hasPermission('dashboard.viajes')) $proximosViajes = collect();
        $cotizacionesWebPendientes = $user->hasPermission('cotizaciones.ver') ? \App\Models\Cotizacion::where('estado', 'NUEVA')->count() : 0;
        return view('dashboard.index', compact('cotizacionesWebPendientes', 'user', 'mes', 'serviciosActivos', 'apartadosAbiertos', 'finanzas', 'fechaDesde', 'fechaHasta', 'desdeApartados', 'proximosViajes'));
    }
}
