{{--
    Lista desplegable con el estilo de los campos. Es un <select> nativo a propósito:
    funciona con teclado y lector de pantalla, y en el celular abre el selector del sistema.

    <x-select etiqueta="Categoría" name="categoria_id" :opciones="$categorias->pluck('nombre', 'id')"
        vacio="Elige una categoría" :valor="$producto->categoria_id" />

    - opciones: [valor => texto]. Si necesitas opciones con datos extra (data-*), escríbelas en el slot.
    - vacio: texto de la primera opción, sin valor ("Todas", "Elige...").
    - etiqueta-oculta: la etiqueta solo la oye el lector de pantalla (por ejemplo en los filtros).
--}}
@props([
    'etiqueta',
    'name',
    'opciones' => [],
    'valor' => null,
    'vacio' => null,
    'ayuda' => null,
    'etiquetaOculta' => false,
])

@php
    $id = $attributes->get('id', $name);
    $tieneError = $errors->has($name);
    $seleccionado = (string) old($name, $valor);
    $idAyuda = $id.'-ayuda';
    $idError = $id.'-error';
    $describe = trim(($ayuda ? $idAyuda : '').' '.($tieneError ? $idError : ''));
@endphp

<div class="flex flex-col gap-2">
    <label for="{{ $id }}" class="font-mono text-xs text-tenue {{ $etiquetaOculta ? 'sr-only' : '' }}">{{ $etiqueta }}</label>

    <div class="relative">
        <select
            id="{{ $id }}"
            name="{{ $name }}"
            @if ($describe) aria-describedby="{{ $describe }}" @endif
            @if ($tieneError) aria-invalid="true" @endif
            {{ $attributes->except('id')->merge([
                // appearance-none quita la flecha del sistema; la nuestra es el ícono de abajo
                'class' => 'w-full cursor-pointer appearance-none rounded-campo border bg-superficie py-3 pr-11 pl-4 font-mono text-base text-tinta transition outline-none focus-visible:outline-none focus:border-tinta focus:shadow-[0_0_0_4px_rgb(245_166_35/0.45)] sm:text-sm '
                    .($tieneError ? 'border-peligro' : 'border-transparent hover:border-borde-fuerte'),
            ]) }}
        >
            @if ($vacio)
                <option value="" @selected($seleccionado === '')>{{ $vacio }}</option>
            @endif
            @foreach ($opciones as $valorOpcion => $texto)
                <option value="{{ $valorOpcion }}" @selected((string) $valorOpcion === $seleccionado)>{{ $texto }}</option>
            @endforeach
            {{ $slot }}
        </select>
        <x-icono nombre="chevron-abajo" class="pointer-events-none absolute top-1/2 right-4 size-4 -translate-y-1/2 text-tenue" />
    </div>

    @if ($ayuda)
        <p id="{{ $idAyuda }}" class="font-mono text-xs text-tenue">{{ $ayuda }}</p>
    @endif

    @error($name)
        <p id="{{ $idError }}" class="font-mono text-xs text-peligro">{{ $message }}</p>
    @enderror
</div>
