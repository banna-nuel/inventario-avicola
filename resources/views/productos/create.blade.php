{{-- La hoja "Nuevo producto" se abre encima del listado: esta vista extiende productos/index --}}
@extends('productos.index')
@section('title', 'Nuevo producto')

@section('hoja')
    <x-hoja titulo="Nuevo producto" :cerrar="route('productos.index', $filtros)">
        {{-- novalidate: los errores los da el servidor, en español y junto a cada campo --}}
        <form action="{{ route('productos.store', $filtros) }}" method="POST" class="group flex flex-1 flex-col" novalidate>
            @csrf

            <div class="flex flex-1 flex-col justify-center gap-6 sm:px-5">
                <x-campo-titulo name="nombre" etiqueta="Nombre del producto" placeholder="Nombre" maximo="150" />

                @include('productos.partes.campos', ['producto' => null])

                <x-campo name="stock_inicial" etiqueta="Stock inicial (opcional)" inputmode="decimal" autocomplete="off"
                    ayuda="Si es mayor que 0, se registra como una entrada en el historial." />
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
