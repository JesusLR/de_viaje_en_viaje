<?php

namespace App\Services;

use App\Models\Cotizacion;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class EstadisticasService
{
    public function rangoHistorico(array $f, bool $finanzas, bool $cotizaciones): array
    {
        $fechas = [today()->toDateString()];
        $agregar = function ($query, string $campo) use (&$fechas) {
            foreach (['min', 'max'] as $funcion) {
                $fecha = (clone $query)->$funcion($campo);
                if ($fecha) $fechas[] = substr((string) $fecha, 0, 10);
            }
        };
        foreach (['v.created_at', 'v.finalizada_at', 'v.eliminada_at'] as $campo) $agregar($this->operaciones($f), $campo);
        if ($finanzas && empty($f['servicio'])) {
            $pagos = DB::table('pagos_ventas as p')->join('ventas_paquetes as v', 'v.iID', '=', 'p.iIDVenta')->where('v.cMoneda', $f['moneda'])
                ->whereNull('p.anulado_at')->whereNull('v.eliminada_at')->when(!empty($f['usuario']), fn ($q) => $q->where('v.iIDUsuario', $f['usuario']));
            $agregar($pagos, 'p.dFechaPago');
            $manuales = DB::table('movimientos_financieros')->where('cMoneda', $f['moneda'])->whereNull('anulado_at')
                ->when(!empty($f['usuario']), fn ($q) => $q->where('iIDUsuario', $f['usuario']));
            $agregar($manuales, 'dFecha');
        }
        if ($cotizaciones) {
            $web = DB::table('cotizaciones')->where('moneda', $f['moneda'])->when(!empty($f['usuario']), fn ($q) => $q->where('atendida_por', $f['usuario']));
            if (empty($f['servicio'])) $agregar($web, 'created_at');
            $manuales = DB::table('cotizaciones_servicios')->where('moneda', $f['moneda'])
                ->when(!empty($f['usuario']), fn ($q) => $q->where('usuario_id', $f['usuario']))
                ->when(!empty($f['servicio']), fn ($q) => $q->whereJsonContains('partidas', ['servicio_id' => (int) $f['servicio']]));
            $agregar($manuales, 'created_at');
        }
        return ['desde' => min($fechas), 'hasta' => max($fechas)];
    }

    private function periodo($query, string $campo, array $f)
    {
        return $query->where($campo, '>=', $f['desde'].' 00:00:00')->where($campo, '<', Carbon::parse($f['hasta'])->addDay()->toDateString().' 00:00:00');
    }

    private function operaciones(array $f)
    {
        return DB::table('ventas_paquetes as v')->where('v.cMoneda', $f['moneda'])
            ->when(!empty($f['usuario']), fn ($q) => $q->where('v.iIDUsuario', $f['usuario']))
            ->when(!empty($f['servicio']), fn ($q) => $q->whereExists(fn ($d) => $d->selectRaw('1')->from('venta_paquete_detalles as filtro_detalle')->whereColumn('filtro_detalle.iIDVenta', 'v.iID')->where('filtro_detalle.iIDServicio', $f['servicio'])));
    }

    private function ventas(array $f)
    {
        return $this->periodo($this->operaciones($f)->whereNull('v.eliminada_at')->whereNull('v.cancelada_at')->whereNotNull('v.finalizada_at'), 'v.finalizada_at', $f);
    }

    private function detalles(array $f)
    {
        return $this->ventas($f)->join('venta_paquete_detalles as d', 'd.iIDVenta', '=', 'v.iID')
            ->when(!empty($f['servicio']), fn ($q) => $q->where('d.iIDServicio', $f['servicio']));
    }

    public function resumen(array $f, bool $verFinanzas, bool $verCotizaciones): array
    {
        $cantidad = $this->ventas($f)->count();
        $importe = !empty($f['servicio']) ? (float) $this->detalles($f)->sum('d.dSubtotal') : (float) $this->ventas($f)->sum('v.dTotal');
        $cantidades = $this->detalles($f)->selectRaw('coalesce(sum(d.iCantidad),0) adultos, coalesce(sum(d.iCantidadMenores),0) menores, coalesce(sum(d.dDescuentoUnitario * d.iCantidad),0) descuentos')->first();
        $ranking = $this->detalles($f)->selectRaw('d.iIDServicio id, max(d.cPaquete) nombre, count(distinct v.iID) ventas, sum(d.iCantidad) adultos, sum(d.iCantidadMenores) menores, sum(d.dSubtotal) importe')
            ->groupBy('d.iIDServicio')->orderByDesc('importe')->limit(10)->get();
        $vendedores = (!empty($f['servicio']) ? $this->detalles($f) : $this->ventas($f))
            ->leftJoin('users as u', 'u.id', '=', 'v.iIDUsuario')
            ->selectRaw("v.iIDUsuario id, coalesce(max(u.name), 'Usuario no disponible') nombre, count(distinct v.iID) ventas, sum(".(!empty($f['servicio']) ? 'd.dSubtotal' : 'v.dTotal').') importe')
            ->groupBy('v.iIDUsuario')->orderByDesc('importe')->limit(10)->get();
        $eliminadas = $this->periodo($this->operaciones($f)->whereNotNull('v.eliminada_at')->whereNotNull('v.finalizada_at'), 'v.eliminada_at', $f)->count();

        $sumaPagos = DB::table('pagos_ventas as p')->selectRaw('coalesce(sum(p.dImporte),0)')->whereColumn('p.iIDVenta', 'v.iID')->whereNull('p.anulado_at');
        $apartados = $this->periodo($this->operaciones($f)->whereNull('v.eliminada_at')->where('v.cTipoOperacion', 'APARTADO'), 'v.created_at', $f)
            ->select('v.dTotal', 'v.cancelada_at', 'v.finalizada_at')->selectSub($sumaPagos, 'abonado')->get();
        $estados = ['Abiertos' => 0, 'Liquidados' => 0, 'Finalizados' => 0, 'Cancelados' => 0]; $saldo = 0;
        foreach ($apartados as $apartado) {
            if ($apartado->cancelada_at) $estados['Cancelados']++;
            elseif ($apartado->finalizada_at) $estados['Finalizados']++;
            else {
                $pendiente = max(0, (int) round($apartado->dTotal * 100) - (int) round($apartado->abonado * 100));
                $estados[$pendiente > 0 ? 'Abiertos' : 'Liquidados']++;
                $saldo += $pendiente;
            }
        }
        $meses = [];
        for ($mes = Carbon::parse($f['desde'])->startOfMonth(); $mes->toDateString() <= $f['hasta']; $mes->addMonth()) {
            $meses[$mes->format('Y-m')] = ['nombre' => $mes->locale('es')->translatedFormat('M Y'), 'vendido' => 0.0, 'cobrado' => 0.0, 'egresos' => 0.0];
        }
        $vendidoMes = (!empty($f['servicio']) ? $this->detalles($f) : $this->ventas($f))->selectRaw("date_format(v.finalizada_at, '%Y-%m') mes, sum(".(!empty($f['servicio']) ? 'd.dSubtotal' : 'v.dTotal').') total')->groupBy('mes')->get();
        foreach ($vendidoMes as $fila) $meses[$fila->mes]['vendido'] = (float) $fila->total;

        $finanzas = null;
        if ($verFinanzas && empty($f['servicio'])) {
            $pagos = DB::table('pagos_ventas as p')->join('ventas_paquetes as v', 'v.iID', '=', 'p.iIDVenta')
                ->whereNull('v.eliminada_at')->whereNull('p.anulado_at')->where('v.cMoneda', $f['moneda'])
                ->whereBetween('p.dFechaPago', [$f['desde'], $f['hasta']])
                ->when(!empty($f['usuario']), fn ($q) => $q->where('v.iIDUsuario', $f['usuario']));
            $manuales = DB::table('movimientos_financieros as m')->whereNull('m.anulado_at')->where('m.cMoneda', $f['moneda'])
                ->whereBetween('m.dFecha', [$f['desde'], $f['hasta']])
                ->when(!empty($f['usuario']), fn ($q) => $q->where('m.iIDUsuario', $f['usuario']));
            $cobrado = (float) (clone $pagos)->sum('p.dImporte');
            $ingresoManual = (float) (clone $manuales)->where('m.cTipo', 'INGRESO')->sum('m.dImporte');
            $egresos = (float) (clone $manuales)->where('m.cTipo', 'EGRESO')->sum('m.dImporte');
            $metodos = [];
            foreach ((clone $pagos)->selectRaw('p.cMetodo metodo, sum(p.dImporte) total')->groupBy('p.cMetodo')->get() as $fila) $metodos[$fila->metodo] = (float) $fila->total;
            foreach ((clone $manuales)->where('m.cTipo', 'INGRESO')->selectRaw('m.cMetodo metodo, sum(m.dImporte) total')->groupBy('m.cMetodo')->get() as $fila) $metodos[$fila->metodo] = ($metodos[$fila->metodo] ?? 0) + (float) $fila->total;
            foreach ((clone $pagos)->selectRaw("date_format(p.dFechaPago, '%Y-%m') mes, sum(p.dImporte) total")->groupBy('mes')->get() as $fila) $meses[$fila->mes]['cobrado'] += (float) $fila->total;
            foreach ((clone $manuales)->selectRaw("date_format(m.dFecha, '%Y-%m') mes, m.cTipo tipo, sum(m.dImporte) total")->groupBy('mes', 'm.cTipo')->get() as $fila) $meses[$fila->mes][$fila->tipo === 'INGRESO' ? 'cobrado' : 'egresos'] += (float) $fila->total;
            $finanzas = ['ingresos' => $cobrado + $ingresoManual, 'cobros' => $cobrado, 'manuales' => $ingresoManual, 'egresos' => $egresos,
                'balance' => $cobrado + $ingresoManual - $egresos, 'metodos' => $metodos, 'saldo' => (float) ($saldo / 100)];
        }

        $web = $cotizaciones = null;
        if ($verCotizaciones) {
            if (empty($f['servicio'])) {
                $query = $this->periodo(DB::table('cotizaciones')->where('moneda', $f['moneda'])
                    ->when(!empty($f['usuario']), fn ($q) => $q->where('atendida_por', $f['usuario'])), 'cotizaciones.created_at', $f);
                $webEstados = array_fill_keys(array_keys(Cotizacion::ESTADOS), 0);
                foreach ((clone $query)->selectRaw('estado, count(*) cantidad')->groupBy('estado')->get() as $fila) $webEstados[$fila->estado] = $fila->cantidad;
                $horas = (clone $query)->where('estado', 'ATENDIDO')->whereNotNull('atendida_at')->selectRaw('avg(timestampdiff(second, created_at, atendida_at))/3600 horas')->value('horas');
                $responsables = (clone $query)->leftJoin('users as u', 'u.id', '=', 'atendida_por')->whereNotNull('atendida_por')
                    ->selectRaw('atendida_por id, max(u.name) nombre, count(*) asignadas, sum(estado = ?) atendidas', ['ATENDIDO'])->groupBy('atendida_por')->orderByDesc('asignadas')->limit(10)->get();
                $web = ['estados' => $webEstados, 'total' => array_sum($webEstados), 'horas' => $horas === null ? null : (float) $horas, 'responsables' => $responsables];
            }
            $cotizaciones = ['cantidad' => 0, 'importe' => 0.0, 'servicios' => []]; $importeCotizado = 0;
            $query = $this->periodo(DB::table('cotizaciones_servicios')->where('moneda', $f['moneda'])
                ->when(!empty($f['usuario']), fn ($q) => $q->where('usuario_id', $f['usuario'])), 'created_at', $f);
            foreach ($query->select('partidas')->cursor() as $cotizacion) {
                $coincide = false;
                foreach (json_decode($cotizacion->partidas, true) ?? [] as $partida) {
                    if (!empty($f['servicio']) && (int) $partida['servicio_id'] !== (int) $f['servicio']) continue;
                    $coincide = true; $importeCotizado += (int) round($partida['subtotal'] * 100);
                    $id = $partida['servicio_id'];
                    if (!isset($cotizaciones['servicios'][$id])) $cotizaciones['servicios'][$id] = ['nombre' => $partida['nombre'], 'solicitudes' => 0, 'personas' => 0];
                    $cotizaciones['servicios'][$id]['solicitudes']++;
                    $cotizaciones['servicios'][$id]['personas'] += $partida['adultos'] + $partida['menores'];
                }
                if ($coincide) $cotizaciones['cantidad']++;
            }
            $cotizaciones['importe'] = (float) ($importeCotizado / 100);
            $cotizaciones['servicios'] = collect($cotizaciones['servicios'])->sortByDesc('solicitudes')->take(10)->values();
        }

        $agrupacion = count($meses) > 24 ? 'anual' : 'mensual';
        if ($agrupacion === 'anual') {
            $anios = [];
            foreach ($meses as $clave => $mes) {
                $anio = substr($clave, 0, 4);
                if (!isset($anios[$anio])) $anios[$anio] = ['nombre' => $anio, 'vendido' => 0.0, 'cobrado' => 0.0, 'egresos' => 0.0];
                foreach (['vendido', 'cobrado', 'egresos'] as $campo) $anios[$anio][$campo] += $mes[$campo];
            }
            $meses = $anios;
        }
        return ['agrupacion' => $agrupacion, 'ventas' => ['cantidad' => $cantidad, 'importe' => $importe, 'ticket' => $cantidad ? $importe / $cantidad : 0,
            'adultos' => (int) $cantidades->adultos, 'menores' => (int) $cantidades->menores, 'descuentos' => (float) $cantidades->descuentos, 'eliminadas' => $eliminadas],
            'apartados' => ['estados' => $estados, 'total' => $apartados->count(), 'conversion' => $apartados->count() ? $estados['Finalizados'] * 100 / $apartados->count() : 0],
            'finanzas' => $finanzas, 'web' => $web, 'cotizaciones' => $cotizaciones, 'ranking' => $ranking, 'vendedores' => $vendedores, 'meses' => $meses];
    }
}
