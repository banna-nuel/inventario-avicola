{{-- La hoja "Editar usuario" se abre encima del listado: esta vista extiende usuarios/index --}}
@extends('usuarios.index')
@section('title', 'Editar usuario')

@section('hoja')
    <x-hoja titulo="Editar usuario" :cerrar="route('usuarios.index', $filtros)">
        <form action="{{ route('usuarios.update', ['usuario' => $usuario] + $filtros) }}" method="POST"
            class="group flex flex-col" novalidate>
            @csrf
            @method('PUT')

            <div class="flex flex-col gap-6 sm:px-5">
                <x-campo-titulo name="name" etiqueta="Nombre completo" placeholder="Nombre" :valor="$usuario->name" maximo="255" />

                @include('usuarios.partes.campos')
            </div>

            <div class="flex justify-center pt-8">
                <x-boton cargando="Guardando…"
                    class="group-has-[textarea:placeholder-shown]:bg-deshabilitado group-has-[textarea:placeholder-shown]:text-tinta/60">
                    Guardar cambios
                </x-boton>
            </div>
        </form>

        {{-- Estado: las cuentas no se eliminan, se desactivan. Nadie puede desactivar la suya (RN-07). --}}
        <div class="mt-10 flex flex-col items-center gap-3 border-t border-borde pt-6 text-center">
            @if ($esSuPropiaCuenta)
                <p class="font-mono text-xs text-tenue">Es tu cuenta: no puedes desactivarla.</p>
            @else
                <form action="{{ route('usuarios.cambiar-estado', ['usuario' => $usuario] + $filtros) }}" method="POST"
                    class="flex flex-col items-center gap-3"
                    @if ($usuario->activo) data-confirmar="¿Desactivar a «{{ $usuario->name }}»? No podrá iniciar sesión hasta que la actives de nuevo." @endif>
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="activo" value="{{ $usuario->activo ? 0 : 1 }}">

                    <p class="font-mono text-xs text-tenue">
                        {{ $usuario->activo ? 'Activa: puede iniciar sesión.' : 'Inactiva: no puede iniciar sesión.' }}
                    </p>
                    <x-boton :variante="$usuario->activo ? 'secundario' : 'primario'" tamano="chico" cargando="Guardando…">
                        {{ $usuario->activo ? 'Desactivar cuenta' : 'Activar cuenta' }}
                    </x-boton>
                </form>
            @endif
        </div>
    </x-hoja>
@endsection
