{{--
    Etiqueta corta de estado.
    alerta = stock bajo (único uso del naranja como fondo, con texto negro: 10:1 de contraste).

    <x-badge variante="alerta">Stock bajo</x-badge>
    <x-badge variante="inactivo">Inactivo</x-badge>
--}}
@props(['variante' => 'neutro'])

@php
    $variantes = [
        'neutro' => 'border border-borde-fuerte bg-superficie text-tinta',
        'alerta' => 'bg-acento text-tinta',
        'peligro' => 'bg-peligro text-superficie',
        'inactivo' => 'bg-deshabilitado text-tinta',
    ];
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 font-mono text-xs whitespace-nowrap '.$variantes[$variante]]) }}>
    @if ($variante === 'alerta')
        <x-icono nombre="alerta" class="size-3.5" />
    @endif
    {{ $slot }}
</span>
