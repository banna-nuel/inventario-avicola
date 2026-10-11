@extends('layouts.app')
@section('title', 'Más movidos')
@section('volver', route('reportes.index'))
@use('App\Enums\UnidadMedida')
@section('content')
    @php
        $rango = ['desde' => $desde->format('Y-m-d'), 'hasta' => $hasta->format('Y-m-d')];
    @endphp

    @include('reportes.partes.encabezado', [
        'titulo' => 'Más movidos',
        'subtitulo' => 'Del ' . $desde->translatedFormat('j M Y') . ' al ' . $hasta->translatedFormat('j M Y'),
    ])

    {{-- Rango (GET, validado por RangoFechasRequest). No sale en la hoja impresa. --}}
    <form action="{{ route('reportes.mas-movidos') }}" method="GET" novalidate
        class="mb-8 grid items-start gap-4 rounded-tarjeta border border-borde-fuerte p-4 sm:grid-cols-[1fr_1fr_auto] print:hidden">
        <x-campo name="desde" etiqueta="Desde" tipo="date" :valor="$rango['desde']" />
        <x-campo name="hasta" etiqueta="Hasta (incluido)" tipo="date" :valor="$rango['hasta']" />
        <div class="sm:pt-6">
            <x-boton tamano="chico" cargando="Generando…">Generar</x-boton>
        </div>
    </form>

    @if ($productos->isEmpty())
        <x-estado-vacio titulo="Sin movimientos en este rango" mensaje="Prueba con otras fechas." />
    @else
        <x-titulo-seccion titulo="Top 10 por número de movimientos" :contador="$productos->count()" class="pb-3.5" />

        {{-- Entradas y salidas en la unidad de cada producto; cada fila abre su historial en el mismo rango --}}
        <x-tabla>
            <x-slot:encabezado>
                <tr>
                    <th class="w-0">#</th>
                    <th>Producto</th>
                    <th class="text-right">Movimientos</th>
                    <th class="hidden text-right sm:table-cell">Entradas</th>
                    <th class="hidden text-right sm:table-cell">Salidas</th>
                </tr>
            </x-slot:encabezado>

            @foreach ($productos as $posicion => $producto)
                @php
                    $unidad = $producto->unidad_medida;
                    $entradas = UnidadMedida::numero($producto->entradas ?? 0);
                    $salidas = UnidadMedida::numero($producto->salidas ?? 0);
                @endphp
                <tr>
                    <td class="font-mono text-xs text-tenue">{{ $posicion + 1 }}</td>
                    <td class="w-full">
                        <a href="{{ route('movimientos.index', ['producto' => $producto->id] + $rango) }}"
                            class="block font-display text-base underline-offset-4 [overflow-wrap:anywhere] hover:underline">{{ $producto->nombre }}</a>
                        <span class="block font-mono text-micro font-medium text-tenue">{{ $producto->categoria->nombre }} · {{ $unidad->value }}</span>
                        <span class="block font-mono text-micro font-medium text-tenue sm:hidden">Entradas {{ $entradas }} · Salidas {{ $salidas }}</span>
                        @if (! $producto->activo)
                            <x-badge variante="inactivo" class="mt-1.5">Inactivo</x-badge>
                        @endif
                    </td>
                    <td class="text-right font-display text-base">{{ $producto->movimientos_count }}</td>
                    <td class="hidden text-right whitespace-nowrap sm:table-cell">
                        <span class="block font-mono text-xs">{{ $entradas }}</span>
                        <span class="block font-mono text-micro font-medium text-tenue">{{ $unidad->value }}</span>
                    </td>
                    <td class="hidden text-right whitespace-nowrap sm:table-cell">
                        <span class="block font-mono text-xs">{{ $salidas }}</span>
                        <span class="block font-mono text-micro font-medium text-tenue">{{ $unidad->value }}</span>
                    </td>
                </tr>
            @endforeach
        </x-tabla>
    @endif
@endsection
