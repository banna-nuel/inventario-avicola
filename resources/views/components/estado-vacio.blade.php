{{--
    Estado vacío: borde punteado como las tarjetas de publicación del Figma.
    El contenido del slot (opcional) es la acción sugerida, por ejemplo un botón "Crear".

    <x-estado-vacio titulo="Sin categorías" mensaje="Crea la primera para organizar los productos.">
        <x-boton href="/categorias/create">Nueva categoría</x-boton>
    </x-estado-vacio>
--}}
@props(['titulo', 'mensaje' => null])

<div {{ $attributes->merge(['class' => 'flex flex-col items-center gap-3 rounded-tarjeta border border-dashed border-borde-fuerte px-6 py-12 text-center']) }}>
    <p class="font-display text-xl font-light tracking-titulo">{{ $titulo }}</p>
    @if ($mensaje)
        <p class="max-w-sm font-mono text-xs leading-relaxed text-tenue">{{ $mensaje }}</p>
    @endif
    @if (! $slot->isEmpty())
        <div class="mt-3">{{ $slot }}</div>
    @endif
</div>
