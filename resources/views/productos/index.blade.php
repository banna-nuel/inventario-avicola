@extends('layouts.app')
@section('title', 'Productos')
@section('volver', route('home'))
@use('App\Enums\UnidadMedida')
@section('content')
    @php
        $esAdmin = auth()->user()->esAdmin();
        $hayFiltros = $q !== '' || $categoria || $estado !== '';
        $total = $productos->total();
    @endphp

    {{-- Encabezado como "Collections" del Figma; el botón + solo para el administrador --}}
    <x-encabezado titulo="Productos" :subtitulo="$total . ' ' . ($total === 1 ? 'producto' : 'productos')">
        @if ($esAdmin)
            <x-boton :href="route('productos.create', $filtros)" tamano="icono" aria-label="Nuevo producto" title="Nuevo producto" data-abre-hoja>
                <x-icono nombre="mas" class="size-5" />
            </x-boton>
        @endif
    </x-encabezado>

    {{-- Búsqueda y filtros por GET: la URL guarda el filtro (se puede recargar o compartir) --}}
    <form action="{{ route('productos.index') }}" method="GET" role="search" class="flex flex-col gap-3 sm:flex-row">
        @if ($estado !== '')
            <input type="hidden" name="estado" value="{{ $estado }}">
        @endif

        <x-buscador :valor="$q" etiqueta="Buscar productos por nombre o categoría" placeholder="Buscar producto o categoría" class="flex-1" />

        {{-- Al cambiar la categoría se filtra solo (data-enviar-al-cambiar, public/js/app.js) --}}
        <div class="sm:w-60">
            <x-select name="categoria" etiqueta="Filtrar por categoría" etiqueta-oculta :opciones="$categorias->pluck('nombre', 'id')"
                vacio="Todas las categorías" :valor="$categoria ?: ''" data-enviar-al-cambiar class="py-3.5!" />
        </div>
    </form>

    <x-barra-filtros parametro="estado" :opciones="$estados" :actual="$estado" class="pt-5 pb-6" />

    @if ($productos->isEmpty())
        @if ($hayFiltros)
            <x-estado-vacio titulo="Sin resultados" mensaje="Ningún producto coincide con la búsqueda o los filtros.">
                <x-boton :href="route('productos.index')" variante="secundario">Quitar filtros</x-boton>
            </x-estado-vacio>
        @elseif ($esAdmin && $categorias->isEmpty())
            <x-estado-vacio titulo="Aún no hay productos"
                mensaje="Primero crea una categoría (por ejemplo: Alimento o Vacunas); después podrás agregar productos.">
                <x-boton :href="route('categorias.create')">Nueva categoría</x-boton>
            </x-estado-vacio>
        @elseif ($esAdmin)
            <x-estado-vacio titulo="Aún no hay productos" mensaje="Crea el primero con su unidad de medida y su stock mínimo.">
                <x-boton :href="route('productos.create')" data-abre-hoja>Nuevo producto</x-boton>
            </x-estado-vacio>
        @else
            <x-estado-vacio titulo="Aún no hay productos" mensaje="Cuando el administrador los cree, aquí verás su stock." />
        @endif
    @else
        <x-tabla>
            <x-slot:encabezado>
                <tr>
                    <th>Producto</th>
                    {{-- En pantallas angostas, categoría y mínimo van bajo el nombre --}}
                    <th class="hidden md:table-cell">Categoría</th>
                    <th class="text-right">Stock</th>
                    <th class="hidden text-right sm:table-cell">Mínimo</th>
                    <th><span class="sr-only">Acciones</span></th>
                </tr>
            </x-slot:encabezado>

            @foreach ($productos as $producto)
                @php
                    // RN-02 (Producto::enStockBajo); a los inactivos no se les marca la alerta
                    $bajo = $producto->activo && $producto->enStockBajo();
                    $minimo = UnidadMedida::numero($producto->stock_minimo);
                @endphp
                <tr>
                    <td class="w-full">
                        {{-- El nombre lleva al historial de movimientos de ese producto --}}
                        <a href="{{ route('movimientos.index', ['producto' => $producto->id]) }}" title="Ver historial"
                            class="block font-display text-base underline-offset-4 [overflow-wrap:anywhere] hover:underline {{ $producto->activo ? '' : 'text-tenue' }}">{{ $producto->nombre }}</a>
                        <span class="block font-mono text-micro font-medium text-tenue md:hidden">
                            {{ $producto->categoria->nombre }}<span class="sm:hidden"> · mín. {{ $minimo }}</span>
                        </span>
                        @if ($bajo || ! $producto->activo)
                            <span class="mt-1.5 flex flex-wrap gap-1.5">
                                @if ($bajo)
                                    <x-badge variante="alerta">Stock bajo</x-badge>
                                @endif
                                @if (! $producto->activo)
                                    <x-badge variante="inactivo">Inactivo</x-badge>
                                @endif
                            </span>
                        @endif
                    </td>
                    <td class="hidden font-mono text-xs whitespace-nowrap md:table-cell">{{ $producto->categoria->nombre }}</td>
                    <td class="text-right whitespace-nowrap">
                        <span class="block font-display text-base">{{ UnidadMedida::numero($producto->stock_actual) }}</span>
                        <span class="block font-mono text-micro font-medium text-tenue">{{ $producto->unidad_medida->nombrePara($producto->stock_actual) }}</span>
                    </td>
                    <td class="hidden text-right font-mono text-xs whitespace-nowrap sm:table-cell">{{ $minimo }}</td>
                    <td class="px-2!">
                        <div class="flex justify-end">
                            {{-- Registrar entrada o salida de este producto (los dos roles; solo si está activo) --}}
                            @if ($producto->activo)
                                <a href="{{ route('movimientos.create', ['producto_id' => $producto->id, 'volver' => 'productos']) }}"
                                    aria-label="Registrar movimiento de {{ $producto->nombre }}" title="Registrar movimiento"
                                    class="flex size-10 items-center justify-center rounded-full transition hover:bg-fondo">
                                    <x-icono nombre="movimientos" class="size-[18px]" />
                                </a>
                            @endif
                            @if ($esAdmin)
                                <a href="{{ route('productos.edit', ['producto' => $producto] + $filtros) }}" data-abre-hoja
                                    aria-label="Editar {{ $producto->nombre }}" title="Editar"
                                    class="flex size-10 items-center justify-center rounded-full transition hover:bg-fondo">
                                    <x-icono nombre="lapiz" class="size-[18px]" />
                                </a>
                            @endif
                        </div>
                    </td>
                </tr>
            @endforeach
        </x-tabla>

        <div class="pt-6">
            {{ $productos->links() }}
        </div>
    @endif
@endsection
