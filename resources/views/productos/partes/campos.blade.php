{{--
    Campos comunes de crear y editar producto (el nombre va aparte, en el campo grande).
    $producto es null al crear; $tieneMovimientos solo existe al editar.
--}}
@use('App\Enums\UnidadMedida')
<div class="flex flex-col gap-5">
    <x-select name="categoria_id" etiqueta="Categoría" :opciones="$categorias->pluck('nombre', 'id')"
        vacio="Elige una categoría" :valor="$producto?->categoria_id" />

    <div class="grid items-start gap-5 sm:grid-cols-2">
        @if ($tieneMovimientos ?? false)
            {{-- RN-08: con movimientos, la unidad queda fija (el historial está en esa unidad) --}}
            <div class="flex flex-col gap-2">
                <p class="font-mono text-xs text-tenue">Unidad de medida</p>
                <p class="rounded-campo border border-borde px-4 py-3 font-mono text-base sm:text-sm">{{ ucfirst($producto->unidad_medida->value) }}</p>
                <p class="font-mono text-xs text-tenue">No se cambia: ya tiene movimientos.</p>
            </div>
        @else
            <x-select name="unidad_medida" etiqueta="Unidad de medida" :opciones="UnidadMedida::opciones()"
                vacio="Elige una unidad" :valor="$producto?->unidad_medida?->value" />
        @endif

        {{--
            type="text" con inputmode="decimal" (y no type="number"): así se puede escribir "12,5" con coma
            en cualquier navegador, y el celular igual muestra el teclado numérico. El servidor acepta coma o punto.
        --}}
        <x-campo name="stock_minimo" etiqueta="Stock mínimo" inputmode="decimal" autocomplete="off"
            :valor="$producto ? str_replace('.', ',', (string) (float) $producto->stock_minimo) : null"
            ayuda="Si el stock baja de aquí, aparece la alerta." />
    </div>
</div>
