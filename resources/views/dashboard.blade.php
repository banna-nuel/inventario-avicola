@extends('layouts.app')
@section('title', 'Dashboard')
@use('App\Enums\UnidadMedida')
@section('content')
    {{-- Indicadores y alertas (DashboardController, RF-03): iguales para los dos roles --}}
    <x-encabezado titulo="Dashboard"
        :subtitulo="'Estado del inventario · ' . now()->translatedFormat('j \d\e F')" />

    <section aria-label="Indicadores" class="grid grid-cols-1 gap-4 pt-2 sm:grid-cols-3">
        <x-indicador etiqueta="Productos activos" :valor="$indicadores['activos']" />
        <x-indicador etiqueta="Activos en stock bajo" :valor="$indicadores['stockBajo']"
            :alerta="$indicadores['stockBajo'] > 0" />
        <x-indicador etiqueta="Movimientos de hoy" :valor="$indicadores['movimientosHoy']" />
    </section>

    <section aria-label="Productos en alerta" class="mt-10 flex flex-col gap-3.5">
        <x-titulo-seccion titulo="Productos en alerta" :contador="$alertas->count()" />

        @if ($alertas->isEmpty())
            <x-estado-vacio titulo="Sin alertas por ahora"
                mensaje="Aquí aparecerán los productos cuyo stock actual esté por debajo de su stock mínimo." />
        @else
            {{-- Del mayor al menor faltante; ⇅ abre "Nuevo movimiento" con una entrada de ese producto --}}
            <x-tabla>
                <x-slot:encabezado>
                    <tr>
                        <th>Producto</th>
                        <th class="hidden md:table-cell">Categoría</th>
                        <th class="text-right">Stock</th>
                        <th class="hidden text-right sm:table-cell">Faltan</th>
                        <th><span class="sr-only">Acciones</span></th>
                    </tr>
                </x-slot:encabezado>

                @foreach ($alertas as $producto)
                    @php
                        $unidad = $producto->unidad_medida;
                        $faltan = $producto->faltante();
                    @endphp
                    <tr>
                        <td class="w-full">
                            {{-- El nombre lleva al historial de ese producto --}}
                            <a href="{{ route('movimientos.index', ['producto' => $producto->id]) }}"
                                class="block font-display text-base underline-offset-4 [overflow-wrap:anywhere] hover:underline">{{ $producto->nombre }}</a>
                            <span class="block font-mono text-micro font-medium text-tenue md:hidden">{{ $producto->categoria->nombre }}</span>
                            <span class="mt-1.5 flex items-center gap-1.5 font-mono text-micro font-medium sm:hidden">
                                <span class="size-1.5 rounded-full bg-acento" aria-hidden="true"></span>
                                Faltan {{ $unidad->cantidad($faltan) }}
                            </span>
                        </td>
                        <td class="hidden font-mono text-xs whitespace-nowrap md:table-cell">{{ $producto->categoria->nombre }}</td>
                        <td class="text-right whitespace-nowrap">
                            <span class="block font-display text-base">{{ UnidadMedida::numero($producto->stock_actual) }}</span>
                            <span class="block font-mono text-micro font-medium text-tenue">de {{ UnidadMedida::numero($producto->stock_minimo) }} {{ $unidad->value }}</span>
                        </td>
                        <td class="hidden text-right font-mono text-xs whitespace-nowrap sm:table-cell">{{ $unidad->cantidad($faltan) }}</td>
                        <td class="px-2!">
                            <a href="{{ route('movimientos.create', ['producto_id' => $producto->id, 'tipo' => 'entrada', 'volver' => 'dashboard']) }}"
                                aria-label="Registrar entrada de {{ $producto->nombre }}" title="Registrar entrada"
                                class="flex size-10 items-center justify-center rounded-full transition hover:bg-fondo">
                                <x-icono nombre="movimientos" class="size-[18px]" />
                            </a>
                        </td>
                    </tr>
                @endforeach
            </x-tabla>
        @endif
    </section>
@endsection
