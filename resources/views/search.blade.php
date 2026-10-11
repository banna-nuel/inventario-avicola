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

        <label for="q" class="sr-only">Buscar productos{{ auth()->user()->esAdmin() ? ', categorías o usuarios' : ' por nombre o categoría' }}</label>
        <div class="mb-5 flex items-center gap-2.5 rounded-campo border border-transparent bg-superficie px-4 py-3.5 transition focus-within:border-tinta focus-within:shadow-[0_0_0_4px_rgb(245_166_35/0.45)] hover:border-borde-fuerte">
            <x-icono nombre="lupa" class="size-[18px] text-tenue" />
            {{-- 16 px en móvil para que el teclado no haga zoom en iPhone; 14 px (Figma) desde sm --}}
            <input id="q" name="q" type="search" value="{{ $q }}" enterkeyhint="search" autocomplete="off" placeholder="Buscar"
                class="min-w-0 flex-1 bg-transparent font-mono text-base outline-none focus-visible:outline-none focus-visible:shadow-none sm:text-sm">
        </div>

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
