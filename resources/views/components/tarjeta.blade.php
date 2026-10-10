{{--
    Tarjeta blanca (como las de "Collections" en el Figma).
    Con href se vuelve un enlace completo con estado hover/press.

    <x-tarjeta class="p-6">...</x-tarjeta>
    <x-tarjeta href="/productos">...</x-tarjeta>
--}}
@props(['href' => null])

@php
    // Plana, sin sombra, como en el Figma
    $clases = 'block rounded-tarjeta bg-superficie';
@endphp

@if ($href)
    {{-- Al presionar se encoge a 0.97 (whileTap del Figma); al pasar el mouse aparece un borde sutil --}}
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $clases.' border border-transparent transition hover:border-borde-fuerte active:scale-[0.97]']) }}>
        {{ $slot }}
    </a>
@else
    <div {{ $attributes->merge(['class' => $clases]) }}>
        {{ $slot }}
    </div>
@endif
