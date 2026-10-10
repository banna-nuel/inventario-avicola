@extends('layouts.app')
@section('title', 'Inicio')
@section('content')
    @php
        $usuario = auth()->user();
    @endphp

    <x-encabezado
        :etiqueta="ucfirst(now()->translatedFormat('l j \d\e F')) . ' · ' . ($usuario->esAdmin() ? 'Administrador' : 'Encargado de inventario')"
        :titulo="'Hola, ' . Str::before($usuario->name, ' ')"
    />

    <section aria-labelledby="titulo-accesos" class="flex flex-col gap-5">
        <h2 id="titulo-accesos" class="font-display text-xl tracking-titulo">Accesos</h2>

        {{-- Cada rol ve solo los módulos que puede usar (matriz de permisos del PROMPT) --}}
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <x-tarjeta-acceso href="/productos" titulo="Productos" icono="caja"
                :descripcion="$usuario->esAdmin() ? 'Catálogo, stock y unidades' : 'Consulta el stock actual'" />

            <x-tarjeta-acceso href="/movimientos" titulo="Movimientos" icono="movimientos"
                descripcion="Entradas, salidas e historial" />

            @if ($usuario->esAdmin())
                <x-tarjeta-acceso href="/categorias" titulo="Categorías" icono="etiqueta"
                    descripcion="Organiza los productos" />

                <x-tarjeta-acceso href="/usuarios" titulo="Usuarios" icono="usuarios"
                    descripcion="Cuentas, roles y acceso" />

                <x-tarjeta-acceso href="/reportes" titulo="Reportes" icono="grafica"
                    descripcion="Stock por categoría y rotación" />
            @endif
        </div>
    </section>
@endsection
