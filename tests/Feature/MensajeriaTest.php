<?php

namespace Tests\Feature;

use App\Models\{Role, User};
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MensajeriaTest extends TestCase
{
    use DatabaseTransactions;

    private const ID = '12345678-1234-4234-8234-123456789012';

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.openwa.url' => 'http://openwa.test/api', 'services.openwa.system_key' => 'test-system-key-never-in-html', 'services.openwa.source_system' => 'de_viaje_en_viaje']);
        Http::preventStrayRequests();
    }

    private function usuario(array $permisos): User
    {
        $usuario = User::factory()->create(['lCambiarPassword' => false]);
        $rol = Role::create(['name' => 'Mensajería prueba', 'slug' => uniqid('mensajeria-'), 'permissions' => $permisos]);
        $usuario->roles()->sync([$rol->id]);
        return $usuario;
    }

    private function sesion(array $datos = []): array
    {
        return array_merge(['id' => self::ID, 'name' => 'de-viaje-en-viaje--recepcion', 'sourceSystem' => 'de_viaje_en_viaje', 'status' => 'created', 'engineLoaded' => false, 'phone' => null, 'createdAt' => now()->toIso8601String()], $datos);
    }

    private function fingir(array $respuestas): void
    {
        Http::swap(new \Illuminate\Http\Client\Factory());
        Http::preventStrayRequests();
        Http::fake($respuestas);
    }

    public function test_modulo_y_acciones_requieren_permisos_sin_exponer_la_credencial(): void
    {
        $this->get(route('mensajeria.index'))->assertRedirect(route('login'));
        $this->actingAs($this->usuario([]))->get(route('mensajeria.index'))->assertForbidden();
        $this->actingAs($this->usuario(['mensajeria.ver']))->get(route('mensajeria.index'))->assertOk()
            ->assertSee('Mensajería')->assertSee('Inicio de sesión')->assertDontSee('test-system-key-never-in-html')->assertDontSee('id="formNuevaSesion"', false)->assertDontSee('id="qrSesion"', false);
        $this->postJson(route('mensajeria.store'), ['nombre' => 'recepcion'])->assertForbidden();
        $this->postJson(route('mensajeria.iniciar', self::ID))->assertForbidden();
        $this->getJson(route('mensajeria.qr', self::ID))->assertForbidden();
        $this->postJson(route('mensajeria.cerrar', self::ID))->assertForbidden();
        $this->deleteJson(route('mensajeria.destroy', self::ID))->assertForbidden();
        Http::assertNothingSent();
    }

    public function test_listado_es_por_backend_con_credencial_y_verifica_el_origen(): void
    {
        $this->actingAs($this->usuario(['mensajeria.ver']));
        $this->fingir(['http://openwa.test/api/integrations/sessions' => Http::response(['sourceSystem' => 'de_viaje_en_viaje', 'sessions' => [$this->sesion()]])]);
        $this->getJson(route('mensajeria.datos'))->assertOk()->assertHeader('Cache-Control', 'no-store, private')->assertJsonPath('datos.0.nombre', 'recepcion')->assertJsonPath('datos.0.id', self::ID);
        Http::assertSent(fn ($peticion) => $peticion->method() === 'GET' && $peticion->hasHeader('Authorization', 'Bearer test-system-key-never-in-html'));
        $this->fingir(['*' => Http::response(['sourceSystem' => 'otro_sistema', 'sessions' => [$this->sesion(['sourceSystem' => 'otro_sistema'])]])]);
        $this->getJson(route('mensajeria.datos'))->assertForbidden()->assertDontSee(self::ID);
    }

    public function test_crear_iniciar_qr_cerrar_y_eliminar_sesion_sin_mensajes(): void
    {
        $this->actingAs($this->usuario(['mensajeria.ver', 'mensajeria.crear', 'mensajeria.conectar', 'mensajeria.eliminar']));
        $this->fingir([
            'http://openwa.test/api/integrations/sessions' => Http::sequence()->push(['sourceSystem' => 'de_viaje_en_viaje', 'sessions' => []])->push($this->sesion()),
            'http://openwa.test/api/integrations/sessions/'.self::ID.'/qr' => Http::response(['qrCode' => 'data:image/png;base64,AAAA', 'status' => 'qr_ready']),
            'http://openwa.test/api/integrations/sessions/'.self::ID => Http::response(null, 204),
            '*' => Http::response($this->sesion()),
        ]);
        $this->postJson(route('mensajeria.store'), ['nombre' => 'recepcion', 'sourceSystem' => 'otro_sistema'])->assertCreated()->assertJsonPath('datos.id', self::ID);
        Http::assertSent(fn ($peticion) => $peticion->method() === 'POST' && $peticion->data() === ['name' => 'recepcion']);
        $this->postJson(route('mensajeria.iniciar', self::ID))->assertOk();
        $this->getJson(route('mensajeria.qr', self::ID))->assertOk()->assertJsonPath('datos.qr', 'data:image/png;base64,AAAA');
        $this->postJson(route('mensajeria.cerrar', self::ID))->assertOk();
        $this->deleteJson(route('mensajeria.destroy', self::ID))->assertOk();
        Http::assertSentCount(6);
        $this->postJson(route('mensajeria.store'), ['nombre' => 'con espacios'])->assertUnprocessable();
        $this->getJson(route('mensajeria.qr', 'id-no-valido'))->assertNotFound();
    }

    public function test_no_permite_crear_otra_sesion_si_ya_existe_una_aunque_este_desconectada(): void
    {
        config(['cache.default' => 'array']);
        $this->actingAs($this->usuario(['mensajeria.ver', 'mensajeria.crear']));
        $this->fingir(['*' => Http::response(['sourceSystem' => 'de_viaje_en_viaje', 'sessions' => [$this->sesion()]])]);
        $this->postJson(route('mensajeria.store'), ['nombre' => 'otra'])->assertStatus(409)->assertSee('Ya existe una');
        Http::assertSentCount(1);
        Http::assertNotSent(fn ($peticion) => $peticion->method() === 'POST');
    }

    public function test_fallos_de_red_y_configuracion_no_filtran_secretos_ni_respuestas_externas(): void
    {
        $this->actingAs($this->usuario(['mensajeria.ver', 'mensajeria.conectar']));
        config(['services.openwa.system_key' => null]);
        $this->getJson(route('mensajeria.datos'))->assertStatus(503);
        Http::assertNothingSent();
        config(['services.openwa.system_key' => 'test-system-key-never-in-html']);
        $this->fingir(['*' => Http::failedConnection()]);
        $this->getJson(route('mensajeria.datos'))->assertStatus(503)->assertDontSee('test-system-key-never-in-html');
        $this->fingir(['*' => Http::response(['secret' => 'no-debe-exponerse'], 401)]);
        $this->getJson(route('mensajeria.datos'))->assertStatus(503)->assertDontSee('no-debe-exponerse');
        $this->fingir(['*' => Http::response(['qrCode' => 'https://otro-sitio.test/imagen', 'status' => 'qr_ready'])]);
        $this->getJson(route('mensajeria.qr', self::ID))->assertStatus(503)->assertDontSee('otro-sitio');
    }
}
