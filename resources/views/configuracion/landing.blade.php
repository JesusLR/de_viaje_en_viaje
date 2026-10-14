@extends('layouts.app')
@section('titulo', 'Configuración de la página de inicio')
@section('contenido')
<div class="apartados-panel landing-editor">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div class="d-flex align-items-center gap-3"><span class="apartados-emblema"><i class="fas fa-globe" aria-hidden="true"></i></span><div><h3 class="fw-bold mb-1">Página de inicio</h3><p class="text-muted small mb-0">Personaliza la presentación pública de tu agencia.</p></div></div>
        <a class="apartado-icono nuevo-apartado" href="{{ route('landing') }}" target="_blank" rel="noopener"><i class="fas fa-external-link-alt" aria-hidden="true"></i>Ver página</a>
    </div>
    <form action="{{ route('configuracion.landing.update') }}" method="POST" enctype="multipart/form-data">
        @csrf @method('PUT')
        <div class="row g-4">
            <div class="col-lg-8">
                @foreach([
                    'Portada' => ['brand' => 'Nombre de la agencia', 'hero_label' => 'Texto superior', 'hero_title' => 'Título principal', 'hero_description' => 'Descripción', 'hero_button' => 'Texto del botón'],
                    'Paquetes destacados' => ['packages_title' => 'Título de la sección', 'packages_description' => 'Descripción de la sección'],
                    'Nuestra agencia' => ['about_title' => 'Título', 'about_description' => 'Descripción'],
                    'Contacto' => ['contact_title' => 'Título', 'contact_description' => 'Descripción', 'phone' => 'Teléfono', 'whatsapp' => 'WhatsApp con código de país', 'email' => 'Correo electrónico', 'address' => 'Dirección', 'maps_embed_url' => 'Mapa de Google Maps (URL para insertar)', 'maps_url' => 'Enlace para abrir la ubicación en Maps', 'facebook' => 'Facebook', 'instagram' => 'Instagram']
                ] as $section => $fields)
                <section class="apartados-filtros mb-4">
                    <h5 class="editor-titulo">{{ $section }}</h5>
                    <div class="row g-3">
                        @foreach($fields as $field => $label)
                        <div class="{{ in_array($field, ['phone', 'whatsapp', 'email']) ? 'col-md-6' : 'col-12' }}">
                            <label for="{{ $field }}" class="form-label">{{ $label }}</label>
                            @if(str_contains($field, 'description'))
                                <textarea id="{{ $field }}" name="{{ $field }}" rows="3" class="form-control @error($field) is-invalid @enderror" maxlength="1000" required>{{ old($field, $content[$field]) }}</textarea>
                            @else
                                <input id="{{ $field }}" name="{{ $field }}" type="{{ $field === 'email' ? 'email' : (in_array($field, ['maps_embed_url','maps_url','facebook','instagram']) ? 'url' : 'text') }}" value="{{ old($field, $content[$field]) }}" class="form-control @error($field) is-invalid @enderror" maxlength="{{ $field === 'address' ? 300 : (str_starts_with($field, 'maps_') ? 3000 : (in_array($field, ['facebook','instagram']) ? 500 : ($field === 'phone' ? 30 : ($field === 'whatsapp' ? 15 : 150)))) }}" @if(!in_array($field, ['phone','whatsapp','email','address','maps_embed_url','maps_url','facebook','instagram'])) required @endif>
                            @endif
                            @error($field)<div class="invalid-feedback">{{ $message }}</div>@enderror
                            @if($field === 'maps_embed_url')<small class="text-muted">En Google Maps, abre tu ubicación → Compartir → Insertar un mapa → Copiar HTML. Pega aquí únicamente la URL que aparece dentro de src="…", que empieza con https://www.google.com/maps/embed?.</small>@endif
                            @if($field === 'maps_url')<small class="text-muted">Pega el enlace para compartir la ubicación de Google Maps.</small>@endif
                            @if($field === 'whatsapp')<small class="text-muted">Solo dígitos, por ejemplo: 525512345678. Activa el botón para solicitar información.</small>@endif
                        </div>
                        @endforeach
                        @if($section === 'Paquetes destacados')
                        <div class="col-12"><label for="packages_mode" class="form-label">Servicios que se mostrarán</label><select id="packages_mode" name="packages_mode" class="form-select"><option value="manual" @selected(old('packages_mode', $content['packages_mode']) === 'manual')>Solo los servicios que yo seleccione</option><option value="automatic" @selected(old('packages_mode', $content['packages_mode']) === 'automatic')>Selección automática de disponibles</option></select></div>
                        <div class="col-12"><label for="featured_ids"  class="form-label">Paquetes a mostrar (máximo 6)</label><select id="featured_ids" name="featured_ids[]" class="form-select" multiple>
                            @foreach($servicios as $servicio)<option value="{{ $servicio->iID }}" @selected(in_array($servicio->iID, (session()->hasOldInput() ? old('featured_ids', []) : $content['featured_ids'])))>{{ $servicio->cCodigo }} · {{ $servicio->cNombre }}</option>@endforeach
                        </select><small class="text-muted">En selección manual solo aparecen los servicios elegidos. Sin selección, no se publica ninguno. En modo automático se muestran hasta 6 disponibles. Los inactivos o vencidos se ocultan.</small>@error('featured_ids')<div class="text-danger small">{{ $message }}</div>@enderror</div>
                        @endif
                    </div>
                </section>
                @endforeach
                <section class="apartados-filtros mb-4"><h5 class="editor-titulo">Beneficios de viajar con la agencia</h5>
                    @foreach($content['benefits'] as $index => $benefit)
                    <div class="{{ $index > 0 ? 'border-top pt-3 mt-3' : '' }}">
                        <label for="benefitTitle{{ $index }}" class="form-label">Beneficio {{ $index + 1 }}</label><input id="benefitTitle{{ $index }}" class="form-control mb-2" name="benefits[{{ $index }}][title]" value="{{ old('benefits.'.$index.'.title', $benefit['title']) }}" required maxlength="100">
                        <label for="benefitDescription{{ $index }}" class="form-label">Descripción</label><textarea id="benefitDescription{{ $index }}" class="form-control" name="benefits[{{ $index }}][description]" rows="2" required maxlength="500">{{ old('benefits.'.$index.'.description', $benefit['description']) }}</textarea>
                    </div>
                    @endforeach
                </section>
            </div>
            <div class="col-lg-4">
                <section class="apartados-filtros editor-imagenes">
                    <h5 class="editor-titulo"><i class="fas fa-images me-2" aria-hidden="true"></i>Imágenes</h5>
                    @foreach(['logo' => 'Logo de la agencia'] as $image => $label)
                        <label for="{{ $image }}" class="form-label">{{ $label }}</label>
                        @if($settings->{$image.'_path'})
                            <img class="editor-preview {{ $image === 'logo' ? 'editor-logo' : '' }} mb-3" src="{{ route('landing.image', ['image' => $image, 'v' => $settings->updated_at?->timestamp]) }}" alt="{{ $label }}">
                        @elseif($image === 'logo')
                            <img class="editor-preview editor-logo mb-3" src="{{ asset('img/logo.jpg') }}" alt="Logo actual">
                        @endif
                        <input id="{{ $image }}" type="file" name="{{ $image }}" class="form-control mb-2" accept="image/jpeg,image/png,image/webp">
                        <p class="text-muted small mb-4">JPG, PNG o WebP. Máximo {{ $image === 'hero' ? '5 MB. Recomendado: 1600 × 1000 px.' : '2 MB.' }}</p>
                        @error($image)<div class="text-danger small mb-3">{{ $message }}</div>@enderror
                    @endforeach
                    <h5 class="editor-titulo mt-4">Carrusel de portada</h5>
                    <input type="hidden" name="carousel_edit" value="1">
                    <div id="portadaSlides">
                        @foreach($settings->slides() as $index => $slide)
                            <div class="editor-slide mb-3">
                                <input type="hidden" name="retained_slides[]" value="{{ $index }}">
                                <img src="{{ route('landing.image', ['image' => 'slide-'.$index, 'v' => $settings->updated_at?->timestamp]) }}" class="editor-preview mb-2" alt="Imagen de portada {{ $index + 1 }}">
                                <div class="d-flex justify-content-end gap-1">
                                    <button type="button" class="apartado-icono slide-subir" title="Mover antes" aria-label="Mover imagen antes"><i class="fas fa-arrow-up" aria-hidden="true"></i></button>
                                    <button type="button" class="apartado-icono slide-bajar" title="Mover después" aria-label="Mover imagen después"><i class="fas fa-arrow-down" aria-hidden="true"></i></button>
                                    <button type="button" class="apartado-icono accion-cancelar slide-quitar" title="Quitar imagen" aria-label="Quitar imagen"><i class="fas fa-trash-alt" aria-hidden="true"></i></button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <label for="carousel" class="form-label">Agregar imágenes</label>
                    <input id="carousel" name="carousel[]" type="file" class="form-control" accept="image/jpeg,image/png,image/webp" multiple>
                    <p class="text-muted small mt-2">Hasta 8 imágenes en total. JPG, PNG o WebP, máximo 5 MB cada una. Las nuevas se agregan al final. Guarda para aplicar el orden o quitar imágenes.</p>
                    @error('carousel')<div class="text-danger small mb-3">{{ $message }}</div>@enderror
                    <div class="editor-ayuda"><i class="fas fa-info-circle me-1" aria-hidden="true"></i>Los cambios se publican al guardar. El botón «Ver página» abre la versión guardada.</div>
                    <button type="submit" class="apartado-icono nuevo-apartado w-100 mt-3"><i class="fas fa-save" aria-hidden="true"></i>Guardar cambios</button>
                </section>
            </div>
        </div>
    </form>
