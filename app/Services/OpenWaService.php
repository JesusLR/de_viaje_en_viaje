<?php

namespace App\Services;

use App\Exceptions\OpenWaException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class OpenWaService
{
    public function configurado(): bool
    {
        return filled(config('services.openwa.url')) && filled(config('services.openwa.system_key'));
    }

    private function solicitud(string $metodo, string $ruta = '', array $datos = [], ?string $idempotencia = null): array
    {
        if (!$this->configurado()) {
            throw new OpenWaException('Falta configurar la conexión de Mensajería con OpenWA.');
        }
        $url = rtrim(config('services.openwa.url'), '/').'/integrations/sessions'.$ruta;
        try {
            $peticion = Http::acceptJson()->withToken(config('services.openwa.system_key'))->connectTimeout(5)->timeout(45)->withoutRedirecting();
            if ($idempotencia) $peticion = $peticion->withHeaders(['Idempotency-Key' => $idempotencia]);
            $respuesta = $peticion->send($metodo, $url, $datos ? ['json' => $datos] : []);
        } catch (ConnectionException $exception) {
            throw new OpenWaException('No fue posible conectar con OpenWA. Revisa su disponibilidad e intenta de nuevo.');
        }
        if (!$respuesta->successful()) {
            $mensaje = match ($respuesta->status()) {
                401, 403 => 'La credencial de Mensajería no es válida o no tiene acceso a este sistema.',
                404 => 'No se encontró la sesión en este sistema. Comprueba también que OpenWA esté actualizado.',
                400, 422 => str_ends_with($ruta, '/documents') ? 'WhatsApp no pudo aceptar el destinatario o el PDF. Confirma que el número tenga WhatsApp y que la sesión esté conectada.' : 'La sesión no está lista para esta acción o los datos no son válidos. Actualiza su estado e intenta de nuevo.',
                409 => str_ends_with($ruta, '/documents') ? 'La sesión no está lista o el envío está en proceso. Revisa su estado antes de intentar de nuevo.' : 'Ya existe una sesión con ese nombre o hay una operación en curso.',
                413 => 'El PDF supera el tamaño permitido por OpenWA.',
                429 => 'Se alcanzó el límite de solicitudes. Espera un momento antes de intentar de nuevo.',
                default => 'OpenWA no pudo completar la solicitud. Intenta de nuevo en unos momentos.',
            };
            throw new OpenWaException($mensaje, in_array($respuesta->status(), [400, 404, 409, 413, 422, 429]) ? $respuesta->status() : 503);
        }
        if ($respuesta->status() === 204) return [];
        $json = $respuesta->json();
        if (!is_array($json)) throw new OpenWaException('OpenWA devolvió una respuesta no válida.');

        return $json;
    }

    private function sesion(array $datos): array
    {
        if (($datos['sourceSystem'] ?? null) !== config('services.openwa.source_system') || !is_string($datos['id'] ?? null) || !Str::isUuid($datos['id'])) {
            throw new OpenWaException('La conexión de OpenWA no corresponde a las sesiones de este sistema.', 403);
        }
        $prefijo = str_replace('_', '-', config('services.openwa.source_system')).'--';
        $nombre = (string) ($datos['name'] ?? '');

        return [
            'id' => $datos['id'], 'nombre' => Str::after($nombre, $prefijo),
            'estado' => $datos['status'] ?? 'created', 'telefono' => $datos['phone'] ?? null,
            'cuenta' => $datos['pushName'] ?? null, 'motorActivo' => (bool) ($datos['engineLoaded'] ?? false),
            'creada' => $datos['createdAt'] ?? null,
            'error' => isset($datos['lastError']) ? Str::limit((string) $datos['lastError'], 500) : null,
        ];
    }

    public function sesiones(): array
    {
        $datos = $this->solicitud('GET');
        if (($datos['sourceSystem'] ?? null) !== config('services.openwa.source_system') || !is_array($datos['sessions'] ?? null)) {
            throw new OpenWaException('La conexión de OpenWA no corresponde a este sistema.', 403);
        }

        return array_map(fn ($sesion) => $this->sesion($sesion), $datos['sessions']);
    }

    public function crear(string $nombre): array
    {
        $lock = Cache::lock('openwa:crear-sesion:'.config('services.openwa.source_system'), 120);
        if (!$lock->get()) throw new OpenWaException('Ya se está creando una sesión.', 409);
        try {
            if ($this->sesiones()) throw new OpenWaException('Ya existe una sesión. Conecta la sesión existente para enviar documentos.', 409);
            return $this->sesion($this->solicitud('POST', '', ['name' => $nombre]));
        } finally { $lock->release(); }
    }

    public function iniciar(string $id): array
    {
        return $this->sesion($this->solicitud('POST', '/'.$id.'/start'));
    }

    public function cerrar(string $id): array
    {
        return $this->sesion($this->solicitud('POST', '/'.$id.'/logout'));
    }

    public function eliminar(string $id): void
    {
        $this->solicitud('DELETE', '/'.$id);
    }

    public function qr(string $id): array
    {
        $datos = $this->solicitud('GET', '/'.$id.'/qr');
        $qr = $datos['qrCode'] ?? null;
        if ($qr !== null && (!is_string($qr) || strlen($qr) > 1000000 || !preg_match('/\Adata:image\/png;base64,[A-Za-z0-9+\/=]+\z/', $qr))) {
            throw new OpenWaException('OpenWA devolvió un QR no válido.');
        }

        return ['qr' => $qr, 'estado' => $datos['status'] ?? null];
    }

    public function enviarPdf(string $sesion, string $telefono, string $contenido, string $nombre, string $idempotencia, string $texto = ''): array
    {
        $respuesta = $this->solicitud('POST', '/'.$sesion.'/documents', [
            'phone' => $telefono, 'base64' => base64_encode($contenido), 'filename' => $nombre, 'caption' => $texto,
        ], $idempotencia);
        if (($respuesta['status'] ?? null) !== 'sent' || (!is_null($respuesta['id'] ?? null) && !is_string($respuesta['id']))) {
            throw new OpenWaException('OpenWA no confirmó que el documento se haya enviado.');
        }
        return $respuesta;
    }
}
