<?php

namespace App\Http\Middleware;

use App\Models\{ServicioTuristico, User, VentaPaquete};
use Closure;
use Illuminate\Http\Request;

class CheckModulePermissions
{
    private function require(Request $request, string $permission): void
    {
        if ($request->user()->hasPermission($permission)) return;
        if ($request->expectsJson() || $request->ajax()) {
            throw new \Illuminate\Http\Exceptions\HttpResponseException(response()->json(['lSuccess' => false, 'cMensaje' => 'No tienes permiso para realizar esta acción.'], 403));
        }
        abort(403, 'No tienes permiso para realizar esta acción.');
    }

    public function handle(Request $request, Closure $next)
    {
        if (!$request->user()->lActivo) {
            \Illuminate\Support\Facades\Auth::logout();
            $request->session()->invalidate();
            return redirect()->route('login');
        }
        $name = $request->route()->getName();
        if (in_array($name, ['dashboard', 'logout', 'password.change', 'password.update', 'perfil.edit', 'perfil.update', 'perfil.photo'], true)) return $next($request);
        $parts = explode('.', $name);
        $action = end($parts);
        $module = match (true) {
            str_starts_with($name, 'itinerarios.') => 'itinerarios',
            str_starts_with($name, 'admin.permisos.') => 'permisos',
            str_starts_with($name, 'admin.usuarios.') => 'usuarios',
            str_starts_with($name, 'configuracion.') => 'configuracion',
            str_starts_with($name, 'agencia.finanzas.') => 'finanzas',
            str_starts_with($name, 'agencia.vender.') => 'vender',
            str_starts_with($name, 'agencia.apartados.') => 'apartados',
            str_starts_with($name, 'agencia.ventas.') => 'ventas',
            str_starts_with($name, 'servicios.paquetes.') => 'servicios',
            str_starts_with($name, 'categoria.') => 'categorias',
            str_starts_with($name, 'catalogos.'), str_starts_with($name, 'servicios.') => $parts[1],
            default => null,
        };
        abort_unless($module, 403);

        if ($module === 'ventas' && $action === 'store') {
            $this->require($request, 'vender.ver');
            $this->require($request, $request->input('accion', 'apartar') === 'vender' ? 'vender.venta' : 'vender.apartado');
        } else {
            if ($module === 'ventas' && $request->route('venta')) {
                $venta = $request->route('venta');
                if (!$venta instanceof VentaPaquete) $venta = VentaPaquete::findOrFail($venta);
                $module = $venta->finalizada_at ? 'ventas' : 'apartados';
                abort_if($venta->eliminada_at && !in_array($action, ['show', 'comprobante', 'itinerarioPdf', 'itinerarioArchivo', 'destroy'], true), 422, 'La venta está eliminada y no se puede modificar.');
                abort_unless($request->user()->hasPermission($module.'.todos') || (int) $venta->iIDUsuario === (int) $request->user()->id, 403);
            }
            $this->require($request, $module.'.ver');
            $specific = match ($action) {
                'saveData' => (int) $request->input('iID', 0) > 0 ? 'editar' : 'crear',
                'deleteData', 'activar' => 'activar',
                'pago' => 'abonos', 'agregarPaquete' => 'paquetes', 'nombre' => 'nombre',
                'fechaPago' => 'fecha', 'anularPago' => 'anular', 'cancelar' => 'cancelar', 'finalizar' => 'finalizar',
                'comprobante', 'comprobanteCompra' => 'pdf',
                'itinerarioPdf' => $module === 'servicios' ? null : 'pdf',
                'exportar' => 'exportar', 'anular' => 'anular',
                'store' => $module === 'permisos' ? 'gestionar' : ($module === 'itinerarios' ? 'editar' : 'crear'),
                'update' => $module === 'permisos' ? 'gestionar' : 'editar',
                'destroy' => 'eliminar', 'assign' => 'asignar',
                default => null,
            };
            if ($module === 'ventas' && $action === 'show' && $request->filled('seccion')) {
                $specific = ['abono' => 'abonos', 'paquetes' => 'paquetes', 'nombre' => 'nombre'][$request->seccion] ?? null;
            }
            if ($specific) $this->require($request, $module.'.'.$specific);
        }

        if (in_array($name, ['servicios.paquetes.itinerarioPdf', 'agencia.ventas.itinerarioPdf'], true)) {
            $this->require($request, 'itinerarios.ver');
            $this->require($request, 'itinerarios.pdf');
        }
        if (in_array($name, ['servicios.paquetes.itinerarioArchivo', 'agencia.ventas.itinerarioArchivo'], true)) $this->require($request, 'itinerarios.ver');
        if ($name === 'admin.usuarios.saveData' && (int) $request->iID > 0 && $request->filled('cPassword')) $this->require($request, 'usuarios.password');
        if ($name === 'admin.usuarios.saveData' && (int) $request->iID > 0 && User::find($request->iID)?->roles()->where('slug', 'administrador')->exists()) $this->require($request, 'permisos.asignar');
        if ($name === 'admin.usuarios.activar' && User::find($request->iID)?->roles()->where('slug', 'administrador')->exists()) $this->require($request, 'permisos.asignar');
        if ($name === 'admin.usuarios.deleteData') {
            $target = User::findOrFail($request->iID);
            if ($target->roles()->where('slug', 'administrador')->exists()) {
                $this->require($request, 'permisos.asignar');
                return \Illuminate\Support\Facades\DB::transaction(function () use ($request, $target, $next) {
                    \App\Models\Role::where('slug', 'administrador')->lockForUpdate()->firstOrFail();
                    abort_if($target->id === $request->user()->id || !User::where('lActivo', true)->where('id', '!=', $target->id)->whereHas('roles', fn ($q) => $q->where('slug', 'administrador'))->exists(), 422, 'No puedes desactivar tu propia cuenta o al último administrador activo.');
                    return $next($request);
                });
            }
        }
        if ($name === 'servicios.paquetes.saveData') {
            $service = ServicioTuristico::find((int) $request->iID);
            if ($request->hasFile('itinerarioPdfFile') || ($request->has('cTipoItinerario') && $request->cTipoItinerario !== ($service?->cTipoItinerario ?? 'CATALOGO'))) $this->require($request, 'itinerarios.asignar');
            foreach (['iIDItinerario' => 'itinerarios.asignar', 'lPublicarItinerario' => 'itinerarios.publicar'] as $field => $permission) {
                $original = $service?->$field;
                if ($request->has($field) && (int) $request->input($field) !== (int) $original) $this->require($request, $permission);
                if (!$request->has($field)) $request->merge([$field => $original ?? ($field === 'lPublicarItinerario' ? 0 : null)]);
            }
            if (!$request->user()->hasPermission('servicios.precios')) {
                foreach (['lPrecioMenorActivo', 'dPrecioMenor'] as $field) {
                    $original = $service?->$field ?? 0;
                    abort_if($request->has($field) && (float) $request->input($field) !== (float) $original, 403);
                    $request->merge([$field => $original]);
                }
            }
            if ($service && !$request->user()->hasPermission('servicios.precios')) $request->merge(['dPrecioVenta' => $request->input('dPrecioVenta', $service->dPrecioVenta), 'cMoneda' => $request->input('cMoneda', $service->cMoneda)]);
            foreach (['dPrecioCompra' => 'costos.editar', 'dPrecioVenta' => 'precios', 'cMoneda' => 'precios', 'lActivo' => 'activar'] as $field => $permission) {
                if ($service && $request->has($field)) {
                    $changed = in_array($field, ['dPrecioCompra', 'dPrecioVenta']) ? (float) $request->input($field) !== (float) $service->$field : (string) $request->input($field) != (string) $service->$field;
                    if ($changed) $this->require($request, 'servicios.'.$permission);
                }
            }
            if (!$request->user()->hasPermission('servicios.costos.editar')) {
                abort_if($request->filled('dPrecioCompra') && (float) $request->dPrecioCompra != (float) ($service?->dPrecioCompra ?? 0), 403);
                $request->merge(['dPrecioCompra' => $service?->dPrecioCompra ?? 0]);
            }
            if ($service && !$request->user()->hasPermission('servicios.activar')) $request->merge(['lActivo' => $service->lActivo]);
            $promoFields = ['lTienePromocion', 'lPromocionActiva', 'cNombrePromocion', 'cDescripcionPromocion', 'cTipoDescuento', 'dValorDescuento', 'dFechaInicioPromocion', 'dFechaFinPromocion'];
            if (!$request->user()->hasPermission('servicios.promociones')) {
                foreach ($promoFields as $field) {
                    $original = $service?->$field;
                    $comparison = $original instanceof \DateTimeInterface ? $original->format('Y-m-d') : (is_bool($original) ? (int) $original : $original);
                    $incoming = $request->input($field);
                    $changed = is_numeric($comparison) && is_numeric($incoming) ? (float) $incoming !== (float) $comparison : (string) $incoming !== (string) ($comparison ?? '');
                    if ($request->has($field) && $changed) $this->require($request, 'servicios.promociones');
                    $request->merge([$field => $original instanceof \DateTimeInterface ? $original->format('Y-m-d H:i:s') : $original]);
                }
            }
        }
        $response = $next($request);
        if ($module === 'servicios' && $response instanceof \Illuminate\Http\JsonResponse) {
            $filter = function ($value) use (&$filter, $request) {
                if (!is_array($value)) return $value;
                foreach ($value as $key => $item) {
                    if (($key === 'dPrecioCompra' && !$request->user()->hasPermission('servicios.costos.ver')) || ((str_contains((string) $key, 'Ganancia') || str_contains((string) $key, 'Utilidad')) && !$request->user()->hasPermission('servicios.utilidad'))) unset($value[$key]);
                    else $value[$key] = $filter($item);
                }
                return $value;
            };
            $response->setData($filter($response->getData(true)));
        }
        $success = $response instanceof \Illuminate\Http\JsonResponse ? ($response->getData(true)['lSuccess'] ?? false) : ($response->isRedirection() && $request->session()->has('success'));
        if ($module !== 'permisos' && $action !== 'simularPrecios' && !$request->isMethod('GET') && $success) {
            $registro = $request->route('venta');
            $registro = $registro instanceof VentaPaquete ? $registro->iID : $registro;
            \Illuminate\Support\Facades\DB::table('permission_audits')->insert([
                'actor_id' => $request->user()->id,
                'action' => (config('permissions.'.$module.'.label') ?? $module).' · '.$action,
                'details' => json_encode(['modulo' => $module, 'registro' => $registro ?? $request->input('iID'), 'accion' => $name]),
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }
        return $response;
    }
}
