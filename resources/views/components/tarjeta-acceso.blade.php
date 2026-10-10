{{--
    Tarjeta de acceso a un módulo desde Home (inspirada en CollectionCard del Figma).

    <x-tarjeta-acceso href="/productos" titulo="Productos" descripcion="Stock y unidades" icono="caja" />
--}}
@props(['href', 'titulo', 'descripcion', 'icono'])

<x-tarjeta :href="$href" class="group flex flex-col items-center gap-4 px-6 pt-7 pb-6 text-center">
    <span class="flex size-11 items-center justify-center rounded-full bg-fondo text-tinta transition group-hover:bg-tinta group-hover:text-superficie">
        <x-icono :nombre="$icono" />
    </span>
    <span class="font-display text-xl font-light tracking-titulo">{{ $titulo }}</span>
    <span class="font-mono text-xs text-tenue">{{ $descripcion }}</span>
</x-tarjeta>
