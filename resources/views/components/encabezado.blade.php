{{--
    Encabezado de página: etiqueta en mono, título grande en Platypi y contador opcional "[12]".
    El slot (opcional) son las acciones de la página (por ejemplo, "Nueva categoría").

    <x-encabezado etiqueta="Inventario" titulo="Categorías" :contador="$categorias->count()">
        <x-boton href="/categorias/create">Nueva</x-boton>
    </x-encabezado>
--}}
@props(['titulo', 'etiqueta' => null, 'contador' => null])

<header {{ $attributes->merge(['class' => 'flex flex-col gap-6 py-8 sm:flex-row sm:items-end sm:justify-between sm:py-10']) }}>
    <div class="flex flex-col gap-4">
        @if ($etiqueta)
            <p class="font-mono text-xs text-tenue">{{ $etiqueta }}</p>
        @endif
        <div class="flex items-start gap-2">
            <h1 class="font-display text-4xl leading-tight font-light tracking-titulo text-balance sm:text-5xl">{{ $titulo }}</h1>
            @if (! is_null($contador))
                <span class="mt-2 font-mono text-xs">[{{ $contador }}]</span>
            @endif
        </div>
    </div>

    @if (! $slot->isEmpty())
        <div class="flex flex-wrap items-center gap-3">{{ $slot }}</div>
    @endif
</header>
