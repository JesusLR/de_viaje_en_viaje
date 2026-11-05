<?php

namespace App\Http\Controllers\Agencia;

use App\Exceptions\OpenWaException;
use App\Http\Controllers\Controller;
use App\Models\CotizacionServicio;
use App\Models\Cliente;
use App\Services\OpenWaService;
use App\Support\TelefonoWhatsApp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CotizacionWhatsAppController extends Controller
{
    public function __construct(private readonly OpenWaService $openwa) {}

    private function autorizar(Request $request, CotizacionServicio $cotizacion, string $tipo): void
    {
        Gate::authorize('cotizaciones_servicios.ver');
        Gate::authorize('cotizaciones_servicios.whatsapp');
        Gate::authorize('cotizaciones_servicios.pdf');
    }

    private function sesiones(): array
    {
        $sesiones = array_values(array_filter($this->openwa->sesiones(), fn ($s) => $s['estado'] === 'ready' && $s['motorActivo']));
        if (!$sesiones) throw ValidationException::withMessages(['sesion' => 'No hay sesiones de WhatsApp conectadas. Vincula una desde Mensajería → Inicio de sesión.']);
        return array_map(fn ($s) => ['id' => $s['id'], 'nombre' => $s['nombre'], 'telefono' => $s['telefono']], $sesiones);
    }

    public function whatsappPreparar(Request $request, CotizacionServicio $cotizacion)
    {
        $datos = $request->validate(['tipo' => ['required', Rule::in(['cotizacion'])], 'telefono' => 'sometimes|required|string|max:50', 'sesion' => 'prohibited']);
        $this->autorizar($request, $cotizacion, $datos['tipo']);
        $telefonoCliente = $cotizacion->cliente_id ? Cliente::find($cotizacion->cliente_id)?->cTelefono : null;
        $numero = null;
        if ($request->has('telefono')) $numero = TelefonoWhatsApp::normalizar($datos['telefono']);
        try {
            $sesiones = $this->sesiones();
            if (!$request->has('telefono') && filled($telefonoCliente)) {
                try { $numero = TelefonoWhatsApp::normalizar($telefonoCliente); } catch (ValidationException) { /* El usuario podrá corregirlo antes de enviar. */ }
            }
            return response()->json(['lSuccess' => true, 'datos' => [
                'cliente' => $cotizacion->cliente_nombre, 'folio' => $cotizacion->folio,
                'telefono' => $numero ? '+'.$numero : ($telefonoCliente ?? ''), 'sesion' => $sesiones[0],
            ]])->header('Cache-Control', 'private, no-store');
        } catch (OpenWaException $exception) {
            return $this->error($exception->getMessage(), $exception->status);
        }
    }

    public function whatsappEnviar(Request $request, CotizacionServicio $cotizacion)
    {
        $datos = $request->validate(['tipo' => ['required', Rule::in(['cotizacion'])], 'telefono' => 'required|string|max:50', 'sesion' => 'prohibited', 'solicitud' => 'required|uuid']);
        $this->autorizar($request, $cotizacion, $datos['tipo']);
        $numero = TelefonoWhatsApp::normalizar($datos['telefono']);
        $clave = 'whatsapp-cotizacion:'.$request->user()->id.':'.$datos['solicitud'];
        $huella = hash('sha256', json_encode([$cotizacion->id, $datos['tipo'], $numero]));
        $lock = Cache::lock($clave.':lock', 180);
        if (!$lock->get()) return $this->error('Este envío ya está en proceso. Espera antes de consultar su resultado.', 409, true);
        try {
            $anterior = Cache::get($clave);
            if ($anterior) {
                abort_unless(hash_equals($anterior['huella'], $huella), 422, 'La solicitud corresponde a otro documento o destinatario. Abre una nueva confirmación.');
                if ($anterior['estado'] === 'enviado') return response()->json(['lSuccess' => true, 'datos' => $anterior['resultado']])->header('Cache-Control', 'private, no-store');
                return $this->error('No se puede confirmar el resultado del intento anterior. Revisa el chat de WhatsApp antes de iniciar otro envío.', 409, true);
            }
            $sesion = $this->sesiones()[0];
            $respuesta = app(\App\Http\Controllers\CotizacionServicioController::class)->pdf($cotizacion);
            $contenido = $respuesta->getContent();
            abort_unless(is_string($contenido) && str_starts_with($contenido, '%PDF-'), 422, 'No fue posible generar el documento PDF.');
            abort_if(strlen($contenido) > 15 * 1024 * 1024, 422, 'El PDF supera los 15 MB permitidos para este envío.');
            preg_match('/filename="?([^";]+)"?(?:;|$)/', $respuesta->headers->get('Content-Disposition', ''), $coincidencias);
            $nombre = $coincidencias[1] ?? 'Cotizacion'.' _ '.$cotizacion->folio.'.pdf';
            Cache::put($clave, ['huella' => $huella, 'estado' => 'pendiente'], now()->addDay());
            try {
                $envio = $this->openwa->enviarPdf($sesion['id'], $numero, $contenido, $nombre, $datos['solicitud'], 'Hola, aquí tienes tu cotización de viaje. Gracias por confiar en De Viaje en Viaje.');
            } catch (OpenWaException $exception) {
                return $this->error('No se pudo confirmar el envío. Revisa el chat antes de volver a enviar. '.$exception->getMessage(), $exception->status, true);
            }
            $resultado = ['telefono' => '+'.$numero, 'archivo' => $nombre, 'mensaje_id' => $envio['id'] ?? null];
            Cache::put($clave, ['huella' => $huella, 'estado' => 'enviado', 'resultado' => $resultado], now()->addDay());
            return response()->json(['lSuccess' => true, 'datos' => $resultado])->header('Cache-Control', 'private, no-store');
        } catch (OpenWaException $exception) {
            return $this->error($exception->getMessage(), $exception->status);
        } finally { $lock->release(); }
    }

    private function error(string $mensaje, int $status, bool $incierto = false)
    {
        return response()->json(['lSuccess' => false, 'mensaje' => $mensaje, 'incierto' => $incierto], $status)->header('Cache-Control', 'private, no-store');
    }
}
