{{-- La hoja "Nueva categoría" se abre encima del listado: esta vista extiende categorias/index --}}
@extends('categorias.index')
@section('title', 'Nueva categoría')

@section('hoja')
    <x-hoja titulo="Nueva categoría" :cerrar="route('categorias.index')">
        <form action="{{ route('categorias.store') }}" method="POST" class="group flex flex-1 flex-col">
            @csrf

            <div class="flex flex-1 flex-col justify-center px-5 sm:px-10">
                <x-campo-titulo name="nombre" etiqueta="Nombre de la categoría" placeholder="Nombre"
                    maximo="100" autofocus />
            </div>

            {{-- Mientras el nombre está vacío, el botón se ve gris (como en el Figma) --}}
            <div class="flex justify-center pt-8">
                <x-boton cargando="Guardando…"
                    class="group-has-[textarea:placeholder-shown]:bg-deshabilitado group-has-[textarea:placeholder-shown]:text-tinta/60">
                    Guardar
                </x-boton>
            </div>
        </form>
    </x-hoja>
@endsection
