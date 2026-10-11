{{-- <head> común a los dos layouts (app e invitado) --}}
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
<meta name="theme-color" content="#f4f4f4">
<title>@yield('title', 'Inicio') · Inventario Avícola</title>

{{-- Precarga de las dos fuentes que se ven primero, para evitar el salto de texto --}}
<link rel="preload" href="{{ asset('fonts/platypi-variable.woff2') }}" as="font" type="font/woff2" crossorigin>
<link rel="preload" href="{{ asset('fonts/ibm-plex-mono-400.woff2') }}" as="font" type="font/woff2" crossorigin>

{{--
    Activa las transiciones entre páginas (View Transitions). Va aquí, inline, y no en app.css:
    el navegador decide si anima mirando esta regla en la página nueva, y a veces lo hacía
    antes de terminar de cargar app.css, así que la transición se cancelaba (~16 % de las veces).
    Con "reducir movimiento" activado en el sistema, no se anima.
--}}
{{-- "@@" es como se escribe una "@" literal en Blade (si no, la tomaría como directiva) --}}
<style>
    @@view-transition { navigation: auto; }
    @@media (prefers-reduced-motion: reduce) { @@view-transition { navigation: none; } }
</style>

@php
    // "?v=" con la fecha de modificación: tras cada cambio el navegador baja el archivo nuevo (no usa uno viejo en caché)
    $version = fn (string $archivo) => file_exists(public_path($archivo)) ? filemtime(public_path($archivo)) : 0;
@endphp
<link rel="stylesheet" href="{{ asset('css/app.css') }}?v={{ $version('css/app.css') }}">
<script src="{{ asset('js/app.js') }}?v={{ $version('js/app.js') }}" defer></script>
