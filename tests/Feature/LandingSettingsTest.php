<?php

namespace Tests\Feature;

use App\Models\{LandingSetting, ServicioTuristico, User};
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LandingSettingsTest extends TestCase
{
    use DatabaseTransactions;

    public function test_publico_ve_la_landing_pero_no_puede_editarla(): void
    {
        $this->get(route('landing'))->assertOk()->assertSee('Paquetes');
        $this->get(route('configuracion.landing.edit'))->assertRedirect(route('login'));
        $this->put(route('configuracion.landing.update'), LandingSetting::defaults())->assertRedirect(route('login'));
    }

    public function test_editor_guarda_contenido_imagenes_y_paquetes_reales(): void
    {
        Storage::fake('local');
        $user = User::factory()->create(['lCambiarPassword' => false]);
        $paquete = ServicioTuristico::create(['cCodigo' => uniqid('LAND'), 'cNombre' => 'Escapada de prueba', 'dPrecioVenta' => 2500, 'lActivo' => true, 'lUsaVigencia' => false]);
        $vencido = ServicioTuristico::create(['cCodigo' => uniqid('LAND'), 'cNombre' => 'Viaje vencido oculto', 'dPrecioVenta' => 1000, 'lActivo' => true, 'lUsaVigencia' => true, 'dFechaInicioVigencia' => today()->subDays(3), 'dFechaFinVigencia' => today()->subDay()]);
        $datos = array_merge(LandingSetting::defaults(), [
            'hero_title' => 'Una nueva portada', 'brand' => 'Agencia prueba', 'whatsapp' => '525512345678',
            'featured_ids' => [$paquete->iID, $vencido->iID], 'logo' => UploadedFile::fake()->image('logo.png'), 'hero' => UploadedFile::fake()->image('portada.jpg'),
        ]);
        $this->actingAs($user)->get(route('configuracion.landing.edit'))->assertOk()->assertSee('Guardar cambios');
        $this->put(route('configuracion.landing.update'), $datos)->assertSessionHasNoErrors()->assertRedirect(route('configuracion.landing.edit'));
        $settings = LandingSetting::current();
        $this->assertSame('Una nueva portada', $settings->content['hero_title']);
        $this->assertSame(Storage::disk('local')->path($settings->logo_path), $settings->logoFilePath());
        $this->get(route('dashboard'))->assertOk()->assertSee($settings->logoUrl(), false);
        Storage::disk('local')->assertExists($settings->hero_path);
        $this->get(route('landing'))->assertOk()->assertSee('Una nueva portada')->assertSee('Escapada de prueba')->assertSee('2,500.00')->assertDontSee('Viaje vencido oculto')->assertSee('https://wa.me/525512345678');
        $this->get(route('landing.image', 'hero'))->assertOk();
        $this->get(route('landing.image', 'otro'))->assertNotFound();
        $anterior = $settings->hero_path;
        $datos['hero'] = UploadedFile::fake()->image('otra.jpg');
        $this->put(route('configuracion.landing.update'), $datos)->assertSessionHasNoErrors();
        Storage::disk('local')->assertMissing($anterior);
    }

    public function test_editor_rechaza_imagenes_y_contactos_invalidos(): void
    {
        $this->actingAs(User::factory()->create(['lCambiarPassword' => false]));
        $datos = array_merge(LandingSetting::defaults(), [
            'whatsapp' => 'javascript:alert(1)', 'email' => 'correo incorrecto',
            'hero' => UploadedFile::fake()->create('archivo.svg', 10, 'image/svg+xml'),
            'featured_ids' => [999999999],
        ]);
        $this->put(route('configuracion.landing.update'), $datos)->assertSessionHasErrors(['whatsapp', 'email', 'hero', 'featured_ids.0']);
    }

    public function test_carrusel_se_ordena_se_quitan_fotos_y_se_publican_mapa_y_contactos(): void
    {
        Storage::fake('local');
        $this->actingAs(User::factory()->create(['lCambiarPassword' => false]));
        $datos = array_merge(LandingSetting::defaults(), [
            'carousel_edit' => 1,
            'carousel' => [UploadedFile::fake()->image('uno.jpg'), UploadedFile::fake()->image('dos.jpg')],
            'maps_embed_url' => 'https://www.google.com/maps/embed?pb=prueba',
            'maps_url' => 'https://maps.app.goo.gl/prueba',
            'facebook' => 'https://www.facebook.com/agencia', 'instagram' => 'https://www.instagram.com/agencia',
        ]);
        $this->put(route('configuracion.landing.update'), $datos)->assertSessionHasNoErrors();
        $slides = LandingSetting::current()->slides();
        $this->assertCount(2, $slides);
        $this->get(route('landing'))->assertOk()->assertSee('portadaCarrusel')->assertSee('Imagen siguiente')->assertSee('Visítanos')->assertSee('https://www.google.com/maps/embed?pb=prueba')->assertSee('Instagram');
        $this->get(route('landing.image', 'slide-0'))->assertOk();
        unset($datos['carousel']);
        $datos['retained_slides'] = [1, 0];
        $this->put(route('configuracion.landing.update'), $datos)->assertSessionHasNoErrors();
        $this->assertSame([$slides[1], $slides[0]], LandingSetting::current()->slides());
        $datos['retained_slides'] = [0];
        $this->put(route('configuracion.landing.update'), $datos)->assertSessionHasNoErrors();
        Storage::disk('local')->assertMissing($slides[0]);
        Storage::disk('local')->assertExists($slides[1]);
        $this->get(route('landing.image', 'slide-1'))->assertNotFound();
        $datos['maps_embed_url'] = 'https://example.com/maps/embed?pb=prueba';
        $this->put(route('configuracion.landing.update'), $datos)->assertSessionHasErrors('maps_embed_url');
    }

    public function test_seleccion_manual_vacia_no_publica_servicios_y_limita_el_carrusel(): void
    {
        $this->actingAs(User::factory()->create(['lCambiarPassword' => false]));
        $datos = array_merge(LandingSetting::defaults(), ['carousel_edit' => 1]);
        $this->put(route('configuracion.landing.update'), $datos)->assertSessionHasNoErrors();
        $this->get(route('landing'))->assertOk()->assertViewHas('paquetes', fn ($paquetes) => $paquetes->isEmpty());
        $datos['carousel'] = array_fill(0, 9, UploadedFile::fake()->image('foto.jpg'));
        $this->put(route('configuracion.landing.update'), $datos)->assertSessionHasErrors('carousel');
    }

    public function test_landing_publica_solo_en_vigencia_y_muestra_las_fechas_del_viaje(): void
    {
        $this->actingAs(User::factory()->create(['lCambiarPassword' => false]));
        $datos = ['cCodigo' => uniqid('LAND'), 'cNombre' => 'Viaje con publicación independiente', 'lActivo' => true, 'lUsaVigencia' => true, 'dPrecioVenta' => 1000,
            'dFechaInicioVigencia' => today()->subDay(), 'dFechaFinVigencia' => today()->addDay(),
            'dFechaInicioViaje' => today()->addMonths(2), 'dFechaFinViaje' => today()->addMonths(2)->addWeek()];
        $servicio = ServicioTuristico::create($datos);
        $programado = ServicioTuristico::create(array_merge($datos, ['cCodigo' => uniqid('LAND'), 'dFechaInicioVigencia' => today()->addWeek(), 'dFechaFinVigencia' => today()->addMonth()]));
        $this->put(route('configuracion.landing.update'), array_merge(LandingSetting::defaults(), ['featured_ids' => [$servicio->iID, $programado->iID]]))->assertSessionHasNoErrors();
        $this->get(route('landing'))->assertOk()->assertSee($servicio->dFechaInicioViaje->format('d/m/Y'))
            ->assertSee($servicio->dFechaFinViaje->format('d/m/Y'))
            ->assertViewHas('paquetes', fn ($paquetes) => $paquetes->pluck('iID')->all() === [$servicio->iID]);
        $servicio->update(['lActivo' => false]);
        $this->get(route('landing'))->assertViewHas('paquetes', fn ($paquetes) => $paquetes->isEmpty());
    }
}
