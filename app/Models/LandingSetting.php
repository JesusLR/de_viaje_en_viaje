<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LandingSetting extends Model
{
    protected $fillable = ['content', 'logo_path', 'hero_path'];
    protected $casts = ['content' => 'array'];

    public static function current(): self
    {
        $settings = static::find(1) ?? new static;
        $settings->id = 1;
        return $settings;
    }

    public static function defaults(): array
    {
        return [
            'brand' => config('app.name', 'De Viaje'),
            'hero_label' => 'Tu próximo viaje empieza aquí',
            'hero_title' => 'Haz espacio para nuevas aventuras.',
            'hero_description' => 'Descubre nuestros paquetes y encuentra ese lugar al que siempre has querido ir. Te acompañamos a planear tu próxima experiencia.',
            'hero_button' => 'Explorar paquetes',
            'packages_title' => 'Encuentra tu próxima escapada',
            'packages_description' => 'Conoce nuestros viajes disponibles y consulta los detalles con nuestro equipo.',
            'about_title' => 'Nos encargamos de los detalles. Tú disfruta el viaje.',
            'about_description' => 'Te ayudamos a elegir tu destino y organizar tu viaje con atención personal, desde la primera consulta.',
            'benefits' => [
                ['title' => 'Atención personal', 'description' => 'Cuéntanos qué viaje tienes en mente y te ayudamos a encontrar opciones.'],
                ['title' => 'Viajes para ti', 'description' => 'Explora experiencias y destinos para tu próxima aventura.'],
                ['title' => 'Reserva con confianza', 'description' => 'Consulta fechas, precios y condiciones antes de reservar.'],
            ],
            'contact_title' => 'Planeemos tu próximo viaje',
            'contact_description' => 'Consulta disponibilidad y recibe atención de nuestro equipo.',
            'phone' => '', 'whatsapp' => '', 'email' => '', 'address' => '',
            'featured_ids' => [],
            'packages_mode' => 'manual',
            'maps_embed_url' => '', 'maps_url' => '', 'facebook' => '', 'instagram' => '',
        ];
    }

    public function values(): array
    {
        return array_replace(static::defaults(), $this->content ?? []);
    }

    public function slides(): array
    {
        return $this->content['slides'] ?? ($this->hero_path ? [$this->hero_path] : []);
    }

    public function logoFilePath(): string
    {
        $disk = \Illuminate\Support\Facades\Storage::disk('local');
        return $this->logo_path && $disk->exists($this->logo_path) ? $disk->path($this->logo_path) : public_path('img/logo.jpg');
    }

    public function logoUrl(): string
    {
        return $this->logo_path && \Illuminate\Support\Facades\Storage::disk('local')->exists($this->logo_path)
            ? route('landing.image', ['image' => 'logo', 'v' => $this->updated_at?->timestamp])
            : asset('img/logo.jpg');
    }
}
