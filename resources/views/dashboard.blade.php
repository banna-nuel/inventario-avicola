@extends('layouts.app')
@section('title', 'Dashboard')
@section('content')
    {{-- Esqueleto: los datos reales los entrega el controlador en la Fase 6 (RF-03) --}}
    <x-encabezado titulo="Dashboard"
        :subtitulo="'Estado del inventario · ' . now()->translatedFormat('j \d\e F')" />

    <section aria-label="Indicadores" class="grid grid-cols-1 gap-4 pt-2 sm:grid-cols-3">
        <x-indicador etiqueta="Productos activos" :valor="$indicadores['activos'] ?? null" />
        <x-indicador etiqueta="Productos en stock bajo" :valor="$indicadores['stockBajo'] ?? null"
            :alerta="($indicadores['stockBajo'] ?? 0) > 0" />
        <x-indicador etiqueta="Movimientos de hoy" :valor="$indicadores['movimientosHoy'] ?? null" />
    </section>

    <section aria-label="Productos en alerta" class="mt-10 flex flex-col gap-3.5">
        <x-titulo-seccion titulo="Productos en alerta" :contador="count($alertas ?? [])" />

        @if (empty($alertas))
            <x-estado-vacio titulo="Sin alertas por ahora"
                mensaje="Aquí aparecerán los productos cuyo stock actual esté por debajo de su stock mínimo." />
        @endif
    </section>
@endsection
