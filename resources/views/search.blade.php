@extends('layouts.app')
@section('title', 'Buscar')
@section('content')
    @php
        $q = trim((string) request('q', ''));

        // El encargado solo busca productos; el administrador también categorías y usuarios
        $filtros = ['' => 'Todo', 'productos' => 'Productos'];
        if (auth()->user()->esAdmin()) {
            $filtros += ['categorias' => 'Categorías', 'usuarios' => 'Usuarios'];
        }
    @endphp

    <x-encabezado etiqueta="Buscar" titulo="¿Qué estás buscando?" />

    <form action="/search" method="GET" role="search" class="flex flex-col gap-6">
        {{-- Conserva el filtro elegido al hacer una nueva búsqueda --}}
        @if (request('tipo'))
            <input type="hidden" name="tipo" value="{{ request('tipo') }}">
        @endif

        <label for="q" class="sr-only">Buscar productos{{ auth()->user()->esAdmin() ? ', categorías o usuarios' : '' }}</label>
        <div class="flex items-center gap-3 rounded-campo border border-transparent bg-superficie px-4 py-3.5 transition focus-within:border-tinta focus-within:shadow-[0_0_0_4px_rgb(245_166_35/0.45)] hover:border-borde-fuerte">
            <x-icono nombre="buscar" class="text-tenue" />
            <input id="q" name="q" type="search" value="{{ $q }}" enterkeyhint="search" autocomplete="off"
                placeholder="{{ auth()->user()->esAdmin() ? 'Producto, categoría o usuario' : 'Nombre del producto o categoría' }}"
                class="flex-1 bg-transparent font-mono text-base outline-none focus-visible:outline-none focus-visible:shadow-none sm:text-sm">
        </div>

        <x-barra-filtros :opciones="$filtros" :actual="request('tipo', '')" />
    </form>

    <section aria-labelledby="titulo-resultados" class="mt-10 flex flex-col gap-5">
        <div class="flex items-baseline gap-2">
            <h2 id="titulo-resultados" class="font-display text-xl tracking-titulo">Resultados</h2>
            <span class="font-mono text-xs">[{{ $total ?? 0 }}]</span>
        </div>

        @if ($q === '')
            <x-estado-vacio titulo="Escribe para buscar"
                mensaje="Busca por nombre de producto o de categoría. Presiona Enter para ver los resultados." />
        @elseif (empty($total))
            <x-estado-vacio titulo="Sin resultados" :mensaje="'No encontramos coincidencias para «' . $q . '».'" />
        @endif
    </section>
@endsection
