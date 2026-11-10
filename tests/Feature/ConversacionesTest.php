<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use App\Models\WhatsAppConversation;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

class ConversacionesTest extends TestCase
{
    use DatabaseTransactions;

    private function userWith(array $p): User
    {
        $u = User::factory()->create(['lCambiarPassword' => false, 'lActivo' => true]);
        $role = Role::create(['name' => 'Chats', 'slug' => uniqid('chat'), 'permissions' => $p]);
        $u->roles()->sync([$role->id]);

        return $u;
    }

    protected function setUp(): void
    {
        parent::setUp();
        config(['cache.default' => 'array', 'services.openwa.url' => 'http://openwa.test/api', 'services.openwa.system_key' => 'test']);
        Http::preventStrayRequests();
        Http::fake(function ($r) {
            if (str_contains($r->url(), '/messages')) {
                return Http::response(['total' => 1, 'messages' => [['id' => 'm1', 'body' => 'Hola', 'direction' => 'incoming', 'timestamp' => 1]]]);
            }if (str_contains($r->url(), '/pdf')) return Http::response(['id'=>'p1','status'=>'sent']);
            if (str_contains($r->url(), '/text')) {
                return Http::response(['id' => 'm2', 'status' => 'sent']);
            }

return Http::response(['sourceSystem' => 'de_viaje_en_viaje', 'sessions' => []]);
        });
    }

    public function test_scope_assignment_transfer_notes_and_text(): void
    {
        $p = ['conversaciones.ver', 'conversaciones.responder', 'conversaciones.nota'];
        $a = $this->userWith($p);
        $b = $this->userWith($p);
        $boss = $this->userWith(array_merge($p, ['conversaciones.todos', 'conversaciones.asignar', 'conversaciones.atender']));
        $c = WhatsAppConversation::create(['session_id' => '12345678-1234-4234-8234-123456789012', 'chat_id' => '529991234567@c.us', 'nombre' => 'Cliente']);
        $this->actingAs($a)->get(route('conversaciones.index'))->assertOk()->assertSee('Conversaciones de WhatsApp');
        $this->getJson(route('conversaciones.mensajes', $c))->assertForbidden();
        $this->getJson(route('conversaciones.datos'))->assertJsonPath('conversaciones.total', 0);
        $this->postJson(route('conversaciones.asignar', $c), ['usuario' => $a->id])->assertForbidden();
        $this->actingAs($boss)->postJson(route('conversaciones.asignar', $c), ['usuario' => $a->id])->assertOk();
        $this->actingAs($a)->getJson(route('conversaciones.mensajes', $c))->assertOk()->assertJsonPath('messages.0.body', 'Hola');
        $this->postJson(route('conversaciones.nota', $c), ['texto' => 'Nota privada'])->assertOk();
        Http::assertNotSent(fn ($r) => $r->method() === 'POST');
        $data = ['texto' => 'Respuesta', 'solicitud' => (string) Str::uuid()];
        $this->postJson(route('conversaciones.responder', $c), $data)->assertOk();
        $this->postJson(route('conversaciones.responder', $c), $data)->assertOk();
        $this->actingAs($boss)->postJson(route('conversaciones.asignar', $c), ['usuario' => $b->id])->assertOk();
        $this->actingAs($a)->getJson(route('conversaciones.mensajes', $c))->assertForbidden();
        $this->postJson(route('conversaciones.responder', $c), $data)->assertForbidden();
        $this->actingAs($b)->getJson(route('conversaciones.mensajes', $c))->assertOk()->assertSee('Nota privada');
        $this->assertSame(2, DB::table('whatsapp_conversation_events')->where('conversation_id', $c->id)->where('tipo', 'asignacion')->count());
    }

    public function test_pdf_enforces_assignment_and_validates_file(): void
    {
        $a=$this->userWith(['conversaciones.ver','conversaciones.responder']);
        $b=$this->userWith(['conversaciones.ver','conversaciones.responder']);
        $c=WhatsAppConversation::create(['session_id'=>'12345678-1234-4234-8234-123456789012','chat_id'=>'123456789@lid','assigned_to'=>$a->id,'estado'=>'en_atencion']);
        $datos=['documento'=>\Illuminate\Http\UploadedFile::fake()->createWithContent('Documento.pdf',"%PDF-1.4\nTest\n%%EOF"),'solicitud'=>(string)\Illuminate\Support\Str::uuid()];
        $this->actingAs($b)->postJson(route('conversaciones.documento',$c),$datos)->assertForbidden();
        $this->actingAs($a)->postJson(route('conversaciones.documento',$c),$datos)->assertOk();
        Http::assertSent(fn($r)=>str_contains($r->url(),'/conversations/pdf')&&$r['chatId']==='123456789@lid');
        $this->postJson(route('conversaciones.documento',$c),['documento'=>\Illuminate\Http\UploadedFile::fake()->createWithContent('falso.pdf','no es pdf'),'solicitud'=>(string)\Illuminate\Support\Str::uuid()])->assertUnprocessable();
    }

