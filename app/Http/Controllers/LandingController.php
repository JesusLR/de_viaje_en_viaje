<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\{LandingSetting, ServicioTuristico};
use Illuminate\Support\Facades\Storage;

class LandingController extends Controller
{
    /**
     * Muestra la página de inicio (Landing Page) pública de la Agencia de Viajes.
     */
    public function index()
    {
        $settings = LandingSetting::current();
        $content = $settings->values();
        $paquetes = ServicioTuristico::with('destino')->publicados();
        if ($content['packages_mode'] === 'manual') $paquetes->whereIn('iID', $content['featured_ids']);
        $paquetes = $paquetes->orderBy('dFechaInicioViaje')->orderBy('iID')->limit(6)->get();
        $whatsappUrl = $content['whatsapp'] ? 'https://wa.me/'.$content['whatsapp'] : null;
        return view('landing.index', compact('settings', 'content', 'paquetes', 'whatsappUrl'));
    }

    public function image(string $image)
    {
        $settings = LandingSetting::current();
        if (preg_match('/^slide-(\d+)$/', $image, $match)) {
            $path = $settings->slides()[(int) $match[1]] ?? null;
        } else {
            abort_unless(in_array($image, ['logo', 'hero'], true), 404);
            $path = $settings->{$image.'_path'};
        }
        $disk = Storage::disk('local');
        abort_unless($path && $disk->exists($path), 404);
        return response()->file($disk->path($path), ['Cache-Control' => 'public, max-age=3600']);
    }
}
