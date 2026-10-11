{{-- La hoja "Nuevo usuario" se abre encima del listado: esta vista extiende usuarios/index --}}
@extends('usuarios.index')
@section('title', 'Nuevo usuario')

@section('hoja')
    <x-hoja titulo="Nuevo usuario" :cerrar="route('usuarios.index', $filtros)">
        {{-- novalidate: los errores los da el servidor, en español y junto a cada campo --}}
        <form action="{{ route('usuarios.store', $filtros) }}" method="POST" class="group flex flex-1 flex-col" novalidate>
            @csrf

            <div class="flex flex-1 flex-col justify-center gap-6 sm:px-5">
                <x-campo-titulo name="name" etiqueta="Nombre completo" placeholder="Nombre" maximo="255" />

                @include('usuarios.partes.campos', ['usuario' => null])
            </div>

            {{-- Mientras el nombre está vacío, el botón se ve gris (como en el Figma) --}}
            <div class="flex justify-center pt-8">
                <x-boton cargando="Guardando…"
                    class="group-has-[textarea:placeholder-shown]:bg-deshabilitado group-has-[textarea:placeholder-shown]:text-tinta/60">
                    Crear usuario
                </x-boton>
            </div>
        </form>
    </x-hoja>
@endsection
