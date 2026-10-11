{{-- La hoja "Nuevo movimiento" se abre encima del historial: esta vista extiende movimientos/index --}}
@extends('movimientos.index')
@section('title', 'Nuevo movimiento')
@use('App\Enums\UnidadMedida')

@section('hoja')
    @php
        $elegido = (string) old('producto_id', $productoElegido);
        $tipoElegido = old('tipo', $tipo);
        $productoActual = $productos->firstWhere('id', (int) $elegido);
    @endphp

    {{-- $cerrar: vuelve al historial (con sus filtros), a productos o al dashboard, según de dónde llegó --}}
    <x-hoja titulo="Nuevo movimiento" :cerrar="$cerrar">
        @if ($productos->isEmpty())
            <x-estado-vacio titulo="No hay productos activos" class="mt-10"
                mensaje="Para registrar una entrada o una salida, primero tiene que existir un producto activo.">
                @if (auth()->user()->esAdmin())
                    <x-boton :href="route('productos.create')">Nuevo producto</x-boton>
                @endif
            </x-estado-vacio>
        @else
            {{-- data-movimiento: public/js/app.js muestra cómo queda el stock y habilita el botón al elegir el tipo --}}
            <form action="{{ route('movimientos.store') }}" method="POST" class="group flex flex-1 flex-col" novalidate data-movimiento>
                @csrf
                @if ($volver)
                    <input type="hidden" name="volver" value="{{ $volver }}">
                @endif

                <div class="flex flex-1 flex-col justify-center gap-7 pt-6 sm:px-5">
                    {{-- Tipo: dos radios nativos con aspecto de interruptor. Ninguno viene marcado a propósito. --}}
                    <fieldset class="flex flex-col items-center gap-2">
                        <legend class="sr-only">Tipo de movimiento</legend>
                        <div class="inline-flex rounded-full bg-superficie p-1 {{ $errors->has('tipo') ? 'ring-1 ring-peligro' : '' }}">
                            @foreach (['entrada' => 'Entrada', 'salida' => 'Salida'] as $valor => $texto)
                                <label class="cursor-pointer">
                                    <input type="radio" name="tipo" value="{{ $valor }}" class="peer sr-only"
                                        @checked($tipoElegido === $valor)
                                        @error('tipo') aria-invalid="true" aria-describedby="tipo-error" @enderror>
                                    <span class="block rounded-full px-7 py-2.5 font-display text-base tracking-titulo transition
                                        hover:bg-fondo peer-checked:bg-tinta peer-checked:text-superficie
                                        peer-focus-visible:outline-2 peer-focus-visible:outline-offset-2 peer-focus-visible:outline-tinta">
                                        {{ $texto }}
                                    </span>
                                </label>
                            @endforeach
                        </div>
                        @error('tipo')
                            <p id="tipo-error" class="font-mono text-xs text-peligro">{{ $message }}</p>
                        @enderror
                    </fieldset>

                    {{-- Productos activos agrupados por categoría; cada opción lleva su stock para el resumen --}}
                    <x-select name="producto_id" etiqueta="Producto" vacio="Elige un producto" :valor="$elegido">
                        @foreach ($productos->groupBy(fn ($producto) => $producto->categoria->nombre)->sortKeys() as $categoria => $grupo)
                            <optgroup label="{{ $categoria }}">
                                @foreach ($grupo as $producto)
                                    <option value="{{ $producto->id }}" @selected((string) $producto->id === $elegido)
                                        data-stock="{{ $producto->stock_actual }}" data-minimo="{{ $producto->stock_minimo }}"
                                        data-unidad="{{ $producto->unidad_medida->value }}" data-singular="{{ $producto->unidad_medida->singular() }}">
                                        {{ $producto->nombre }}
                                    </option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </x-select>

                    {{-- Cantidad: el campo grande de la hoja (como el nombre en categorías y productos) --}}
                    <div class="flex flex-col items-center gap-2">
                        <label for="cantidad" class="font-mono text-xs text-tenue">Cantidad</label>
                        <input id="cantidad" name="cantidad" value="{{ old('cantidad') }}" inputmode="decimal" autocomplete="off" placeholder="0"
                            @if ($productoActual && $tipoElegido) data-foco-inicial @endif
                            @error('cantidad') aria-invalid="true" aria-describedby="cantidad-error" @enderror
                            class="w-full max-w-60 border-b-[1.5px] bg-transparent pb-1 text-center font-display text-[56px] leading-[1.2] tracking-titulo text-tinta caret-tinta outline-none focus-visible:shadow-none focus-visible:outline-none
                                {{ $errors->has('cantidad') ? 'border-peligro' : 'border-transparent focus:border-tinta' }}">
                        @error('cantidad')
                            <p id="cantidad-error" class="text-center font-mono text-xs text-peligro">{{ $message }}</p>
                        @enderror
                    </div>

                    {{--
                        Resumen: stock actual y cómo queda (lo actualiza public/js/app.js al escribir).
                        data-estado="bajo" → punto naranja; data-estado="insuficiente" → texto rojo.
                    --}}
                    <p data-resumen aria-live="polite"
                        class="min-h-5 text-center font-mono text-xs text-balance text-tenue
                            data-[estado=bajo]:text-tinta data-[estado=bajo]:before:mr-2 data-[estado=bajo]:before:inline-block data-[estado=bajo]:before:size-2 data-[estado=bajo]:before:rounded-full data-[estado=bajo]:before:bg-acento
                            data-[estado=insuficiente]:text-peligro">
                        @if ($productoActual)
                            Stock actual: {{ $productoActual->unidad_medida->cantidad($productoActual->stock_actual) }}
                            · mínimo {{ UnidadMedida::numero($productoActual->stock_minimo) }}
                        @else
                            Elige un producto para ver su stock.
                        @endif
                    </p>
                </div>

                {{-- Deshabilitado hasta elegir entrada o salida (el JS lo habilita al marcar el tipo) --}}
                <div class="flex flex-col items-center gap-3 pt-8">
                    <x-boton cargando="Registrando…" :disabled="! $tipoElegido">Registrar</x-boton>
                    <p class="font-mono text-xs text-tenue group-has-[input[name=tipo]:checked]:hidden">
                        Elige entrada o salida para registrar.
                    </p>
                </div>
            </form>
        @endif
    </x-hoja>
@endsection
