@extends('layouts.app')
@section('title', 'Categorías')
@section('volver', route('home'))
@section('content')
    {{-- Encabezado como "Collections" del Figma: título, conteo y botón circular + --}}
    <x-encabezado titulo="Categorías"
        :subtitulo="$categorias->count() . ' ' . ($categorias->count() === 1 ? 'categoría' : 'categorías')">
        {{-- data-abre-hoja: al cerrar la hoja, el foco vuelve a este enlace (public/js/app.js) --}}
        <x-boton :href="route('categorias.create')" tamano="icono" aria-label="Nueva categoría" title="Nueva categoría" data-abre-hoja>
            <x-icono nombre="mas" class="size-5" />
        </x-boton>
    </x-encabezado>

    {{-- Filtro por nombre que llega desde "Ver todos" en Search ($q no existe en las hojas) --}}
    @if (($q ?? '') !== '')
        <p class="pb-4 text-center font-mono text-xs text-tenue">
            Categorías con «{{ $q }}» ·
            <a href="{{ route('categorias.index') }}" class="text-tinta underline underline-offset-4">Ver todas</a>
        </p>
    @endif

    @if ($categorias->isEmpty())
        <x-estado-vacio titulo="Aún no hay categorías"
            mensaje="Crea la primera para organizar los productos, por ejemplo: Alimento, Medicamentos o Vacunas.">
            <x-boton :href="route('categorias.create')" data-abre-hoja>Nueva categoría</x-boton>
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
                            <a href="{{ route('categorias.edit', $categoria) }}" data-abre-hoja
                                aria-label="Editar {{ $categoria->nombre }}" title="Editar"
                                class="flex size-10 items-center justify-center rounded-full transition hover:bg-fondo">
                                <x-icono nombre="lapiz" class="size-[18px]" />
                            </a>

                            @if ($categoria->productos_count > 0)
                                {{--
                                    RN-03: con productos (activos o inactivos) no se puede eliminar.
                                    aria-disabled (y no disabled) para que siga recibiendo foco y se pueda
                                    leer el motivo, que aparece al pasar el mouse o al enfocarlo.
                                    El servidor también lo valida (CategoriaController@destroy).
                                --}}
                                <span class="group relative flex">
                                    <button type="button" aria-disabled="true" aria-label="Eliminar {{ $categoria->nombre }}"
                                        aria-describedby="motivo-{{ $categoria->id }}"
                                        class="flex size-10 cursor-not-allowed items-center justify-center rounded-full text-tinta/30">
                                        <x-icono nombre="papelera" class="size-[18px]" />
                                    </button>
                                    <span id="motivo-{{ $categoria->id }}" role="tooltip"
                                        class="pointer-events-none invisible absolute top-1/2 right-full mr-1 -translate-y-1/2 rounded-full bg-tinta px-3 py-1.5 font-mono text-micro font-medium whitespace-nowrap text-superficie opacity-0 transition group-focus-within:visible group-focus-within:opacity-100 group-hover:visible group-hover:opacity-100">
                                        Tiene {{ $textoProductos }}
                                    </span>
                                </span>
                            @else
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
                            @endif
                        </div>
                    </td>
                </tr>
            @endforeach
        </x-tabla>
    @endif
@endsection