    public function test_incoming_message_reopens_closed_conversation(): void
    {
        $u=$this->userWith(['conversaciones.ver','conversaciones.todos']);$this->actingAs($u);
        $session='12345678-1234-4234-8234-123456789012';
        $c=WhatsAppConversation::create(['session_id'=>$session,'chat_id'=>'529991234567@c.us','assigned_to'=>$u->id,'estado'=>'atendida','last_timestamp'=>100]);
        Http::swap(new \Illuminate\Http\Client\Factory());Http::preventStrayRequests();
        Http::fake(function($r) use($session){
            if(str_contains($r->url(),'/messages')) return Http::response(['messages'=>[['timestamp'=>101,'direction'=>'incoming']]]);
            if(str_ends_with($r->url(),'/conversations')) return Http::response([['id'=>'529991234567@c.us','name'=>'Cliente','timestamp'=>101]]);
            return Http::response(['sourceSystem'=>'de_viaje_en_viaje','sessions'=>[['id'=>$session,'sourceSystem'=>'de_viaje_en_viaje','name'=>'Prueba','status'=>'ready','engineLoaded'=>true]]]);
        });
        $this->getJson(route('conversaciones.datos'))->assertOk();
        $this->assertSame('en_atencion',$c->fresh()->estado);
        $this->assertSame(1,DB::table('whatsapp_conversation_events')->where('conversation_id',$c->id)->where('tipo','reapertura')->count());
        $this->getJson(route('conversaciones.datos'))->assertOk();
        $this->assertSame(1,DB::table('whatsapp_conversation_events')->where('conversation_id',$c->id)->where('tipo','reapertura')->count());
    }

    public function test_unassigned_document_returns_explanation_without_sending(): void
    {
        $u=$this->userWith(['conversaciones.ver','conversaciones.todos','conversaciones.responder']);
        $c=WhatsAppConversation::create(['session_id'=>'12345678-1234-4234-8234-123456789012','chat_id'=>'529991234567@c.us','estado'=>'sin_asignar']);
        $this->actingAs($u)->postJson(route('conversaciones.documento',$c),['documento'=>\Illuminate\Http\UploadedFile::fake()->createWithContent('prueba.pdf',"%PDF-1.4\n%%EOF"),'solicitud'=>(string)\Illuminate\Support\Str::uuid()])->assertUnprocessable()->assertJsonPath('message','Asigna un responsable y abre la atención de esta conversación antes de enviar documentos.');
        Http::assertNothingSent();
    }

    public function test_assignment_notifies_only_new_owner_and_bell_marks_all_read(): void
    {
        $boss=$this->userWith(['conversaciones.ver','conversaciones.todos','conversaciones.asignar']);
        $a=$this->userWith(['conversaciones.ver','conversaciones.responder']);
        $b=$this->userWith(['conversaciones.ver','conversaciones.responder']);
        $c=WhatsAppConversation::create(['session_id'=>'12345678-1234-4234-8234-123456789012','chat_id'=>'529991234567@c.us','nombre'=>'Cliente de prueba']);
        $this->actingAs($boss)->postJson(route('conversaciones.asignar',$c),['usuario'=>$a->id])->assertOk();
        $this->postJson(route('conversaciones.asignar',$c),['usuario'=>$a->id])->assertOk();
        $this->assertSame(1,$a->notifications()->count());
        $this->assertSame(0,$b->notifications()->count());
        $this->actingAs($a)->getJson(route('notificaciones.index'))->assertOk()->assertJsonPath('sin_leer',1)->assertJsonPath('notificaciones.0.titulo','Conversación asignada');
        $this->postJson(route('notificaciones.leerTodas'))->assertOk();
        $this->getJson(route('notificaciones.index'))->assertJsonPath('sin_leer',0);
        $this->actingAs($boss)->postJson(route('conversaciones.asignar',$c),['usuario'=>$b->id])->assertOk();
        $this->assertSame(1,$b->notifications()->count());
        $this->assertSame(0,$boss->notifications()->count());
    }

    public function test_closed_conversation_cannot_send_and_inactive_assignee_rejected(): void
    {
        $p = ['conversaciones.ver', 'conversaciones.todos', 'conversaciones.responder', 'conversaciones.asignar', 'conversaciones.atender'];
        $u = $this->userWith($p);
        $this->actingAs($u);
        $c = WhatsAppConversation::create(['session_id' => '12345678-1234-4234-8234-123456789012', 'chat_id' => '529991234567@c.us', 'assigned_to' => $u->id, 'estado' => 'en_atencion']);
        $this->postJson(route('conversaciones.atender', $c), ['estado' => 'atendida'])->assertOk();
        $this->postJson(route('conversaciones.responder', $c), ['texto' => 'No enviar', 'solicitud' => (string) Str::uuid()])->assertUnprocessable();
        $this->assertNull($c->fresh()->assigned_to);
        $this->assertSame('atendida',$c->fresh()->estado);
        $this->postJson(route('conversaciones.atender', $c), ['estado' => 'en_atencion'])->assertUnprocessable();
        $this->postJson(route('conversaciones.asignar', $c), ['usuario' => $u->id])->assertOk();
        $bad = $this->userWith($p);
        $bad->update(['lActivo' => false]);
        $this->postJson(route('conversaciones.asignar',$c), ['usuario' => $bad->id])->assertUnprocessable();
        Http::assertNothingSent();
    }
}
