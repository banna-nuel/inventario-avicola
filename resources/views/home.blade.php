@extends('layouts.app')
@section('title', 'Inicio')
@section('content')
    @php
        $usuario = auth()->user();

        // Módulos de la v1. Cada rol ve solo los que puede usar (matriz de permisos del PROMPT).
        $accesos = collect([
            ['href' => '/productos', 'titulo' => 'Productos', 'icono' => 'caja', 'soloAdmin' => false,
                'descripcion' => $usuario->esAdmin() ? 'Catálogo, stock y unidades' : 'Consulta el stock actual'],
            ['href' => '/movimientos', 'titulo' => 'Movimientos', 'icono' => 'movimientos', 'soloAdmin' => false,
                'descripcion' => 'Entradas, salidas e historial'],
            ['href' => '/categorias', 'titulo' => 'Categorías', 'icono' => 'etiqueta', 'soloAdmin' => true,
                'descripcion' => 'Organiza los productos'],
            ['href' => '/usuarios', 'titulo' => 'Usuarios', 'icono' => 'usuarios', 'soloAdmin' => true,
                'descripcion' => 'Cuentas, roles y acceso'],
            ['href' => '/reportes', 'titulo' => 'Reportes', 'icono' => 'grafica', 'soloAdmin' => true,
                'descripcion' => 'Stock por categoría y rotación'],
        ])->reject(fn ($acceso) => $acceso['soloAdmin'] && ! $usuario->esAdmin());
    @endphp

    {{-- Encabezado como "My Trove [87]" del Figma: título centrado con el número de accesos --}}
    <div class="flex flex-col items-center gap-3 pt-2.5 pb-6 text-center">
        <x-titulo-seccion nivel="h1" :titulo="'Hola, ' . Str::before($usuario->name, ' ')" :contador="$accesos->count()" centrado />
        <p class="font-mono text-xs text-balance text-tenue">
            {{ ucfirst(now()->translatedFormat('l j \d\e F')) }} · {{ $usuario->esAdmin() ? 'Administrador' : 'Encargado de inventario' }}
        </p>
    </div>

    {{-- Lista de tarjetas en una columna, como "Collections" del Figma --}}
    <nav aria-label="Accesos" class="flex flex-col gap-4 pt-2">
        @foreach ($accesos as $acceso)
            <x-tarjeta-acceso :href="$acceso['href']" :titulo="$acceso['titulo']" :icono="$acceso['icono']"
                :descripcion="$acceso['descripcion']" />
        @endforeach
    </nav>
@endsection
