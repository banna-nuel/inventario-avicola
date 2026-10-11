{{-- La hoja "Editar categoría" se abre encima del listado: esta vista extiende categorias/index --}}
@extends('categorias.index')
@section('title', 'Editar categoría')

@section('hoja')
    <x-hoja titulo="Editar categoría" :cerrar="route('categorias.index')">
        <form action="{{ route('categorias.update', $categoria) }}" method="POST" class="group flex flex-1 flex-col">
            @csrf
            @method('PUT')

            <div class="flex flex-1 flex-col justify-center px-5 sm:px-10">
                <x-campo-titulo name="nombre" etiqueta="Nombre de la categoría" placeholder="Nombre"
                    :valor="$categoria->nombre" maximo="100" autofocus />

                @php
                    $productos = $categorias->firstWhere('id', $categoria->id)?->productos_count ?? 0;
                @endphp
                <p class="text-center font-mono text-xs text-tenue">
                    {{ $productos }} {{ $productos === 1 ? 'producto usa' : 'productos usan' }} esta categoría
                </p>
            </div>

            <div class="flex justify-center pt-8">
                <x-boton cargando="Guardando…"
                    class="group-has-[textarea:placeholder-shown]:bg-deshabilitado group-has-[textarea:placeholder-shown]:text-tinta/60">
                    Guardar cambios
                </x-boton>
            </div>
        </form>
    </x-hoja>
@endsection
