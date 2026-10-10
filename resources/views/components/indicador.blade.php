{{--
    Indicador numérico del dashboard, con el lenguaje de las tarjetas del Figma:
    centrado, número grande en Platypi y etiqueta mono debajo.
    Con :alerta="true" muestra el punto naranja. Si el valor es null se muestra "—" (sin datos).

    <x-indicador etiqueta="Productos en stock bajo" :valor="$stockBajo" :alerta="$stockBajo > 0" />
--}}
@props(['etiqueta', 'valor' => null, 'alerta' => false])

<x-tarjeta {{ $attributes->merge(['class' => 'flex flex-col items-center gap-4 px-6 py-7 text-center']) }}>
    <p class="font-display text-titulo font-light">{{ $valor ?? '—' }}</p>
    <p class="flex items-center gap-2 font-mono text-xs text-tenue">
        @if ($alerta)
            <span class="size-2 rounded-full bg-acento" aria-hidden="true"></span>
        @endif
        {{ $etiqueta }}
    </p>
</x-tarjeta>
