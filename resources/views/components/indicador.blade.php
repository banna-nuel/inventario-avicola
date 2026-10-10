{{--
    Indicador numérico del dashboard. Con :alerta="true" muestra el punto naranja.
    Si el valor es null se muestra "—" (todavía sin datos).

    <x-indicador etiqueta="Productos en stock bajo" :valor="$stockBajo" :alerta="$stockBajo > 0" />
--}}
@props(['etiqueta', 'valor' => null, 'alerta' => false])

<x-tarjeta {{ $attributes->merge(['class' => 'flex flex-col gap-6 p-5 sm:p-6']) }}>
    <p class="flex items-center gap-2 font-mono text-xs text-tenue">
        @if ($alerta)
            <span class="size-2 rounded-full bg-acento" aria-hidden="true"></span>
        @endif
        {{ $etiqueta }}
    </p>
    <p class="font-display text-5xl leading-none font-light tracking-titulo">{{ $valor ?? '—' }}</p>
</x-tarjeta>
