<?php

namespace App\Services;

use App\Http\Controllers\Agencia\VentaPaqueteController;
use App\Models\VentaPaquete;
use App\Support\TelefonoWhatsApp;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class VentaLiquidadaWhatsAppService
{
    public function __construct(private readonly OpenWaService $openwa) {}

    // Se ejecuta después de registrar la venta. Un fallo de mensajería no revierte el cobro.
    public function enviar(VentaPaquete $venta): array
    {
        if (!\App\Models\VariableSistema::envioAutomaticoVentaWhatsApp()) return ['enviado' => false, 'mensaje' => 'El envío automático está desactivado.'];
        if ($venta->cTipoOperacion !== 'VENTA' || !$venta->finalizada_at || $venta->cancelada_at || $venta->eliminada_at || $venta->saldo_centavos !== 0) {
            return ['enviado' => false, 'mensaje' => 'La operación no es una venta liquidada disponible.'];
        }
        $clave = 'whatsapp-venta-liquidada:'.$venta->iID;
        $lock = Cache::lock($clave.':lock', 180);
        if (!$lock->get()) return ['enviado' => false, 'mensaje' => 'El envío por WhatsApp ya está en proceso.'];
        try {
            $previo = Cache::get($clave);
            if ($previo) return $previo;
            $venta->loadMissing('cliente');
            try {
                $telefono = TelefonoWhatsApp::normalizar($venta->cliente?->cTelefono ?? '');
            } catch (ValidationException) {
                return ['enviado' => false, 'mensaje' => 'No se envió el PDF por WhatsApp: el cliente no tiene un número válido.'];
            }
            if (!$this->openwa->configurado()) return ['enviado' => false, 'mensaje' => 'No se envió el PDF: WhatsApp no está configurado.'];
            $sesiones = array_values(array_filter($this->openwa->sesiones(), fn ($s) => $s['estado'] === 'ready' && $s['motorActivo']));
            if (!$sesiones) return ['enviado' => false, 'mensaje' => 'No se envió el PDF: no hay una sesión de WhatsApp conectada.'];
            $pdf = app(VentaPaqueteController::class)->comprobanteCompra($venta);
            $contenido = $pdf->getContent();
            if (!is_string($contenido) || !str_starts_with($contenido, '%PDF-') || strlen($contenido) > 15 * 1024 * 1024) {
                return ['enviado' => false, 'mensaje' => 'No se pudo preparar el PDF para WhatsApp.'];
            }
            preg_match('/filename="?([^";]+)"?(?:;|$)/', $pdf->headers->get('Content-Disposition', ''), $nombre);
            $pendiente = ['enviado' => false, 'mensaje' => 'No se pudo confirmar el envío por WhatsApp. Revisa el chat antes de enviarlo nuevamente.'];
            Cache::put($clave, $pendiente, now()->addDays(30));
            $this->openwa->enviarPdf($sesiones[0]['id'], $telefono, $contenido, $nombre[1] ?? 'Comprobante de pago_'.$venta->folio.'.pdf', (string) Str::uuid(), 'Hola, aquí tienes tu comprobante de pago. Gracias por viajar con De Viaje en Viaje.');
            $resultado = ['enviado' => true, 'mensaje' => 'El comprobante de pago se envió por WhatsApp.'];
            Cache::put($clave, $resultado, now()->addDays(30));
            return $resultado;
        } catch (\Throwable $exception) {
            // No se reintenta un envío de resultado incierto ni se exponen respuestas externas.
            return ['enviado' => false, 'mensaje' => 'No se pudo confirmar el envío por WhatsApp. Revisa la conexión y el chat antes de enviarlo nuevamente.'];
        } finally {
            $lock->release();
        }
    }
}
