{{--
    Encabezado de página centrado (pantalla "Collections" del Figma):
    título grande en Platypi 50 px, una línea mono debajo y, opcionalmente, acciones.

    <x-encabezado titulo="Categorías" subtitulo="6 categorías">
        <x-boton href="/categorias/create">Nueva</x-boton>
    </x-encabezado>
--}}
@props(['titulo', 'subtitulo' => null])

<header {{ $attributes->merge(['class' => 'flex flex-col items-center gap-[21px] py-10 text-center']) }}>
    <div class="flex flex-col items-center gap-[22px]">
        <h1 class="font-display text-titulo font-light text-balance">{{ $titulo }}</h1>
        @if ($subtitulo)
            <p class="font-mono text-xs text-tenue">{{ $subtitulo }}</p>
        @endif
    </div>

    @if (! $slot->isEmpty())
        <div class="flex flex-wrap items-center justify-center gap-3">{{ $slot }}</div>
    @endif
</header>
