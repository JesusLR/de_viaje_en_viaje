<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Exception;

class PasswordController extends Controller
{
    /**
     * Muestra la vista para el cambio obligatorio de contraseña.
     */
    public function showChangeForm()
    {
        return view('auth.change_password');
    }

    /**
     * Procesa la actualización de contraseña del usuario autenticado.
     */
    public function updatePassword(Request $request)
    {
        try {
            $request->validate([
                'cPasswordActual' => 'required|string',
                'cPasswordNueva'  => 'required|string|min:6|different:cPasswordActual',
                'cPasswordConfirm' => 'required|string|same:cPasswordNueva',
            ], [
                'cPasswordActual.required' => 'Debe ingresar su contraseña actual.',
                'cPasswordNueva.required'  => 'Debe ingresar una nueva contraseña.',
                'cPasswordNueva.min'       => 'La nueva contraseña debe tener al menos 6 caracteres.',
                'cPasswordNueva.different' => 'La nueva contraseña debe ser diferente a la contraseña actual.',
                'cPasswordConfirm.required'=> 'Debe confirmar su nueva contraseña.',
                'cPasswordConfirm.same'    => 'La confirmación de la contraseña no coincide.',
            ]);

            $oUser = auth()->user();

            // Verificar contraseña actual
            if (!Hash::check($request->input('cPasswordActual'), $oUser->password)) {
                $cMensajeError = 'La contraseña actual ingresada es incorrecta.';

                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json([
                        'lSuccess' => false,
                        'cMensaje' => $cMensajeError,
                    ], 422);
                }

                return back()->withErrors(['cPasswordActual' => $cMensajeError]);
            }

            // Actualizar contraseña y quitar requerimiento de cambio
            $oUser->password = Hash::make($request->input('cPasswordNueva'));
            $oUser->lCambiarPassword = 0; // Se desactiva la bandera
            $oUser->save();

            $cMensajeExito = '¡Su contraseña ha sido actualizada correctamente! Bienvenido al sistema.';

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'lSuccess' => true,
                    'cMensaje' => $cMensajeExito,
                    'redirect' => route('dashboard'),
                ]);
            }

            return redirect()->route('dashboard')->with('success', $cMensajeExito);
        } catch (Exception $ex) {
            $cMensajeError = 'Error al actualizar la contraseña: ' . $ex->getMessage();

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'lSuccess' => false,
                    'cMensaje' => $cMensajeError,
                ], 500);
            }

            return back()->withErrors(['cPasswordNueva' => $cMensajeError]);
        }
    }
}
