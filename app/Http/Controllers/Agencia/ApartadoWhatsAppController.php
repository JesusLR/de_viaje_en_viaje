<?php

namespace App\Http\Controllers\Agencia;

use App\Exceptions\OpenWaException;
use App\Http\Controllers\Controller;
use App\Models\VentaPaquete;
use App\Services\OpenWaService;
use App\Support\TelefonoWhatsApp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ApartadoWhatsAppController extends Controller
{
    public function __construct(private readonly OpenWaService $openwa) {}

    private function autorizar(Request $request, VentaPaquete $venta, string $tipo): void
    {
        $module = $request->routeIs('agencia.ventas.*') ? 'ventas' : 'apartados';
        abort_if($venta->eliminada_at || $venta->cancelada_at, 422, 'Esta operación no está disponible para enviar documentos.');
        abort_unless($module === 'ventas' ? (bool) $venta->finalizada_at : ($venta->cTipoOperacion === 'APARTADO' && !$venta->finalizada_at), 422);
        Gate::authorize($module.'.ver');
        Gate::authorize($module.'.whatsapp');
        abort_unless($request->user()->hasPermission($module.'.todos') || (int) $venta->iIDUsuario === (int) $request->user()->id, 403);
        Gate::authorize($module.'.pdf');
        $venta->load(['cliente', 'detalles.servicio']);
        if ($tipo === 'abonos') abort_unless($venta->tiene_historial_abonos, 422, 'Esta operación no tiene historial de abonos.');
        if ($tipo === 'comprobante') abort_unless($venta->finalizada_at && $venta->saldo_centavos === 0, 422, 'El comprobante de pago requiere una venta liquidada.');
        if ($tipo === 'itinerario') {
            Gate::authorize('itinerarios.ver');
            Gate::authorize('itinerarios.pdf');
            abort_unless($venta->detalles->contains(fn ($detalle) => $detalle->tiene_itinerario), 422, 'El apartado no tiene itinerario disponible.');
        }
    }

    private function sesiones(): array
    {
        $sesiones = array_values(array_filter($this->openwa->sesiones(), fn ($s) => $s['estado'] === 'ready' && $s['motorActivo']));
        if (!$sesiones) throw ValidationException::withMessages(['sesion' => 'No hay sesiones de WhatsApp conectadas. Vincula una desde Mensajería → Inicio de sesión.']);
        return array_map(fn ($s) => ['id' => $s['id'], 'nombre' => $s['nombre'], 'telefono' => $s['telefono']], $sesiones);
    }

    public function whatsappPreparar(Request $request, VentaPaquete $venta)
    {
        $datos = $request->validate(['tipo' => ['required', Rule::in(['itinerario', 'abonos', 'comprobante'])], 'telefono' => 'sometimes|required|string|max:50', 'sesion' => 'prohibited']);
        $this->autorizar($request, $venta, $datos['tipo']);
        $numero = null;
        if ($request->has('telefono')) $numero = TelefonoWhatsApp::normalizar($datos['telefono']);
        try {
            $sesiones = $this->sesiones();
            if (!$request->has('telefono') && filled($venta->cliente?->cTelefono)) {
                try { $numero = TelefonoWhatsApp::normalizar($venta->cliente->cTelefono); } catch (ValidationException) { /* El usuario podrá corregirlo antes de enviar. */ }
            }
            return response()->json(['lSuccess' => true, 'datos' => [
                'cliente' => $venta->cliente?->nombre_completo, 'folio' => $venta->folio,
                'telefono' => $numero ? '+'.$numero : ($venta->cliente?->cTelefono ?? ''), 'sesion' => $sesiones[0],
            ]])->header('Cache-Control', 'private, no-store');
        } catch (OpenWaException $exception) {
            return $this->error($exception->getMessage(), $exception->status);
        }
    }

    public function whatsappEnviar(Request $request, VentaPaquete $venta)
    {
        $datos = $request->validate(['tipo' => ['required', Rule::in(['itinerario', 'abonos', 'comprobante'])], 'telefono' => 'required|string|max:50', 'sesion' => 'prohibited', 'solicitud' => 'required|uuid']);
        $this->autorizar($request, $venta, $datos['tipo']);
        $numero = TelefonoWhatsApp::normalizar($datos['telefono']);
        $clave = 'whatsapp-apartado:'.$request->user()->id.':'.$datos['solicitud'];
        $huella = hash('sha256', json_encode([$venta->iID, $datos['tipo'], $numero]));
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
            // Se reutiliza exactamente el mismo PDF privado que se descarga desde el apartado.
            $pdfs = app(VentaPaqueteController::class);
            $respuesta = match ($datos['tipo']) {
                'itinerario' => $pdfs->itinerarioPdf($venta),
                'comprobante' => $pdfs->comprobanteCompra($venta),
                default => $pdfs->comprobante($venta),
            };
            $contenido = $respuesta->getContent();
            abort_unless(is_string($contenido) && str_starts_with($contenido, '%PDF-'), 422, 'No fue posible generar el documento PDF.');
            abort_if(strlen($contenido) > 15 * 1024 * 1024, 422, 'El PDF supera los 15 MB permitidos para este envío.');
            preg_match('/filename="?([^";]+)"?(?:;|$)/', $respuesta->headers->get('Content-Disposition', ''), $coincidencias);
            $nombre = $coincidencias[1] ?? ($datos['tipo'] === 'itinerario' ? 'Itinerario' : 'Abono').' _ '.$venta->folio.'.pdf';
            Cache::put($clave, ['huella' => $huella, 'estado' => 'pendiente'], now()->addDay());
            try {
                $envio = $this->openwa->enviarPdf($sesion['id'], $numero, $contenido, $nombre, $datos['solicitud'], $datos['tipo'] === 'comprobante' ? 'Hola, aquí tienes tu comprobante de pago. Gracias por viajar con De Viaje en Viaje.' : ($datos['tipo'] === 'itinerario' ? 'Hola, aquí tienes tu itinerario de viaje. Gracias por viajar con De Viaje en Viaje.' : 'Hola, aquí tienes tu historial de abonos. Gracias por viajar con De Viaje en Viaje.'));
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
