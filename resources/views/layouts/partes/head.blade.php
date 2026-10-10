{{-- <head> común a los dos layouts (app e invitado) --}}
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
<meta name="theme-color" content="#f4f4f4">
<title>@yield('title', 'Inicio') · Inventario Avícola</title>

{{-- Precarga de las dos fuentes que se ven primero, para evitar el salto de texto --}}
<link rel="preload" href="{{ asset('fonts/platypi-variable.woff2') }}" as="font" type="font/woff2" crossorigin>
<link rel="preload" href="{{ asset('fonts/ibm-plex-mono-400.woff2') }}" as="font" type="font/woff2" crossorigin>

<link rel="stylesheet" href="{{ asset('css/app.css') }}">
<script src="{{ asset('js/app.js') }}" defer></script>