</div>
@include('agencia.partials.avisos')
@endsection
@push('styles')
<link rel="stylesheet" href="{{ asset('css/agencia-grid.css') }}?v={{ filemtime(public_path('css/agencia-grid.css')) }}">
<style>
.landing-editor .editor-titulo{font-size:15px;font-weight:650;color:#25324b;margin-bottom:22px}.landing-editor .form-control{font-size:13px}.editor-preview{width:100%;max-height:180px;object-fit:cover;border-radius:12px;border:1px solid #e8edf4}.editor-preview.editor-logo{object-fit:contain;background:white;padding:14px;height:120px}.editor-ayuda{padding:14px;background:#f3f5fa;border-radius:12px;font-size:12px;color:#64748b;line-height:1.7}.editor-imagenes{position:sticky;top:20px}.landing-editor small{font-size:11px}
</style>
@endpush

@push('scripts')
<script>
document.getElementById('portadaSlides').addEventListener('click', function (event) {
    const button = event.target.closest('button');
    if (!button) return;
    const slide = button.closest('.editor-slide');
    if (button.classList.contains('slide-subir') && slide.previousElementSibling) slide.parentNode.insertBefore(slide, slide.previousElementSibling);
    if (button.classList.contains('slide-bajar') && slide.nextElementSibling) slide.parentNode.insertBefore(slide.nextElementSibling, slide);
    if (button.classList.contains('slide-quitar')) {
        Swal.fire({icon: 'warning', title: '¿Quitar esta imagen?', text: 'Se quitará de la portada cuando guardes los cambios.', showCancelButton: true, confirmButtonText: 'Sí, quitar', cancelButtonText: 'Cancelar', confirmButtonColor: '#536fe8'}).then(result => { if (result.isConfirmed) slide.remove(); });
    }
});
$(function () {
    $('#featured_ids').select2({width: '100%', language: 'es', maximumSelectionLength: 6});
});
</script>
@endpush
