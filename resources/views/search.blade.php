@extends('layouts.app')
@section('title', 'Buscar')
@use('App\Enums\UnidadMedida')
@section('content')
    @php
        $esAdmin = auth()->user()->esAdmin();
        $etiquetas = ['productos' => 'Productos', 'categorias' => 'Categorías', 'usuarios' => 'Usuarios'];
        // Filtros: "Todo" más los grupos que este rol puede buscar (BusquedaController)
        $filtros = ['' => 'Todo'] + array_intersect_key($etiquetas, array_flip($permitidos));
    @endphp

    {{-- Como en el Figma, Search no muestra título: la barra va directo bajo el encabezado --}}
    <h1 class="sr-only">Buscar</h1>

    <form action="{{ route('search') }}" method="GET" role="search" class="pt-2.5">
        {{-- Conserva el filtro elegido al hacer una nueva búsqueda --}}
        @if ($tipo !== '')
            <input type="hidden" name="tipo" value="{{ $tipo }}">
        @endif

        <x-buscador :valor="$q" class="mb-5"
            :etiqueta="$esAdmin ? 'Buscar productos, categorías o usuarios' : 'Buscar productos por nombre o categoría'" />

        <x-barra-filtros :opciones="$filtros" :actual="$tipo" class="pb-6" />
    </form>

    <section aria-label="Resultados" class="flex flex-col gap-8">
        <x-titulo-seccion titulo="Resultados" :contador="$total" />

        @if ($q === '')
            <x-estado-vacio titulo="Escribe para buscar"
                :mensaje="$esAdmin
                    ? 'Busca productos (por nombre o categoría), categorías o usuarios. Presiona Enter para ver los resultados.'
                    : 'Busca por nombre de producto o de categoría. Presiona Enter para ver los resultados.'" />
        @elseif ($muyCorto)
            <x-estado-vacio titulo="Escribe al menos 2 caracteres" mensaje="Con una sola letra habría demasiados resultados." />
        @elseif ($total === 0)
            <x-estado-vacio titulo="Sin resultados" :mensaje="'No encontramos coincidencias para «' . $q . '».'" />
        @else
            @foreach ($grupos as $grupo => $resultado)
                @continue($resultado['total'] === 0)

                {{-- Máximo 10 por grupo; "Ver todos" abre el listado filtrado con la misma búsqueda --}}
                <div class="flex flex-col gap-3">
                    <div class="flex items-baseline justify-between gap-4">
                        <x-titulo-seccion nivel="h3" :titulo="$etiquetas[$grupo]" :contador="$resultado['total']" />
                        <a href="{{ $resultado['verTodos'] }}" aria-label="Ver todos los resultados de {{ $etiquetas[$grupo] }}"
                            class="font-mono text-xs whitespace-nowrap underline underline-offset-4 hover:text-tenue">Ver todos</a>
                    </div>

                    <ul class="divide-y divide-borde overflow-hidden rounded-tarjeta bg-superficie">
                        @foreach ($resultado['items'] as $item)
                            <li>
                                @if ($grupo === 'productos')
                                    {{-- Producto: su stock a la vista; el enlace abre su historial --}}
                                    <a href="{{ route('movimientos.index', ['producto' => $item->id]) }}"
                                        class="flex items-center justify-between gap-4 px-4 py-3.5 transition hover:bg-fondo/60">
                                        <span class="min-w-0">
                                            <span class="block font-display text-base [overflow-wrap:anywhere]">{{ $item->nombre }}</span>
                                            <span class="flex flex-wrap items-center gap-1.5 pt-0.5 font-mono text-micro font-medium text-tenue">
                                                {{ $item->categoria->nombre }}
                                                @if (! $item->activo)
                                                    <x-badge variante="inactivo">Inactivo</x-badge>
                                                @elseif ($item->enStockBajo())
                                                    <x-badge variante="alerta">Stock bajo</x-badge>
                                                @endif
                                            </span>
                                        </span>
                                        <span class="shrink-0 text-right">
                                            <span class="block font-display text-base">{{ UnidadMedida::numero($item->stock_actual) }}</span>
                                            <span class="block font-mono text-micro font-medium text-tenue">{{ $item->unidad_medida->nombrePara($item->stock_actual) }}</span>
                                        </span>
                                    </a>
                                @elseif ($grupo === 'categorias')
                                    <a href="{{ route('categorias.edit', $item) }}"
                                        class="flex items-center justify-between gap-4 px-4 py-3.5 transition hover:bg-fondo/60">
                                        <span class="font-display text-base [overflow-wrap:anywhere]">{{ $item->nombre }}</span>
                                        <span class="shrink-0 font-mono text-xs text-tenue">
                                            {{ $item->productos_count }} {{ $item->productos_count === 1 ? 'producto' : 'productos' }}
                                        </span>
                                    </a>
                                @else
                                    <a href="{{ route('usuarios.edit', $item) }}"
                                        class="flex items-center justify-between gap-4 px-4 py-3.5 transition hover:bg-fondo/60">
                                        <span class="min-w-0">
                                            <span class="block font-display text-base [overflow-wrap:anywhere]">{{ $item->name }}</span>
                                            <span class="block font-mono text-micro font-medium text-tenue [overflow-wrap:anywhere]">{{ $item->email }}</span>
                                        </span>
                                        <span class="flex shrink-0 flex-wrap justify-end gap-1.5">
                                            <x-badge :variante="$item->esAdmin() ? 'oscuro' : 'neutro'">{{ $item->esAdmin() ? 'Admin' : 'Encargado' }}</x-badge>
                                            @if (! $item->activo)
                                                <x-badge variante="inactivo">Inactivo</x-badge>
                                            @endif
                                        </span>
                                    </a>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endforeach
        @endif
    </section>
@endsection
