{{--
    Botón (píldora, como en el Figma). Si recibe href se dibuja como enlace.
    Variantes: primario (negro), secundario (blanco con borde), fantasma (solo texto), peligro.
    'cargando' es el texto que se muestra mientras se envía el formulario.

    <x-boton>Guardar</x-boton>
    <x-boton variante="secundario" href="/categorias">Cancelar</x-boton>
    <x-boton cargando="Guardando…">Guardar</x-boton>
--}}
@props([
    'variante' => 'primario',
    'href' => null,
    'tipo' => 'submit',
    'tamano' => 'normal',
    'cargando' => null,
])

@php
    $base = 'inline-flex items-center justify-center gap-2 rounded-full font-display tracking-titulo whitespace-nowrap transition active:scale-[0.97] disabled:pointer-events-none disabled:bg-deshabilitado disabled:text-tinta/60 aria-busy:cursor-progress';

    $tamanos = [
        'normal' => 'px-6 py-[18px] text-base leading-[1.2]',
        'chico' => 'min-h-10 px-4 py-2 text-sm',
        // Botón circular de 41 px solo con ícono (el "+" de Collections en el Figma); requiere aria-label
        'icono' => 'size-[41px] shrink-0',
    ];

    $variantes = [
        'primario' => 'bg-tinta text-superficie hover:bg-tinta-suave',
        'secundario' => 'border border-borde-fuerte bg-superficie text-tinta hover:border-tinta',
        'fantasma' => 'text-tinta underline-offset-4 hover:underline',
        'peligro' => 'bg-peligro text-superficie hover:brightness-95',
    ];

    $clases = $base.' '.$tamanos[$tamano].' '.$variantes[$variante];
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $clases]) }}>{{ $slot }}</a>
@else
    <button type="{{ $tipo }}" @if ($cargando) data-texto-cargando="{{ $cargando }}" @endif {{ $attributes->merge(['class' => $clases]) }}>{{ $slot }}</button>
@endif
