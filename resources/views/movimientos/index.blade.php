@extends('layouts.app')
@section('title', 'Movimientos')
@section('volver', route('home'))
@use('App\Enums\UnidadMedida')
@section('content')
    @php
        $total = $movimientos->total();
        $hayFiltros = $filtros !== [];
        $productoFiltrado = isset($filtros['producto']) ? ($productosFiltro[$filtros['producto']] ?? null) : null;
    @endphp

    {{-- El + abre la hoja con el producto del filtro ya elegido (si hay uno) --}}
    <x-encabezado titulo="Movimientos" :subtitulo="$total . ' ' . ($total === 1 ? 'movimiento' : 'movimientos')">
        <x-boton :href="route('movimientos.create', $filtros)" tamano="icono" aria-label="Nuevo movimiento" title="Nuevo movimiento" data-abre-hoja>
            <x-icono nombre="mas" class="size-5" />
        </x-boton>
    </x-encabezado>

    {{--
        Filtros (GET, validados por FiltrosHistorialRequest). <details> es un panel nativo que se
        abre y se cierra sin JavaScript; queda abierto si hay filtros activos o algún error.
    --}}
    {{-- Panel con borde y sin fondo: los campos (blancos) se distinguen sobre el gris de la página --}}
    <details class="group/filtros mb-6 rounded-tarjeta border border-borde-fuerte" @if ($hayFiltros || $errors->any()) open @endif>
        <summary class="flex cursor-pointer list-none items-center justify-between gap-3 rounded-tarjeta px-4 py-3.5 font-mono text-xs [&::-webkit-details-marker]:hidden">
            <span>
                Filtrar historial
                @if ($hayFiltros)
                    <span class="text-tenue">[{{ count($filtros) }}]</span>
                @endif
            </span>
            <x-icono nombre="chevron-abajo" class="size-4 text-tenue transition group-open/filtros:rotate-180" />
        </summary>

        <form action="{{ route('movimientos.index') }}" method="GET" novalidate
            class="grid items-start gap-4 border-t border-borde p-4 sm:grid-cols-2 lg:grid-cols-4">
            <x-select name="producto" etiqueta="Producto" vacio="Todos los productos" :opciones="$productosFiltro"
                :valor="$filtros['producto'] ?? ''" />
            <x-select name="tipo" etiqueta="Tipo" vacio="Entradas y salidas"
                :opciones="['entrada' => 'Solo entradas', 'salida' => 'Solo salidas']" :valor="$filtros['tipo'] ?? ''" />
            <x-campo name="desde" etiqueta="Desde" tipo="date" :valor="$filtros['desde'] ?? ''" />
            <x-campo name="hasta" etiqueta="Hasta (incluido)" tipo="date" :valor="$filtros['hasta'] ?? ''" />

            <div class="flex flex-wrap items-center gap-5 sm:col-span-2 lg:col-span-4">
                <x-boton tamano="chico" cargando="Filtrando…">Filtrar</x-boton>
                @if ($hayFiltros)
                    <a href="{{ route('movimientos.index') }}" class="font-mono text-xs underline underline-offset-4 hover:text-tenue">Limpiar filtros</a>
                @endif
            </div>
        </form>
    </details>

    @if ($movimientos->isEmpty())
        @if ($hayFiltros)
            <x-estado-vacio titulo="Sin movimientos con estos filtros" mensaje="Prueba con otro producto, tipo o rango de fechas.">
                <x-boton :href="route('movimientos.index')" variante="secundario">Limpiar filtros</x-boton>
            </x-estado-vacio>
        @else
            <x-estado-vacio titulo="Aún no hay movimientos"
                mensaje="Registra una entrada o una salida; aquí verás el historial, del más reciente al más antiguo.">
                <x-boton :href="route('movimientos.create')" data-abre-hoja>Nuevo movimiento</x-boton>
            </x-estado-vacio>
        @endif
    @else
        <x-titulo-seccion :titulo="$productoFiltrado ? 'Historial de ' . $productoFiltrado : 'Historial'" :contador="$total" class="pb-3.5" />

        <x-tabla>
            <x-slot:encabezado>
                <tr>
                    <th>Producto</th>
                    {{-- En móvil, tipo, fecha y usuario van bajo el nombre --}}
                    <th class="hidden sm:table-cell">Tipo</th>
                    <th class="hidden md:table-cell">Fecha</th>
                    <th class="hidden lg:table-cell">Registró</th>
                    <th class="text-right">Cantidad</th>
                </tr>
            </x-slot:encabezado>

            @foreach ($movimientos as $movimiento)
                @php
                    $entrada = $movimiento->tipo === 'entrada';
                    $unidad = $movimiento->producto->unidad_medida;
                    $fecha = $movimiento->fecha->translatedFormat('j M Y · H:i');
                @endphp
                <tr>
                    <td class="w-full">
                        {{-- El nombre filtra el historial por ese producto --}}
                        <a href="{{ route('movimientos.index', ['producto' => $movimiento->producto_id]) }}"
                            class="block font-display text-base underline-offset-4 [overflow-wrap:anywhere] hover:underline">{{ $movimiento->producto->nombre }}</a>
                        <span class="block font-mono text-micro font-medium text-tenue sm:hidden">
                            {{ $entrada ? 'Entrada' : 'Salida' }} · {{ $fecha }} · {{ $movimiento->usuario->name }}
                        </span>
                        <span class="hidden font-mono text-micro font-medium text-tenue sm:block md:hidden">{{ $fecha }}</span>
                    </td>
                    <td class="hidden sm:table-cell">
                        <x-badge :variante="$entrada ? 'neutro' : 'oscuro'">{{ $entrada ? 'Entrada' : 'Salida' }}</x-badge>
                    </td>
                    <td class="hidden font-mono text-xs whitespace-nowrap md:table-cell">{{ $fecha }}</td>
                    <td class="hidden font-mono text-xs whitespace-nowrap lg:table-cell">{{ $movimiento->usuario->name }}</td>
                    <td class="text-right whitespace-nowrap">
                        {{-- "+" suma al stock (entrada), "−" resta (salida) --}}
                        <span class="block font-display text-base">
                            <span aria-hidden="true">{{ $entrada ? '+' : '−' }}</span>{{ UnidadMedida::numero($movimiento->cantidad) }}
                        </span>
                        <span class="block font-mono text-micro font-medium text-tenue">{{ $unidad->nombrePara($movimiento->cantidad) }}</span>
                    </td>
                </tr>
            @endforeach
        </x-tabla>

        <div class="pt-6">
            {{ $movimientos->links() }}
        </div>
    @endif
@endsection
