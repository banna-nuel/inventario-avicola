@extends('layouts.app')
@section('title', 'Stock bajo')
@section('volver', route('reportes.index'))
@use('App\Enums\UnidadMedida')
@section('content')
    @include('reportes.partes.encabezado', [
        'titulo' => 'Stock bajo',
        'subtitulo' => $productos->count() . ' ' . ($productos->count() === 1 ? 'producto activo' : 'productos activos') . ' bajo su mínimo',
    ])

    @if ($productos->isEmpty())
        <x-estado-vacio titulo="Sin productos en stock bajo"
            mensaje="Ningún producto activo está por debajo de su stock mínimo." />
    @else
        {{-- Del mayor al menor faltante: la misma regla y el mismo orden que el Dashboard (Producto::enAlerta) --}}
        <x-tabla>
            <x-slot:encabezado>
                <tr>
                    <th>Producto</th>
                    <th class="hidden md:table-cell">Categoría</th>
                    <th class="text-right">Stock</th>
                    <th class="hidden text-right sm:table-cell">Mínimo</th>
                    <th class="text-right">Faltan</th>
                </tr>
            </x-slot:encabezado>

            @foreach ($productos as $producto)
                @php
                    $unidad = $producto->unidad_medida;
                @endphp
                <tr>
                    <td class="w-full">
                        <a href="{{ route('movimientos.index', ['producto' => $producto->id]) }}"
                            class="block font-display text-base underline-offset-4 [overflow-wrap:anywhere] hover:underline">{{ $producto->nombre }}</a>
                        <span class="block font-mono text-micro font-medium text-tenue md:hidden">{{ $producto->categoria->nombre }}</span>
                    </td>
                    <td class="hidden font-mono text-xs whitespace-nowrap md:table-cell">{{ $producto->categoria->nombre }}</td>
                    <td class="text-right whitespace-nowrap">
                        <span class="block font-display text-base">{{ UnidadMedida::numero($producto->stock_actual) }}</span>
                        <span class="block font-mono text-micro font-medium text-tenue">{{ $unidad->nombrePara($producto->stock_actual) }}</span>
                    </td>
                    <td class="hidden text-right font-mono text-xs whitespace-nowrap sm:table-cell">{{ UnidadMedida::numero($producto->stock_minimo) }}</td>
                    <td class="text-right whitespace-nowrap">
                        <span class="block font-display text-base">{{ UnidadMedida::numero($producto->faltante()) }}</span>
                        <span class="block font-mono text-micro font-medium text-tenue">{{ $unidad->nombrePara($producto->faltante()) }}</span>
                    </td>
                </tr>
            @endforeach
        </x-tabla>
    @endif
@endsection
