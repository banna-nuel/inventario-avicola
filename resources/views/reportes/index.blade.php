@extends('layouts.app')
@section('title', 'Reportes')
@section('volver', route('home'))
@section('content')
    {{-- Tres accesos con el mismo patrón de tarjetas de Home --}}
    <x-encabezado titulo="Reportes" subtitulo="En pantalla · se pueden imprimir" />

    <nav aria-label="Reportes" class="flex flex-col gap-4">
        <x-tarjeta-acceso :href="route('reportes.stock-por-categoria')" titulo="Stock por categoría"
            descripcion="Cada producto activo con su stock y su unidad" icono="etiqueta" />
        <x-tarjeta-acceso :href="route('reportes.stock-bajo')" titulo="Stock bajo"
            descripcion="Productos bajo su mínimo, del mayor al menor faltante" icono="alerta" />
        <x-tarjeta-acceso :href="route('reportes.mas-movidos')" titulo="Más movidos"
            descripcion="Los 10 con más movimientos en un rango de fechas" icono="movimientos" />
    </nav>
@endsection
