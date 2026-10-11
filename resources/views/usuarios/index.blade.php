@extends('layouts.app')
@section('title', 'Usuarios')
@section('volver', route('home'))
@section('content')
    @php
        $total = $usuarios->total();
    @endphp

    <x-encabezado titulo="Usuarios" :subtitulo="$total . ' ' . ($total === 1 ? 'usuario' : 'usuarios')">
        <x-boton :href="route('usuarios.create', $filtros)" tamano="icono" aria-label="Nuevo usuario" title="Nuevo usuario" data-abre-hoja>
            <x-icono nombre="mas" class="size-5" />
        </x-boton>
    </x-encabezado>

    {{-- Búsqueda por nombre o correo (GET) --}}
    <form action="{{ route('usuarios.index') }}" method="GET" role="search" class="pb-6">
        <x-buscador :valor="$q" etiqueta="Buscar usuarios por nombre o correo" placeholder="Buscar nombre o correo" />
    </form>

    @if ($usuarios->isEmpty())
        <x-estado-vacio titulo="Sin resultados" :mensaje="'Ningún usuario coincide con «' . $q . '».'">
            <x-boton :href="route('usuarios.index')" variante="secundario">Ver todos</x-boton>
        </x-estado-vacio>
    @else
        <x-tabla>
            <x-slot:encabezado>
                <tr>
                    <th>Usuario</th>
                    {{-- En móvil, rol y estado van bajo el nombre --}}
                    <th class="hidden sm:table-cell">Rol</th>
                    <th class="hidden sm:table-cell">Estado</th>
                    <th><span class="sr-only">Acciones</span></th>
                </tr>
            </x-slot:encabezado>

            @foreach ($usuarios as $usuario)
                @php
                    $esYo = $usuario->is(auth()->user());
                    $rol = $usuario->esAdmin() ? 'Administrador' : 'Encargado';
                @endphp
                <tr>
                    <td class="w-full">
                        <span class="block font-display text-base [overflow-wrap:anywhere] {{ $usuario->activo ? '' : 'text-tenue' }}">
                            {{ $usuario->name }}
                            @if ($esYo)
                                <span class="font-mono text-xs text-tenue">(tú)</span>
                            @endif
                        </span>
                        <span class="block font-mono text-micro font-medium text-tenue [overflow-wrap:anywhere]">{{ $usuario->email }}</span>
                        <span class="mt-1.5 flex flex-wrap gap-1.5 sm:hidden">
                            <x-badge :variante="$usuario->esAdmin() ? 'oscuro' : 'neutro'">{{ $rol }}</x-badge>
                            @if (! $usuario->activo)
                                <x-badge variante="inactivo">Inactivo</x-badge>
                            @endif
                        </span>
                    </td>
                    <td class="hidden sm:table-cell">
                        <x-badge :variante="$usuario->esAdmin() ? 'oscuro' : 'neutro'">{{ $rol }}</x-badge>
                    </td>
                    <td class="hidden font-mono text-xs whitespace-nowrap sm:table-cell">
                        @if ($usuario->activo)
                            Activo
                        @else
                            <x-badge variante="inactivo">Inactivo</x-badge>
                        @endif
                    </td>
                    <td class="px-2!">
                        <a href="{{ route('usuarios.edit', ['usuario' => $usuario] + $filtros) }}" data-abre-hoja
                            aria-label="Editar {{ $usuario->name }}" title="Editar"
                            class="flex size-10 items-center justify-center rounded-full transition hover:bg-fondo">
                            <x-icono nombre="lapiz" class="size-[18px]" />
                        </a>
                    </td>
                </tr>
            @endforeach
        </x-tabla>

        <div class="pt-6">
            {{ $usuarios->links() }}
        </div>
    @endif
@endsection
