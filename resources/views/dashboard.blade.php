@extends('layouts.app')
@section('title', 'Dashboard')
@section('content')
    {{-- Esqueleto: los datos reales los entrega el controlador en la Fase 6 (RF-03) --}}
    <x-encabezado etiqueta="Dashboard" titulo="Estado del inventario" />

    <section aria-label="Indicadores" class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <x-indicador etiqueta="Productos activos" :valor="$indicadores['activos'] ?? null" />
        <x-indicador etiqueta="Productos en stock bajo" :valor="$indicadores['stockBajo'] ?? null"
            :alerta="($indicadores['stockBajo'] ?? 0) > 0" />
        <x-indicador etiqueta="Movimientos de hoy" :valor="$indicadores['movimientosHoy'] ?? null" />
    </section>

    <section aria-labelledby="titulo-alertas" class="mt-12 flex flex-col gap-5">
        <div class="flex items-baseline gap-2">
            <h2 id="titulo-alertas" class="font-display text-xl tracking-titulo">Productos en alerta</h2>
            <span class="font-mono text-xs">[{{ count($alertas ?? []) }}]</span>
        </div>

        @if (empty($alertas))
            <x-estado-vacio titulo="Sin alertas por ahora"
                mensaje="Aquí aparecerán los productos cuyo stock actual esté por debajo de su stock mínimo." />
        @endif
    </section>
@endsection
