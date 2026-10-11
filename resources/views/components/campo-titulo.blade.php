{{--
    Campo principal de una hoja: texto grande y centrado en Platypi (como "Collection name" del Figma).
    Crece en alto con el texto; public/js/app.js achica la letra cuando el nombre es largo
    (40 → 32 → 26 → 22 px) y hace que Enter envíe el formulario en vez de saltar de línea.

    <x-campo-titulo name="nombre" etiqueta="Nombre de la categoría" placeholder="Nombre" :valor="$categoria->nombre" maximo="100" />
--}}
@props(['name', 'etiqueta', 'valor' => null, 'placeholder' => '', 'maximo' => null])

@php
    $id = $attributes->get('id', $name);
    $tieneError = $errors->has($name);
@endphp

<div class="flex w-full flex-col items-center gap-3 py-5">
    <label for="{{ $id }}" class="sr-only">{{ $etiqueta }}</label>

    <textarea
        id="{{ $id }}"
        name="{{ $name }}"
        rows="1"
        placeholder="{{ $placeholder }}"
        data-campo-titulo
        @if ($maximo) maxlength="{{ $maximo }}" @endif
        @if ($tieneError) aria-invalid="true" aria-describedby="{{ $id }}-error" @endif
        {{ $attributes->except('id')->merge([
            // Subrayado: negro al enfocar; rojo si hay error
            'class' => 'field-sizing-content w-full resize-none overflow-hidden border-b-[1.5px] bg-transparent pb-1 text-center font-display text-[40px] leading-[1.25] font-normal tracking-titulo text-tinta caret-tinta outline-none focus-visible:shadow-none focus-visible:outline-none '
                .($tieneError ? 'border-peligro' : 'border-transparent focus:border-tinta'),
        ]) }}
    >{{ old($name, $valor) }}</textarea>

    @error($name)
        <p id="{{ $id }}-error" class="text-center font-mono text-xs text-peligro">{{ $message }}</p>
    @enderror
</div>
