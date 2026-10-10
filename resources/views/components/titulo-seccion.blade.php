{{--
    Título de sección con contador, como "Representations [87]" y "My Trove [87]" del Figma.
    'nivel' es la etiqueta HTML (h1, h2...) y 'centrado' lo alinea al centro.

    <x-titulo-seccion titulo="Resultados" :contador="$total" />
    <x-titulo-seccion nivel="h1" titulo="Hola, Laura" :contador="2" centrado />
--}}
@props(['titulo', 'contador' => null, 'nivel' => 'h2', 'centrado' => false])

<div {{ $attributes->merge(['class' => 'flex items-baseline gap-2 '.($centrado ? 'justify-center' : '')]) }}>
    <{{ $nivel }} class="font-display text-seccion font-normal">{{ $titulo }}</{{ $nivel }}>
    @if (! is_null($contador))
        <span class="font-mono text-xs text-tenue">[{{ $contador }}]</span>
    @endif
</div>
