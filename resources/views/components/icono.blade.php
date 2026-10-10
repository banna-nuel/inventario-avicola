{{--
    Íconos SVG inline. Uso: <x-icono nombre="casa" class="size-4" />
    Toman el color del texto (currentColor). Son decorativos: si un botón solo
    tiene ícono, ponle aria-label al botón, no al ícono.
--}}
@props(['nombre'])

@php
    // Íconos rellenos copiados tal cual del diseño de la navegación del Figma
    $rellenos = [
        'casa' => ['16.7398 16.7398 16.5204 16.5204', 'M16.7398 33.2602V22.255L25 16.7398L33.2602 22.255V33.2602H27.2631V26.3913H22.737V33.2602H16.7398Z'],
        'panel' => ['16.7398 16.7398 16.5204 16.5204', 'M16.7399 33.2602V16.7398H23.4669V33.2602H16.7399ZM26.533 23.467V16.7398H33.2601V23.467H26.533ZM26.533 33.2602V26.5331H33.2601V33.2602H26.533Z'],
        'buscar' => ['0 0 16.5203 16.5203', 'M7.23149 0C11.2253 0 14.463 3.23765 14.463 7.23149C14.463 8.79313 13.9652 10.2371 13.1236 11.4192L16.5203 14.8161L14.8161 16.5203L11.4192 13.1236C10.2371 13.9652 8.79313 14.463 7.23149 14.463C3.23765 14.463 0 11.2253 0 7.23149C0 3.23765 3.23765 0 7.23149 0ZM7.23149 2.41049C4.56892 2.41049 2.41049 4.56892 2.41049 7.23149C2.41049 9.894 4.56892 12.0525 7.23149 12.0525C9.894 12.0525 12.0525 9.894 12.0525 7.23149C12.0525 4.56892 9.894 2.41049 7.23149 2.41049Z'],
    ];

    // Íconos de trazo (viewBox 24x24) con el mismo peso visual
    $trazos = [
        'flecha-izquierda' => 'M19 12H5M12 19l-7-7 7-7',
        'flecha-derecha' => 'M5 12h14M12 5l7 7-7 7',
        'mas' => 'M12 5v14M5 12h14',
        'x' => 'M18 6 6 18M6 6l12 12',
        'salir' => 'M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9',
        'caja' => 'M21 8l-9-5-9 5v8l9 5 9-5V8zM3 8l9 5 9-5M12 13v8',
        'movimientos' => 'M7 4v16M3 8l4-4 4 4M17 20V4M13 16l4 4 4-4',
        'etiqueta' => 'M3 12V3h9l9 9-9 9-9-9zM7.5 7.5h.01',
        'usuarios' => 'M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8zM2 21v-1a6 6 0 0 1 12 0v1M16 3.5a4 4 0 0 1 0 7M22 21v-1a6 6 0 0 0-4-5.6',
        'grafica' => 'M3 3v18h18M8 17v-5M13 17V8M18 17v-8',
        'alerta' => 'M12 3 2 20h20L12 3zM12 10v4M12 17h.01',
        'check' => 'M20 6 9 17l-5-5',
    ];
@endphp

@if (isset($rellenos[$nombre]))
    <svg {{ $attributes->merge(['class' => 'size-4 shrink-0']) }} viewBox="{{ $rellenos[$nombre][0] }}" fill="currentColor" aria-hidden="true" focusable="false">
        <path d="{{ $rellenos[$nombre][1] }}" />
    </svg>
@elseif (isset($trazos[$nombre]))
    <svg {{ $attributes->merge(['class' => 'size-5 shrink-0']) }} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
        <path d="{{ $trazos[$nombre] }}" />
    </svg>
@endif
