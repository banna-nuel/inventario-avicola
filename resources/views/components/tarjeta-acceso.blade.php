{{--
    Tarjeta de acceso a un módulo desde Home. Copia la anatomía de CollectionCard del Figma:
    arriba el nombre (Platypi 20) y una línea mono de 10 px; abajo una franja de 107 px
    con una "miniatura" gris que muestra el ícono del módulo.

    <x-tarjeta-acceso href="/productos" titulo="Productos" descripcion="Stock y unidades" icono="caja" />
--}}
@props(['href', 'titulo', 'descripcion', 'icono'])

<x-tarjeta :href="$href" class="group overflow-hidden">
    <div class="flex flex-col items-center gap-4 px-6 pt-7 pb-2.5 text-center">
        <p class="font-display text-seccion leading-6 font-light">{{ $titulo }}</p>
        <p class="font-mono text-micro font-medium text-tenue">{{ $descripcion }}</p>
    </div>

    <div class="flex h-[107px] p-2">
        <div class="flex flex-1 items-center justify-center rounded-miniatura bg-miniatura transition group-hover:bg-tinta group-hover:text-superficie">
            <x-icono :nombre="$icono" class="size-7" />
        </div>
    </div>
</x-tarjeta>
