<?php

namespace Tests\Feature;

use App\Models\{Cliente, Itinerario, Role, ServicioTuristico, User, VentaPaquete};
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UploadedItineraryTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->actingAs(User::factory()->create(['lCambiarPassword' => false]));
    }

    private function pdf(string $text = 'Original itinerary'): UploadedFile
    {
        $pdf = new \FPDF();
        $pdf->AddPage();
        $pdf->SetFont('Arial', '', 12);
        $pdf->Text(20, 20, $text);
        return UploadedFile::fake()->createWithContent('itinerario.pdf', $pdf->Output('S'));
    }

    private function data(): array
    {
        return ['cNombre' => 'Paquete con PDF', 'dPrecioCompra' => 100, 'dPrecioVenta' => 1000,
            'lUsaVigencia' => 0, 'lActivo' => 1, 'cTipoItinerario' => 'PDF'];
    }

    private function createPdfService(): ServicioTuristico
    {
        $response = $this->postJson(route('servicios.paquetes.saveData'), $this->data() + ['itinerarioPdfFile' => $this->pdf()])
            ->assertOk()->assertJsonPath('lSuccess', true);
        return ServicioTuristico::findOrFail($response->json('cData.iID'));
    }

    public function test_upload_edit_publish_and_private_pdf_access(): void
    {
        $service = $this->createPdfService();
        $this->assertTrue($service->tiene_itinerario);
        $this->assertSame([], $service->itinerarioSnapshot());
        Storage::disk('local')->assertExists($service->cItinerarioPdf);
        $original = $service->cItinerarioPdf;
        $this->get(route('servicios.paquetes.itinerarioArchivo', $service))->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->get(route('servicios.paquetes.itinerarioPdf', $service))->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->get(route('catalogo.publico.itinerario', $service->iID))->assertNotFound();
        $this->postJson(route('servicios.paquetes.saveData'), $this->data() + ['iID' => $service->iID, 'lPublicarItinerario' => 1])->assertOk();
        $this->assertSame($original, $service->fresh()->cItinerarioPdf);
        $this->get(route('catalogo.publico.show', $service->iID))->assertOk()->assertSee('Ver itinerario PDF');
        $this->get(route('catalogo.publico.itinerario', $service->iID))->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->postJson(route('servicios.paquetes.saveData'), $this->data() + ['iID' => $service->iID, 'itinerarioPdfFile' => $this->pdf('Replacement')])->assertOk();
        $this->assertNotSame($original, $service->fresh()->cItinerarioPdf);
        Storage::disk('local')->assertExists($original);
        $service->update(['lActivo' => false]);
        $this->get(route('catalogo.publico.itinerario', $service->iID))->assertNotFound();
    }

    public function test_sale_keeps_the_uploaded_version_and_can_merge_it_with_catalog_itineraries(): void
    {
        $service = $this->createPdfService();
        $original = $service->cItinerarioPdf;
        $bytes = Storage::disk('local')->get($original);
        $itinerary = Itinerario::create(['nombre' => 'Recorrido del catálogo']);
        $itinerary->dias()->create(['numero' => 1, 'titulo' => 'Llegada']);
        $catalog = ServicioTuristico::create(['cCodigo' => uniqid('CAT'), 'cNombre' => 'Otro paquete', 'dPrecioVenta' => 1000,
            'lActivo' => true, 'lUsaVigencia' => false, 'iIDItinerario' => $itinerary->id]);
        $client = Cliente::create(['cNombre' => 'Familia', 'cPrimerApellido' => 'Prueba', 'lActivo' => true]);
        $this->post(route('agencia.ventas.store'), ['iIDCliente' => $client->iID, 'accion' => 'apartar', 'items' => [
            ['iIDServicio' => $service->iID, 'iCantidad' => 1], ['iIDServicio' => $catalog->iID, 'iCantidad' => 1],
        ]])->assertSessionHasNoErrors();
        $sale = VentaPaquete::latest('iID')->firstOrFail();
        $detail = $sale->detalles()->where('iIDServicio', $service->iID)->firstOrFail();
        $this->assertSame($original, $detail->cItinerarioPdf);
        $this->postJson(route('servicios.paquetes.saveData'), $this->data() + ['iID' => $service->iID, 'itinerarioPdfFile' => $this->pdf('Changed')])->assertOk();
        $this->postJson(route('servicios.paquetes.saveData'), array_replace($this->data(), ['iID' => $service->iID, 'cTipoItinerario' => 'CATALOGO', 'iIDItinerario' => $itinerary->id]))->assertOk();
        $url = route('agencia.ventas.itinerarioArchivo', ['venta' => $sale, 'detalle' => $detail->iID]);
        $this->assertSame($bytes, $this->get($url)->assertOk()->getContent());
        $this->get(route('agencia.ventas.show', ['venta' => $sale, 'seccion' => 'itinerario']))->assertOk()->assertSee('Ver itinerario PDF');
        $merged = $this->get(route('agencia.ventas.itinerarioPdf', $sale))->assertOk()->assertHeader('Content-Type', 'application/pdf')->getContent();
        $parser = new \setasign\Fpdi\Fpdi();
        $this->assertSame(2, $parser->setSourceFile(\setasign\Fpdi\PdfParser\StreamReader::createByString($merged)));
        $sale->update(['finalizada_at' => now()]);
        $this->get(route('agencia.ventas.itinerarioPdf', $sale))->assertOk();
        $user = User::factory()->create(['lCambiarPassword' => false]);
        $user->roles()->detach();
        $role = Role::create(['name' => 'Consulta propia', 'slug' => uniqid('own'), 'permissions' => ['ventas.ver', 'itinerarios.ver']]);
        $user->roles()->attach($role);
        $this->actingAs($user)->get($url)->assertForbidden();
    }

    public function test_invalid_files_missing_pdf_and_unauthorized_uploads_are_rejected(): void
    {
        $this->postJson(route('servicios.paquetes.saveData'), $this->data())->assertStatus(422);
        $this->postJson(route('servicios.paquetes.saveData'), $this->data() + ['itinerarioPdfFile' => UploadedFile::fake()->image('foto.jpg')])->assertStatus(422);
        $this->postJson(route('servicios.paquetes.saveData'), $this->data() + ['itinerarioPdfFile' => UploadedFile::fake()->createWithContent('roto.pdf', '%PDF-1.4 archivo inválido')])->assertStatus(422);
        $service = $this->createPdfService();
        $user = User::factory()->create(['lCambiarPassword' => false]);
        $user->roles()->detach();
        $role = Role::create(['name' => 'Editor sin itinerarios', 'slug' => uniqid('editor'), 'permissions' => ['servicios.ver', 'servicios.editar']]);
        $user->roles()->attach($role);
        $this->actingAs($user)->postJson(route('servicios.paquetes.saveData'), $this->data() + ['iID' => $service->iID, 'itinerarioPdfFile' => $this->pdf()])->assertForbidden();
        $this->get(route('servicios.paquetes.itinerarioArchivo', $service))->assertForbidden();
    }
}
