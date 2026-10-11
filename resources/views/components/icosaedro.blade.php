{{--
    Icosaedro decorativo (RNF-08). La clase App\View\Components\Icosaedro calcula $lineas.

    <x-icosaedro tamano="compacto" />   Inicio (≈ 176-200 px)
    <x-icosaedro tamano="grande" />     login (≈ 216-300 px)

    - Decorativo: aria-hidden y sin foco. El tamaño queda reservado (no mueve el layout al cargar).
    - Muestra un SVG estático; public/js/icosaedro.js lo cambia por el 3D (Three.js autoalojado)
      después de cargar la página. Sin WebGL o con "reducir movimiento", se queda el SVG.
--}}
@php
    $tamanos = [
        'compacto' => 'size-[176px] sm:size-[200px]',
        'grande' => 'size-[216px] sm:size-[300px]',
    ];
    // "?v=": tras cada cambio el navegador baja el archivo nuevo (como app.js y app.css)
    $version = filemtime(public_path('js/icosaedro.js'));
@endphp

<div {{ $attributes->merge(['class' => 'mx-auto shrink-0 text-tinta-suave '.$tamanos[$tamano]]) }} data-icosaedro aria-hidden="true">
    <svg viewBox="-1 -1 2 2" class="size-full" focusable="false">
        @foreach ($lineas as [$x1, $y1, $x2, $y2, $opacidad])
            <line x1="{{ $x1 }}" y1="{{ $y1 }}" x2="{{ $x2 }}" y2="{{ $y2 }}" stroke="currentColor" stroke-opacity="{{ $opacidad }}" />
        @endforeach
    </svg>
</div>

{{-- Módulo del mismo origen (sin scripts inline: compatible con una CSP estricta). @once: una sola vez por página --}}
@once
    <script type="module" src="{{ asset('js/icosaedro.js') }}?v={{ $version }}"></script>
@endonce
