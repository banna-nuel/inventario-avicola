@extends('layouts.app')
@section('title', 'Inicio')
@section('content')
    @php
        $usuario = auth()->user();

        // Módulos que este rol puede usar (definidos una sola vez en config/navegacion.php)
        $accesos = $usuario->enlaces('modulos');
    @endphp

    {{-- Encabezado como "My Trove [87]" del Figma: título centrado con el número de accesos --}}
    <div class="flex flex-col items-center gap-3 pt-2.5 pb-6 text-center">
        <x-titulo-seccion nivel="h1" :titulo="'Hola, ' . Str::before($usuario->name, ' ')" :contador="count($accesos)" centrado />
        <p class="font-mono text-xs text-balance text-tenue">
            {{ ucfirst(now()->translatedFormat('l j \d\e F')) }} · {{ $usuario->esAdmin() ? 'Administrador' : 'Encargado de inventario' }}
        </p>
    </div>

    {{-- Lista de tarjetas en una columna, como "Collections" del Figma --}}
    <nav aria-label="Accesos" class="flex flex-col gap-4 pt-2">
        @foreach ($accesos as $acceso)
            <x-tarjeta-acceso :href="route($acceso['ruta'])" :titulo="$acceso['etiqueta']" :icono="$acceso['icono']"
                :descripcion="$acceso['descripcion']" />
        @endforeach
    </nav>
@endsection
