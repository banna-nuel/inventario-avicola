{{--
    Fila de filtros con subrayado en el activo (FilterBar del Figma).
    Son enlaces GET: conservan el resto de la búsqueda y vuelven a la página 1.

    <x-barra-filtros :opciones="['' => 'Todo', 'productos' => 'Productos']" :actual="request('tipo', '')" />
--}}
@props(['opciones', 'actual' => '', 'parametro' => 'tipo'])

{{-- Repartidos a lo ancho (justify-between), como en el Figma --}}
<nav {{ $attributes->merge(['class' => 'flex flex-wrap items-center justify-between gap-x-4 gap-y-2']) }} aria-label="Filtros">
    @foreach ($opciones as $valor => $etiqueta)
        @php
            $activo = (string) $valor === (string) $actual;
            $url = request()->fullUrlWithQuery([$parametro => $valor === '' ? null : $valor, 'page' => null]);
        @endphp
        <a
            href="{{ $url }}"
            @if ($activo) aria-current="true" @endif
            class="relative pb-1.5 font-mono text-xs leading-[18px] font-medium tracking-filtro whitespace-nowrap transition {{ $activo ? 'text-tinta' : 'text-tenue hover:text-tinta' }}"
        >
            {{ $etiqueta }}
            @if ($activo)
                <span class="absolute inset-x-0 bottom-0 h-[1.5px] rounded-full bg-tinta" aria-hidden="true"></span>
            @endif
        </a>
    @endforeach
</nav>
