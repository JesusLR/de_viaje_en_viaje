<?php

namespace App\Http\Controllers\Configuracion;

use App\Http\Controllers\Controller;
use App\Models\{LandingSetting, ServicioTuristico};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class LandingSettingsController extends Controller
{
    public function edit()
    {
        $settings = LandingSetting::current();
        return view('configuracion.landing', [
            'settings' => $settings, 'content' => $settings->values(),
            'servicios' => ServicioTuristico::where('lActivo', true)->orderBy('cNombre')->get(),
        ]);
    }

    public function update(Request $request)
    {
        $rules = [];
        foreach (['brand', 'hero_label', 'hero_title', 'hero_button', 'packages_title', 'about_title', 'contact_title'] as $field) {
            $rules[$field] = ['required', 'string', 'max:150'];
        }
        foreach (['hero_description', 'packages_description', 'about_description', 'contact_description'] as $field) {
            $rules[$field] = ['required', 'string', 'max:1000'];
        }
        $data = $request->validate($rules + [
            'phone' => ['nullable', 'string', 'max:30'],
            'whatsapp' => ['nullable', 'regex:/^[0-9]{8,15}$/'],
            'email' => ['nullable', 'email', 'max:150'],
            'address' => ['nullable', 'string', 'max:300'],
            'packages_mode' => ['required', 'in:manual,automatic'],
            'maps_embed_url' => ['nullable', 'url:https', 'max:3000', 'regex:~^https://www\.google\.com/maps/embed\?~'],
            'maps_url' => ['nullable', 'url:https', 'max:3000', 'regex:~^https://(www\.google\.com/maps[/?]|maps\.app\.goo\.gl/|goo\.gl/maps/)~'],
            'facebook' => ['nullable', 'url:https', 'max:500', 'regex:~^https://(www\.)?facebook\.com/~'],
            'instagram' => ['nullable', 'url:https', 'max:500', 'regex:~^https://(www\.)?instagram\.com/~'],
            'benefits' => ['required', 'array', 'size:3'],
            'benefits.*.title' => ['required', 'string', 'max:100'],
            'benefits.*.description' => ['required', 'string', 'max:500'],
            'featured_ids' => ['nullable', 'array', 'max:6'],
            'featured_ids.*' => ['integer', 'distinct', 'exists:servicio_turisticos,iID'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'hero' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'carousel' => ['nullable', 'array', 'max:8'],
            'carousel.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'retained_slides' => ['nullable', 'array', 'max:8'],
            'retained_slides.*' => ['integer', 'distinct'],
            'carousel_edit' => ['nullable', 'boolean'],
        ], ['whatsapp.regex' => 'WhatsApp debe contener entre 8 y 15 dígitos, incluyendo el código de país, sin espacios ni signos.']);
        $settings = LandingSetting::current();
        $previousSlides = $settings->slides();
        $indices = $request->boolean('carousel_edit') ? ($data['retained_slides'] ?? []) : array_keys($previousSlides);
        foreach ($indices as $index) {
            if (!array_key_exists($index, $previousSlides)) {
                throw \Illuminate\Validation\ValidationException::withMessages(['carousel' => 'La portada cambió. Recarga la página antes de guardar.']);
            }
        }
        if (count($indices) + count($request->file('carousel', [])) > 8) {
            throw \Illuminate\Validation\ValidationException::withMessages(['carousel' => 'El carrusel admite un máximo de 8 imágenes.']);
        }
        $slides = array_map(fn ($index) => $previousSlides[$index], $indices);
        $settings->id = 1;
        $content = collect($data)->except(['logo', 'hero', 'carousel', 'retained_slides', 'carousel_edit'])->all();
        $content['featured_ids'] = array_map('intval', $data['featured_ids'] ?? []);
        $newPaths = $oldPaths = [];
        try {
            foreach (['logo', 'hero'] as $image) {
                if ($request->hasFile($image)) {
                    $oldPaths[] = $settings->{$image.'_path'};
                    $path = $request->file($image)->store('landing', 'local');
                    if (!$path) throw new \RuntimeException('No se pudo guardar la imagen.');
                    $newPaths[] = $path;
                    $settings->{$image.'_path'} = $path;
                }
            }
            if ($request->hasFile('hero')) $slides = [$settings->hero_path];
            foreach ($request->file('carousel', []) as $file) {
                $path = $file->store('landing', 'local');
                if (!$path) throw new \RuntimeException('No se pudo guardar la imagen.');
                $newPaths[] = $path;
                $slides[] = $path;
            }
            $content['slides'] = $slides;
            $settings->content = $content;
            $settings->hero_path = $slides[0] ?? null;
            $oldPaths = array_merge($oldPaths, array_diff($previousSlides, $slides));
            $settings->save();
        } catch (\Throwable $error) {
            Storage::disk('local')->delete($newPaths);
            throw $error;
        }
        Storage::disk('local')->delete(array_diff(array_filter($oldPaths), $slides));
        return redirect()->route('configuracion.landing.edit')->with('success', 'La página de inicio se actualizó correctamente.');
    }
}
