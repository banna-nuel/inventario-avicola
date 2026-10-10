{{--
    Mensaje flash / alerta. El layout la muestra sola con session('success') y session('error').
    Se puede cerrar con el botón × (ver public/js/app.js).

    <x-alerta tipo="exito">Categoría creada</x-alerta>
    <x-alerta tipo="error">No se pudo guardar</x-alerta>
--}}
@props(['tipo' => 'info'])

@php
    $estilos = [
        'exito' => ['bg-tinta text-superficie', 'check'],
        'error' => ['bg-superficie text-peligro border border-peligro', 'alerta'],
        'info' => ['bg-superficie text-tinta border border-borde-fuerte', 'alerta'],
    ];
    [$clases, $icono] = $estilos[$tipo];
@endphp

<div
    {{ $attributes->merge(['class' => 'flex items-start gap-3 rounded-campo px-4 py-3 text-sm '.$clases]) }}
    role="{{ $tipo === 'error' ? 'alert' : 'status' }}"
    data-alerta
>
    <x-icono :nombre="$icono" class="mt-0.5 size-4" />
    <div class="flex-1">{{ $slot }}</div>
    <button type="button" data-cerrar-alerta class="-m-1 rounded-full p-1 opacity-70 transition hover:opacity-100" aria-label="Cerrar mensaje">
        <x-icono nombre="x" class="size-4" />
    </button>
</div>
