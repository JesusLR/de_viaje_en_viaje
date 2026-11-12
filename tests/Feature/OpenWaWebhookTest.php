<?php

namespace Tests\Feature;

use App\Models\WhatsAppConversation;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OpenWaWebhookTest extends TestCase
{
    use DatabaseTransactions;

    private string $secret = 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa';

    private string $session = '12345678-1234-4234-8234-123456789012';

    protected function setUp(): void
    {
        parent::setUp();
        config(['cache.default' => 'array', 'services.openwa.webhook_secret' => $this->secret]);
        Http::preventStrayRequests();
        DB::table('openwa_webhook_sessions')->insert(['session_id' => $this->session, 'registered_at' => now()]);
    }

    private function payload(string $key = 'msg1'): array
    {
        return ['sessionId' => $this->session, 'idempotencyKey' => $key, 'event' => 'message.received', 'timestamp' => now()->toIso8601String(), 'data' => ['id' => $key, 'chatId' => '529991234567@c.us', 'fromMe' => false, 'isGroup' => false, 'timestamp' => now()->timestamp, 'contact' => ['pushName' => 'Cliente webhook']]];
    }

    private function deliver(array $data, ?string $signature = null)
    {
        $body = json_encode($data);

        return $this->call('POST', route('openwa.webhook'), [], [], [], ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json', 'HTTP_X_OPENWA_SIGNATURE' => $signature ?? 'sha256='.hash_hmac('sha256', $body, $this->secret)], $body);
    }

    public function test_signed_message_creates_chat_once_and_reopens_without_responsible(): void
    {
        $this->deliver($this->payload())->assertOk();
        $c = WhatsAppConversation::where('session_id', $this->session)->firstOrFail();
        $this->assertEquals(1, $c->unread_count);
        $this->deliver($this->payload())->assertOk();
        $this->assertEquals(1, $c->fresh()->unread_count);
        $this->assertSame(1, DB::table('openwa_webhook_receipts')->where('session_id',$this->session)->count());
        $c->update(['estado' => 'atendida', 'assigned_to' => null]);
        $this->deliver($this->payload('msg2'))->assertOk();
        $this->assertSame('sin_asignar', $c->fresh()->estado);
        $this->assertEquals(2, $c->fresh()->unread_count);
        $this->assertNotEmpty(Cache::get('openwa:conversation-revision'));
        Http::assertNothingSent();
    }

    public function test_signature_unknown_session_and_missing_configuration_are_rejected(): void
    {
        $this->deliver($this->payload(), 'sha256=bad')->assertUnauthorized();
        $data = $this->payload();
        $data['sessionId'] = '22345678-1234-4234-8234-123456789012';
        $this->deliver($data)->assertForbidden();
        config(['services.openwa.webhook_secret' => null]);
        $this->deliver($this->payload())->assertStatus(503);
        $this->assertSame(0, DB::table('openwa_webhook_receipts')->where('session_id',$this->session)->count());
    }

    public function test_native_openwa_probe_is_accepted_without_creating_a_chat(): void
    {
        $data=$this->payload('probe');$data['event']='test';$data['data']=['message'=>'This is a test webhook from OpenWA'];
        $this->deliver($data)->assertOk();
        $this->assertSame(0,WhatsAppConversation::where('session_id',$this->session)->count());
        $this->assertSame(1,DB::table('openwa_webhook_receipts')->where('session_id',$this->session)->where('event','test')->count());
    }

    public function test_group_and_ack_events_do_not_create_conversations_but_trigger_refresh(): void
    {
        $data = $this->payload();
        $data['data']['chatId'] = '12345@g.us';
        $data['data']['isGroup'] = true;
        $this->deliver($data)->assertOk();
        $data = $this->payload('ack');
        $data['event'] = 'message.ack';
        $this->deliver($data)->assertOk();
        $this->assertSame(0, WhatsAppConversation::where('session_id', $this->session)->count());
        $this->assertNotEmpty(Cache::get('openwa:conversation-revision'));
    }
}
