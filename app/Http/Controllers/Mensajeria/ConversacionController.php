<?php

namespace App\Http\Controllers\Mensajeria;

use App\Exceptions\OpenWaException;
use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\User;
use App\Models\WhatsAppConversation;
use App\Services\OpenWaService;
use App\Support\NombreDocumento;
use App\Support\TelefonoWhatsApp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ConversacionController extends Controller
{
    public function __construct(private readonly OpenWaService $openwa) {}

    private function acceso(WhatsAppConversation $conversacion): void
    {
        abort_unless(auth()->user()->hasPermission('conversaciones.todos') || (int) $conversacion->assigned_to === (int) auth()->id(), 403);
    }

    private function evento(WhatsAppConversation $c, string $tipo, string $texto): void
    {
        DB::table('whatsapp_conversation_events')->insert(['conversation_id' => $c->id, 'user_id' => auth()->id(), 'tipo' => $tipo, 'texto' => $texto, 'created_at' => now(), 'updated_at' => now()]);
    }

    public function index()
    {
        return view('mensajeria.conversaciones');
    }

    public function datos(Request $r)
    {
        $r->validate(['buscar'=>'nullable|string|max:150','estado' => ['nullable', Rule::in(['sin_asignar', 'en_atencion', 'atendida'])]]);
        try {
            $sesiones = array_values(array_filter($this->openwa->sesiones(), fn($s)=>$s['estado']==='ready' && $s['motorActivo']));
        } catch (OpenWaException $e) {
            return $this->sinConexion('No se pudo comprobar la conexión con OpenWA. '.$e->getMessage());
        }
        if (!$sesiones) return $this->sinConexion('No hay una sesión de WhatsApp conectada.');
        try {
            $lock = Cache::lock('whatsapp:sync-conversations', 60);
            if (! Cache::has('whatsapp:sync-conversations:recent') && $lock->get()) {
                try {
                    foreach ($sesiones as $s) {
                        if ($s['estado'] !== 'ready' || ! $s['motorActivo']) {
                            continue;
                        }
                        foreach ($this->openwa->conversaciones($s['id']) as $chat) {
                            if (! is_string($chat['id'] ?? null) || ! preg_match('/^[0-9]{5,20}@(c\.us|s\.whatsapp\.net|lid)$/D', $chat['id'])) {
                                continue;
                            }
                            $c = WhatsAppConversation::firstOrCreate(['session_id' => $s['id'], 'chat_id' => $chat['id']], ['nombre' => mb_substr($chat['name'] ?? $chat['id'], 0, 255)]);
                            // Reopen only when an incoming message newer than the last observed message exists.
                            if ($c->estado === 'atendida' && (int) ($chat['timestamp'] ?? 0) > $c->last_timestamp) {
                                $history = $this->openwa->mensajes($s['id'], $chat['id']);
                                $incoming = collect($history['messages'] ?? [])->where('direction', 'incoming')->max('timestamp') ?? 0;
                                if ($incoming > $c->last_timestamp) {
                                    DB::transaction(function () use ($c) {
                                        $locked = WhatsAppConversation::lockForUpdate()->findOrFail($c->id);
                                        if ($locked->estado === 'atendida') {
                                            $locked->update(['estado' => $locked->assigned_to ? 'en_atencion' : 'sin_asignar']);
                                            $this->evento($locked, 'reapertura', 'Conversación reabierta por un mensaje entrante.');
                                        }
                                    });
                                }
                            }
                            $c->update(['unread_count'=>max(0,min(100000,(int)($chat['unreadCount']??0))),'nombre' => mb_substr($chat['name'] ?? $chat['id'], 0, 255), 'last_timestamp' => max($c->last_timestamp, (int) ($chat['timestamp'] ?? 0))]);
                        }
                    }
                    Cache::put('whatsapp:sync-conversations:recent', true, 15);
                } finally {
                    $lock->release();
                }
            }
        } catch (OpenWaException $e) {
            $error = $e->getMessage();
        }
        $q = WhatsAppConversation::with('responsable')->whereIn('session_id', array_column($sesiones,'id'));
        if (! $r->user()->hasPermission('conversaciones.todos')) {
            $q->where('assigned_to', $r->user()->id);
        }
        if ($r->filled('buscar')) $q->where(fn($query)=>$query->where('nombre','like','%'.$r->buscar.'%')->orWhere('chat_id','like','%'.$r->buscar.'%'));
        if ($r->filled('estado')) {
            $q->where('estado', $r->estado);
        }

        return response()->json(['conectado'=>true,'conversaciones' => $q->orderByDesc('last_timestamp')->paginate(30), 'usuarios' => $r->user()->hasPermission('conversaciones.asignar') ? User::where('lActivo', true)->where('lDev', false)->get()->filter(fn ($u) => $u->hasPermission('conversaciones.ver') && $u->hasPermission('conversaciones.responder'))->map(fn ($u) => ['id' => $u->id, 'nombre' => $u->nombre_completo])->values() : [], 'aviso' => $error ?? null])->header('Cache-Control', 'private, no-store');
    }

    private function sinConexion(string $aviso)
    {
        return response()->json(['conectado'=>false,'conversaciones'=>WhatsAppConversation::whereRaw('1=0')->paginate(30),'usuarios'=>[],'aviso'=>$aviso])->header('Cache-Control','private, no-store');
    }

    public function imagen(Request $r,WhatsAppConversation $conversacion) {
        $this->acceso($conversacion);$r->validate(['mensaje'=>'required|string|max:500']);
        try {$data=$this->openwa->imagenChat($conversacion->session_id,$conversacion->chat_id,$r->mensaje);}
        catch(OpenWaException $e){abort(404,'Imagen no disponible.');}
        abort_unless(in_array($data['mimetype']??'', ['image/jpeg','image/png','image/webp','image/gif']),404);
        $bytes=base64_decode($data['data']??'',true);
        abort_unless($bytes && strlen($bytes)<=15*1024*1024 && @getimagesizefromstring($bytes),404);
        $this->acceso($conversacion->fresh());
        return response($bytes)->withHeaders(['Content-Type'=>$data['mimetype'],'Cache-Control'=>'private, no-store','X-Content-Type-Options'=>'nosniff']);
    }
    public function leer(WhatsAppConversation $conversacion) {
        $this->acceso($conversacion);
        try {$result=$this->openwa->leerChat($conversacion->session_id,$conversacion->chat_id);}
        catch(OpenWaException $e){return response()->json(['message'=>'No se pudo marcar como leída.'],503);}
        if(!($result['success']??false)) return response()->json(['message'=>'WhatsApp no confirmó la lectura.'],409);
        $conversacion->update(['unread_count'=>0]);return response()->json(['ok'=>true]);
    }

    public function mensajes(Request $request, WhatsAppConversation $conversacion)
    {
        $this->acceso($conversacion);
        $request->validate(['offset' => 'nullable|integer|min:0|max:1000000']);
        try {
            $datos = $this->openwa->mensajes($conversacion->session_id, $conversacion->chat_id, (int) $request->input('offset', 0));
        } catch (OpenWaException $e) {
            return response()->json(['message' => $e->getMessage()], 503);
        }
        foreach ($datos['messages'] as &$message) {
            if (($message['type']??null)==='image' && !empty($message['mediaId'])) $message['image_url']=route('conversaciones.imagen',['conversacion'=>$conversacion->id,'mensaje'=>$message['mediaId']]);
        }
        unset($message);
        $datos['eventos'] = DB::table('whatsapp_conversation_events')->leftJoin('users', 'users.id', '=', 'user_id')->where('conversation_id', $conversacion->id)->orderByDesc('whatsapp_conversation_events.id')->limit(100)->get(['texto', 'tipo', 'users.name', 'whatsapp_conversation_events.created_at']);
        $datos['cliente'] = null;
        if (preg_match('/^([0-9]+)@(c\.us|s\.whatsapp\.net)$/D', $conversacion->chat_id, $numero)) {
            $normalizar = static function ($value) {
                try {
                    return TelefonoWhatsApp::normalizar($value ?? '');
                } catch (ValidationException) {
                    return null;
                }
            };
            $telefonoNormalizado = $normalizar('+'.$numero[1]);
            $cliente = $telefonoNormalizado ? Cliente::select(['iID', 'cNombre', 'cPrimerApellido', 'cSegundoApellido', 'cTelefono'])->get()->first(fn ($c) => $normalizar($c->cTelefono) === $telefonoNormalizado) : null;
            if ($cliente) {
                $datos['cliente'] = ['nombre' => $cliente->nombre_completo, 'ventas' => auth()->user()->hasPermission('ventas.ver') ? route('agencia.ventas.index', ['cliente' => $cliente->iID]) : null, 'apartados' => auth()->user()->hasPermission('apartados.ver') ? route('agencia.apartados.index', ['cliente' => $cliente->iID]) : null, 'cotizaciones' => auth()->user()->hasPermission('cotizaciones_servicios.ver') ? route('atencion.cotizaciones.index', ['buscar' => $cliente->nombre_completo]) : null];
            }
        }

        $this->acceso($conversacion->fresh());
        return response()->json($datos)->header('Cache-Control', 'private, no-store');
    }

    public function asignar(Request $r, WhatsAppConversation $conversacion)
    {
        Gate::authorize('conversaciones.asignar');
        $this->acceso($conversacion);
        $data = $r->validate(['usuario' => 'nullable|integer|exists:users,id']);
        $u = isset($data['usuario']) ? User::findOrFail($data['usuario']) : null;
        abort_if($u && (! $u->lActivo || ! $u->hasPermission('conversaciones.ver') || ! $u->hasPermission('conversaciones.responder')), 422, 'El responsable debe estar activo y tener permisos de conversación.');
        DB::transaction(function () use ($conversacion, $u) {
            $c = WhatsAppConversation::lockForUpdate()->findOrFail($conversacion->id);
            $this->acceso($c);
            $anterior = $c->assigned_to;
            $c->update(['assigned_to' => $u?->id, 'estado' => $u ? 'en_atencion' : 'sin_asignar']);
            $this->evento($c, 'asignacion', 'Responsable anterior: '.($anterior ?? 'ninguno').'. Nuevo responsable: '.($u?->nombre_completo ?? 'sin asignar'));
            if ($u && (int)$anterior !== (int)$u->id) {
                $u->notify(new \App\Notifications\ConversacionAsignada($c->id, $c->nombre ?: 'Cliente', auth()->user()->nombre_completo));
            }
        });

        return response()->json(['ok' => true]);
    }

    public function atender(Request $r, WhatsAppConversation $conversacion)
    {
        $data = $r->validate(['estado' => ['required', Rule::in(['en_atencion', 'atendida'])]]);
        DB::transaction(function () use ($conversacion, $data) {
            $c = WhatsAppConversation::lockForUpdate()->findOrFail($conversacion->id);
            $this->acceso($c);
            abort_unless($c->assigned_to, 422, 'Asigna un responsable primero.');
            $anterior = $c->assigned_to;
            if ($data['estado'] === 'atendida') $data['assigned_to'] = null;
            $c->update($data);
            $this->evento($c, 'estado', $data['estado'].($data['estado'] === 'atendida' ? '. Responsable liberado: '.$anterior : ''));
        });

        return response()->json(['ok' => true]);
    }

    public function nota(Request $r, WhatsAppConversation $conversacion)
    {
        $data = $r->validate(['texto' => 'required|string|max:4000']);
        $this->acceso($conversacion);
        $this->evento($conversacion, 'nota', $data['texto']);

        return response()->json(['ok' => true]);
    }

    public function documento(Request $r, WhatsAppConversation $conversacion)
    {
        $r->validate(['documento' => 'required|file|max:15360', 'solicitud' => 'required|uuid']);
        $this->acceso($conversacion);
        abort_unless($conversacion->estado === 'en_atencion' && $conversacion->assigned_to, 422, 'Asigna un responsable y abre la atención de esta conversación antes de enviar documentos.');
        $bytes = file_get_contents($r->file('documento')->getRealPath());
        $extension = strtolower($r->file('documento')->getClientOriginalExtension());
        abort_if($extension === 'pdf' && !str_starts_with($bytes, '%PDF-'),422,'El archivo debe ser un PDF válido.');

        try {
            return DB::transaction(function () use ($conversacion, $r, $bytes) {
                $c = WhatsAppConversation::lockForUpdate()->findOrFail($conversacion->id);
                $this->acceso($c);
                abort_unless($c->estado === 'en_atencion' && $c->assigned_to, 422, 'Asigna un responsable y abre la atención de esta conversación antes de enviar documentos.');
                $key = 'chat-pdf:'.auth()->id().':'.$r->solicitud;
                $hash = hash('sha256', $c->id.$bytes);
                if ($prev = Cache::get($key)) {
                    abort_unless($prev['hash'] === $hash, 422);
                    abort_unless($prev['ok'], 409, 'Revisa el chat antes de reenviar el archivo.');

                    return response()->json(['ok' => true]);
                }
                Cache::put($key, ['hash' => $hash, 'ok' => false], 86400);
                $ext = preg_replace('/[^a-z0-9]/','',strtolower($r->file('documento')->getClientOriginalExtension()));
                $nombre = NombreDocumento::parte(pathinfo($r->file('documento')->getClientOriginalName(), PATHINFO_FILENAME), 'documento').($ext ? '.'.substr($ext,0,12) : '');
                $this->openwa->documentoChat($c->session_id, $c->chat_id, $bytes, $nombre, $r->solicitud, $r->file('documento')->getMimeType() ?: 'application/octet-stream');
                Cache::put($key, ['hash' => $hash, 'ok' => true], 86400);
                $this->evento($c, 'documento', 'Archivo enviado: '.$nombre);

                return response()->json(['ok' => true]);
            });
        } catch (OpenWaException $e) {
            return response()->json(['message' => 'No se pudo confirmar el envío del archivo. Revisa el chat antes de repetir.'], 503);
        }
    }

    public function responder(Request $r, WhatsAppConversation $conversacion)
    {
        $data = $r->validate(['texto' => 'required|string|max:4000', 'solicitud' => 'required|uuid']);
        // Serializes transfers against sending: a former assignee cannot reply after reassignment.
        try {
            return DB::transaction(function () use ($conversacion, $data) {
                $c = WhatsAppConversation::lockForUpdate()->findOrFail($conversacion->id);
                $this->acceso($c);
                abort_unless($c->estado === 'en_atencion', 422, 'La conversación debe estar en atención.');
                $key = 'chat-send:'.auth()->id().':'.$data['solicitud'];
                $hash = hash('sha256', $c->id.$data['texto']);
                if ($old = Cache::get($key)) {
                    abort_unless($old['hash'] === $hash, 422);
                    abort_unless($old['ok'], 409, 'Resultado incierto. Revisa el chat antes de volver a enviar.');

                    return response()->json(['ok' => true]);
                }
                Cache::put($key, ['hash' => $hash, 'ok' => false], 86400);
                $this->openwa->responderChat($c->session_id, $c->chat_id, $data['texto'], $data['solicitud']);
                Cache::put($key,['hash' => $hash, 'ok' => true],86400);
                $this->evento($c,'respuesta','Respuesta enviada por '.auth()->user()->nombre_completo);

                return response()->json(['ok' => true]);
            });
        } catch (OpenWaException $e) {
            return response()->json(['message' => 'No se pudo confirmar el envío. Revisa el chat antes de repetir.'],503);
        }
    }
}
