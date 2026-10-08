<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Exception;

class LoginController extends Controller
{
    /**
     * Muestra la vista de inicio de sesión.
     */
    public function showLoginForm()
    {
        return view('auth.login');
    }

    /**
     * Procesa la solicitud de inicio de sesión (SSR y AJAX).
     */
    public function login(LoginRequest $request)
    {
        try {
            $credentials = $request->only('email', 'password');
            $remember = $request->boolean('remember');

            if (Auth::attempt($credentials, $remember)) {
                // Protección contra Session Fixation: Regenerar ID de sesión
                $request->session()->regenerate();

                $redirectUrl = route('dashboard');

                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json([
                        'lSuccess'  => true,
                        'cMensaje'  => '¡Inicio de sesión exitoso! Redirigiendo...',
                        'redirect'  => $redirectUrl,
                    ]);
                }

                return redirect()->intended($redirectUrl);
            }

            // Credenciales incorrectas
            $cMensajeError = 'Las credenciales ingresadas no coinciden con nuestros registros.';

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'lSuccess' => false,
                    'cMensaje' => $cMensajeError,
                ], 422);
            }

            return back()->withErrors([
                'email' => $cMensajeError,
            ])->onlyInput('email');

        } catch (Exception $ex) {
            $cMensajeError = 'Ocurrió un error inesperado al procesar el inicio de sesión.';

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'lSuccess' => false,
                    'cMensaje' => $cMensajeError . ' Details: ' . $ex->getMessage(),
                ], 500);
            }

            return back()->withErrors(['email' => $cMensajeError])->onlyInput('email');
        }
    }

    /**
     * Cierra la sesión del usuario.
     */
    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'lSuccess' => true,
                'cMensaje' => 'Sesión cerrada correctamente.',
                'redirect' => route('login'),
            ]);
        }

        return redirect()->route('login');
    }
}
