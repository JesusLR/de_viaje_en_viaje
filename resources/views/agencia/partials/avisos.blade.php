@push('scripts')
@if(session('success') || $errors->any())
    @php
        $avisoIcono = $errors->any() ? 'error' : 'success';
        $avisoTitulo = $errors->any() ? 'Revisa los datos' : 'Operación completada';
        $avisoTexto = $errors->any() ? implode("\n", $errors->all()) : session('success');
    @endphp
    <script>
    Swal.fire({
        icon: @json($avisoIcono),
        title: @json($avisoTitulo),
        text: @json($avisoTexto),
        confirmButtonText: 'Entendido', confirmButtonColor: '#536fe8'
    });
    </script>
@endif
@endpush
