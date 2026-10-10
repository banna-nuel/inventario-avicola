{{--
    Contenido común de las páginas de error (403, 404, 419).

    <x-pagina-error codigo="403" titulo="No tienes permiso" mensaje="..." />
--}}
@props(['codigo', 'titulo', 'mensaje', 'enlace' => '/home', 'textoEnlace' => 'Volver al inicio'])

<div class="flex w-full max-w-md flex-col items-center gap-[22px] text-center">
    <p class="font-mono text-xs text-tenue">Error {{ $codigo }}</p>
    <h1 class="font-display text-titulo font-light text-balance">{{ $titulo }}</h1>
    <p class="font-mono text-xs leading-relaxed text-balance text-tenue">{{ $mensaje }}</p>
    <x-boton :href="$enlace" class="mt-4">{{ $textoEnlace }}</x-boton>
</div>
