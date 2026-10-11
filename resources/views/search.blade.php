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

    {{-- Como en el Figma, Search no muestra título: la barra va directo bajo el encabezado --}}
    <h1 class="sr-only">Buscar</h1>

    <form action="{{ route('search') }}" method="GET" role="search" class="pt-2.5">
        {{-- Conserva el filtro elegido al hacer una nueva búsqueda --}}
        @if (request('tipo'))
            <input type="hidden" name="tipo" value="{{ request('tipo') }}">
        @endif

        <x-buscador :valor="$q" class="mb-5"
            :etiqueta="'Buscar productos' . (auth()->user()->esAdmin() ? ', categorías o usuarios' : ' por nombre o categoría')" />

        <x-barra-filtros :opciones="$filtros" :actual="request('tipo', '')" class="pb-6" />
    </form>

    <section aria-label="Resultados" class="flex flex-col gap-3.5">
        <x-titulo-seccion titulo="Resultados" :contador="$total ?? 0" />

        @if ($q === '')
            <x-estado-vacio titulo="Escribe para buscar"
                mensaje="Busca por nombre de producto o de categoría. Presiona Enter para ver los resultados." />
        @elseif (empty($total))
            <x-estado-vacio titulo="Sin resultados" :mensaje="'No encontramos coincidencias para «' . $q . '».'" />
        @endif
    </section>
@endsection
