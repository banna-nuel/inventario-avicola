{{-- La hoja "Editar producto" se abre encima del listado: esta vista extiende productos/index --}}
@extends('productos.index')
@section('title', 'Editar producto')

@section('hoja')
    <x-hoja titulo="Editar producto" :cerrar="route('productos.index', $filtros)">
        <form action="{{ route('productos.update', ['producto' => $producto] + $filtros) }}" method="POST"
            class="group flex flex-col" novalidate>
            @csrf
            @method('PUT')

            <div class="flex flex-col gap-6 sm:px-5">
                <x-campo-titulo name="nombre" etiqueta="Nombre del producto" placeholder="Nombre"
                    :valor="$producto->nombre" maximo="150" />

                {{-- El stock no se edita aquí: solo cambia con entradas y salidas (RN-04) --}}
                <p class="text-center font-mono text-xs text-tenue">
                    Stock actual: <span class="text-tinta">{{ $producto->unidad_medida->cantidad($producto->stock_actual) }}</span>
                    · cambia solo con movimientos ·
                    <a href="{{ route('movimientos.index', ['producto' => $producto->id]) }}" class="text-tinta underline underline-offset-4">Ver historial ({{ $movimientos }})</a>
                </p>

                @include('productos.partes.campos')
            </div>

            <div class="flex justify-center pt-8">
                <x-boton cargando="Guardando…"
                    class="group-has-[textarea:placeholder-shown]:bg-deshabilitado group-has-[textarea:placeholder-shown]:text-tinta/60">
                    Guardar cambios
                </x-boton>
            </div>
        </form>

        {{-- RN-03: el producto no se elimina; se desactiva (y se puede volver a activar) --}}
        <form action="{{ route('productos.cambiar-estado', ['producto' => $producto] + $filtros) }}" method="POST"
            class="mt-10 flex flex-col items-center gap-3 border-t border-borde pt-6 text-center"
            @if ($producto->activo) data-confirmar="¿Desactivar «{{ $producto->nombre }}»? No admitirá nuevos movimientos; su historial se conserva." @endif>
            @csrf
            @method('PATCH')
            <input type="hidden" name="activo" value="{{ $producto->activo ? 0 : 1 }}">

            <p class="font-mono text-xs text-tenue">
                {{ $producto->activo ? 'Activo: admite entradas y salidas.' : 'Inactivo: no admite movimientos.' }}
            </p>
            <x-boton :variante="$producto->activo ? 'secundario' : 'primario'" tamano="chico" cargando="Guardando…">
                {{ $producto->activo ? 'Desactivar producto' : 'Activar producto' }}
            </x-boton>
        </form>
    </x-hoja>
@endsection
