{{--
    Campo de búsqueda con lupa (barra de búsqueda del Figma). Va dentro de un <form method="GET">;
    Enter envía la búsqueda.

    <x-buscador :valor="$q" etiqueta="Buscar productos por nombre" placeholder="Buscar producto" />
--}}
@props(['etiqueta', 'valor' => '', 'placeholder' => 'Buscar', 'name' => 'q'])

<div {{ $attributes->merge(['class' => 'flex items-center gap-2.5 rounded-campo border border-transparent bg-superficie px-4 py-3.5 transition focus-within:border-tinta focus-within:shadow-[0_0_0_4px_rgb(245_166_35/0.45)] hover:border-borde-fuerte']) }}>
    <label for="{{ $name }}" class="sr-only">{{ $etiqueta }}</label>
    <x-icono nombre="lupa" class="size-[18px] text-tenue" />
    {{-- 16 px en móvil para que el teclado no haga zoom en iPhone; 14 px (Figma) desde sm --}}
    <input id="{{ $name }}" name="{{ $name }}" type="search" value="{{ $valor }}" enterkeyhint="search" autocomplete="off"
        placeholder="{{ $placeholder }}"
        class="min-w-0 flex-1 bg-transparent font-mono text-base outline-none focus-visible:shadow-none focus-visible:outline-none sm:text-sm">
</div>
