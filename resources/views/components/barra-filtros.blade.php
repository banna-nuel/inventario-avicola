{{--
    Fila de filtros con subrayado en el activo (FilterBar del Figma).
    Son enlaces GET: conservan el resto de la búsqueda y vuelven a la página 1.

    <x-barra-filtros :opciones="['' => 'Todo', 'productos' => 'Productos']" :actual="request('tipo', '')" />
--}}
@props(['opciones', 'actual' => '', 'parametro' => 'tipo'])

<nav {{ $attributes->merge(['class' => 'flex flex-wrap items-center gap-x-4 gap-y-2 sm:gap-x-6']) }} aria-label="Filtros">
    @foreach ($opciones as $valor => $etiqueta)
        @php
            $activo = (string) $valor === (string) $actual;
            $url = request()->fullUrlWithQuery([$parametro => $valor === '' ? null : $valor, 'page' => null]);
        @endphp
        <a
            href="{{ $url }}"
            @if ($activo) aria-current="true" @endif
            class="relative pb-1.5 font-mono text-xs tracking-tight transition {{ $activo ? 'text-tinta' : 'text-tenue hover:text-tinta' }}"
        >
            {{ $etiqueta }}
            @if ($activo)
                <span class="absolute inset-x-0 bottom-0 h-[1.5px] rounded-full bg-tinta" aria-hidden="true"></span>
            @endif
        </a>
    @endforeach
</nav>
