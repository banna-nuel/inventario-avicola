{{--
    Tarjeta blanca (como las de "Collections" en el Figma).
    Con href se vuelve un enlace completo con estado hover/press.

    <x-tarjeta class="p-6">...</x-tarjeta>
    <x-tarjeta href="/productos">...</x-tarjeta>
--}}
@props(['href' => null])

@php
    $clases = 'block rounded-tarjeta bg-superficie shadow-tarjeta';
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $clases.' transition hover:-translate-y-0.5 hover:shadow-md active:scale-[0.98]']) }}>
        {{ $slot }}
    </a>
@else
    <div {{ $attributes->merge(['class' => $clases]) }}>
        {{ $slot }}
    </div>
@endif
