{{--
    Hoja para formularios de crear/editar (pantalla "New collection" del Figma Make).
    - Móvil: sube desde abajo y ocupa casi toda la pantalla.
    - Escritorio (sm+): modal centrado con esquinas de 30 px y sombra suave.
    Se cierra con la flecha/X, tocando el velo de fondo o con la tecla Esc (public/js/app.js).

    Se usa dentro de @section('hoja') en una vista que extiende el listado, así el listado
    queda visible detrás (por ejemplo categorias/create.blade.php).

    <x-hoja titulo="Nueva categoría" :cerrar="route('categorias.index')"> ...formulario... </x-hoja>
--}}
@props(['titulo', 'cerrar'])

{{-- Velo oscuro: tocarlo cierra la hoja --}}
<a href="{{ $cerrar }}" class="vt-velo fixed inset-0 z-30 bg-tinta/30" tabindex="-1" aria-hidden="true"></a>

<section role="dialog" aria-modal="true" aria-labelledby="hoja-titulo" data-hoja data-cerrar="{{ $cerrar }}"
    class="vt-hoja fixed inset-x-0 top-2 bottom-0 z-30 flex flex-col overflow-y-auto rounded-t-hoja bg-fondo
        sm:inset-auto sm:top-1/2 sm:left-1/2 sm:max-h-[calc(100dvh-4rem)] sm:w-[calc(100%-2.5rem)] sm:max-w-lg sm:-translate-x-1/2 sm:-translate-y-1/2 sm:rounded-[30px] sm:shadow-[0_18px_45px_rgb(0_0_0/0.1)]">

    {{-- Barrita de arrastre (solo móvil, decorativa) --}}
    <div class="flex shrink-0 justify-center pt-2.5 sm:hidden" aria-hidden="true">
        <div class="h-1 w-10 rounded-full bg-tinta/15"></div>
    </div>

    {{-- Barra superior: volver en móvil, X en escritorio --}}
    <div class="flex shrink-0 items-center justify-between px-5 pt-4 sm:pt-5">
        <a href="{{ $cerrar }}" aria-label="Cerrar" title="Cerrar"
            class="-ml-2 flex size-10 items-center justify-center rounded-full transition hover:bg-superficie sm:hidden">
            <x-icono nombre="flecha-izquierda" />
        </a>
        <span class="hidden sm:block" aria-hidden="true"></span>
        <a href="{{ $cerrar }}" aria-label="Cerrar" title="Cerrar"
            class="-mr-2 hidden size-10 items-center justify-center rounded-full transition hover:bg-superficie sm:flex">
            <x-icono nombre="x" />
        </a>
    </div>

    {{-- Contenido; abajo deja espacio para la barra flotante en móvil --}}
    <div class="flex flex-1 flex-col px-5 pb-32 sm:pb-10">
        <h2 id="hoja-titulo" class="pt-10 text-center font-mono text-xs text-tenue">{{ $titulo }}</h2>
        {{ $slot }}
    </div>
</section>
