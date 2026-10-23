<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use setasign\Fpdi\Fpdi;
use setasign\Fpdi\PdfParser\StreamReader;

class ItinerarioPdfService
{
    public function guardar(UploadedFile $archivo): string
    {
        try {
            // Import every page now so an unsupported or damaged file cannot break later downloads.
            $this->combinar([$archivo->getContent()]);
        } catch (\Exception $exception) {
            throw ValidationException::withMessages(['itinerarioPdfFile' => 'No se puede leer este PDF. Usa un archivo sin contraseña; si es necesario, vuelve a guardarlo con Imprimir a PDF.']);
        }
        $path = $archivo->store('itinerarios', 'local');
        if (!$path) throw new \RuntimeException('No se pudo guardar el PDF del itinerario.');
        return $path;
    }

    public function contenido(string $path): string
    {
        abort_unless(str_starts_with($path, 'itinerarios/') && Storage::disk('local')->exists($path), 404, 'El PDF del itinerario no está disponible.');
        return Storage::disk('local')->get($path);
    }

    public function combinar(array $documentos): string
    {
        $pdf = new Fpdi();
        $pdf->SetAutoPageBreak(false);
        foreach ($documentos as $documento) {
            $paginas = $pdf->setSourceFile(StreamReader::createByString($documento));
            for ($pagina = 1; $pagina <= $paginas; $pagina++) {
                $id = $pdf->importPage($pagina);
                $size = $pdf->getTemplateSize($id);
                $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
                $pdf->useTemplate($id);
            }
        }
        return $pdf->Output('S');
    }
}
