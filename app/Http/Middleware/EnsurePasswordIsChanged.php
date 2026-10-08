<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePasswordIsChanged
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->check()) {
            $oUser = auth()->user();

            if ($oUser->lCambiarPassword) {
                // Rutas permitidas mientras se requiere cambio de contraseña
                $aRutasPermitidas = [
                    'password.change',
                    'password.update',
                    'logout',
                ];

                $cRutaActual = $request->route() ? $request->route()->getName() : null;

                if (!in_array($cRutaActual, $aRutasPermitidas)) {
                    if ($request->ajax() || $request->wantsJson()) {
                        return response()->json([
                            'lSuccess' => false,
                            'cMensaje' => 'Debe actualizar su contraseña obligatoriamente para continuar.',
                            'redirect' => route('password.change'),
                        ], 403);
                    }

                    return redirect()->route('password.change')->with('warning', 'Es necesario que cambies tu contraseña antes de continuar.');
                }
            }
        }

        return $next($request);
    }
}
