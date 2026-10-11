@extends('layouts.app')
@section('title', 'Categorías')
@section('volver', route('home'))
@section('content')
    {{-- Encabezado como "Collections" del Figma: título, conteo y botón circular + --}}
    <x-encabezado titulo="Categorías"
        :subtitulo="$categorias->count() . ' ' . ($categorias->count() === 1 ? 'categoría' : 'categorías')">
        <x-boton :href="route('categorias.create')" tamano="icono" aria-label="Nueva categoría" title="Nueva categoría">
            <x-icono nombre="mas" class="size-5" />
        </x-boton>
    </x-encabezado>

    @if ($categorias->isEmpty())
        <x-estado-vacio titulo="Aún no hay categorías"
            mensaje="Crea la primera para organizar los productos, por ejemplo: Alimento, Medicamentos o Vacunas.">
            <x-boton :href="route('categorias.create')">Nueva categoría</x-boton>
        </x-estado-vacio>
    @else
        <x-tabla>
            <x-slot:encabezado>
                <tr>
                    <th>Nombre</th>
                    {{-- En móvil esta columna se oculta y el conteo va debajo del nombre --}}
                    <th class="hidden text-right sm:table-cell">Productos</th>
                    <th><span class="sr-only">Acciones</span></th>
                </tr>
            </x-slot:encabezado>

            @foreach ($categorias as $categoria)
                @php
                    $textoProductos = $categoria->productos_count . ' ' . ($categoria->productos_count === 1 ? 'producto' : 'productos');
                @endphp
                <tr>
                    <td class="w-full">
                        <span class="block font-display text-base [overflow-wrap:anywhere]">{{ $categoria->nombre }}</span>
                        <span class="font-mono text-micro font-medium text-tenue sm:hidden">{{ $textoProductos }}</span>
                    </td>
                    <td class="hidden text-right font-mono text-xs sm:table-cell">{{ $categoria->productos_count }}</td>
                    {{-- px-2! (con "!" = important) gana al padding general de x-tabla --}}
                    <td class="px-2!">
                        <div class="flex justify-end">
                            <a href="{{ route('categorias.edit', $categoria) }}"
                                aria-label="Editar {{ $categoria->nombre }}" title="Editar"
                                class="flex size-10 items-center justify-center rounded-full transition hover:bg-fondo">
                                <x-icono nombre="lapiz" class="size-[18px]" />
                            </a>

                            {{-- Eliminar pide confirmación (data-confirmar, ver public/js/app.js) --}}
                            <form action="{{ route('categorias.destroy', $categoria) }}" method="POST"
                                data-confirmar="¿Eliminar la categoría «{{ $categoria->nombre }}»?">
                                @csrf
                                @method('DELETE')
                                <button type="submit" aria-label="Eliminar {{ $categoria->nombre }}" title="Eliminar"
                                    class="flex size-10 items-center justify-center rounded-full text-peligro transition hover:bg-fondo">
                                    <x-icono nombre="papelera" class="size-[18px]" />
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
            @endforeach
        </x-tabla>
    @endif
@endsection
