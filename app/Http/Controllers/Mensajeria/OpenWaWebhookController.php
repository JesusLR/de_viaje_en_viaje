<?php

namespace App\Http\Controllers\Mensajeria;

use App\Http\Controllers\Controller;
use App\Models\WhatsAppConversation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OpenWaWebhookController extends Controller
{
    public function __invoke(Request $r)
    {
        $secret = (string) config('services.openwa.webhook_secret');
        abort_unless(strlen($secret) >= 32, 503, 'Webhook no configurado.');
        abort_if(strlen($r->getContent()) > 1024 * 1024, 413);
        $expected = 'sha256='.hash_hmac('sha256', $r->getContent(), $secret);
        abort_unless(hash_equals($expected, (string) $r->header('X-OpenWA-Signature')), 401);
        $d = $r->validate(['sessionId' => 'required|uuid', 'idempotencyKey' => 'required|string|max:255', 'event' => 'required|string|max:60', 'timestamp' => 'required|date', 'data' => 'required|array']);
        abort_unless(DB::table('openwa_webhook_sessions')->where('session_id', $d['sessionId'])->exists(), 403);
        $events = ['test', 'message.received', 'message.sent', 'message.ack', 'message.failed', 'message.edited', 'message.revoked', 'session.status', 'session.disconnected', 'session.authenticated'];
        abort_unless(in_array($d['event'], $events, true), 422);
        $key = hash('sha256', $d['sessionId'].':'.$d['idempotencyKey']);
        DB::transaction(function () use ($d, $key) {
            if (! DB::table('openwa_webhook_receipts')->insertOrIgnore(['event_key' => $key, 'session_id' => $d['sessionId'], 'event' => $d['event'], 'received_at' => now()])) {
                return;
            }
            $data = $d['data'];
            $chat = $data['chatId'] ?? null;
            if (in_array($d['event'], ['message.received', 'message.sent'], true) && is_string($chat) && preg_match('/^[0-9]{5,20}@(c\.us|s\.whatsapp\.net|lid)$/D', $chat) && empty($data['isGroup'])) {
                $name = $data['contact']['pushName'] ?? $data['contact']['name'] ?? $chat;
                $c = WhatsAppConversation::firstOrCreate(['session_id' => $d['sessionId'], 'chat_id' => $chat], ['nombre' => mb_substr(is_string($name) ? $name : $chat, 0, 255)]);
                $c = WhatsAppConversation::lockForUpdate()->findOrFail($c->id);
                $incoming = $d['event'] === 'message.received' && empty($data['fromMe']);
                $timestamp = is_numeric($data['timestamp'] ?? null) ? max(0, min(4102444800, (int) $data['timestamp'])) : now()->timestamp;
                $patch = ['last_timestamp' => max($c->last_timestamp, $timestamp)];
                if ($incoming) {
                    $patch['unread_count'] = min(100000, $c->unread_count + 1);
                    if ($c->estado === 'atendida' && $timestamp >= $c->last_timestamp) {
                        $patch['estado'] = $c->assigned_to ? 'en_atencion' : 'sin_asignar';
                        DB::table('whatsapp_conversation_events')->insert(['conversation_id' => $c->id, 'user_id' => null, 'tipo' => 'reapertura', 'texto' => 'Conversación reabierta por un mensaje entrante.', 'created_at' => now(), 'updated_at' => now()]);
                    }
                }
                $c->update($patch);
            }
            DB::table('openwa_webhook_sessions')->where('session_id', $d['sessionId'])->update(['last_event_at' => now()]);
            Cache::forever('openwa:conversation-revision', (string) Str::uuid());
        });

        return response()->json(['ok' => true])->header('Cache-Control','no-store');
    }
}
