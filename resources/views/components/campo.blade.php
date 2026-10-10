{{--
    Campo de formulario con etiqueta, ayuda y error de validación.
    Recupera solo el valor anterior (old) cuando la validación falla.

    <x-campo etiqueta="Nombre" name="nombre" :valor="$categoria->nombre ?? ''" />
    <x-campo etiqueta="Contraseña" name="password" tipo="password" autocomplete="current-password" />
--}}
@props([
    'etiqueta',
    'name',
    'tipo' => 'text',
    'valor' => null,
    'ayuda' => null,
])

@php
    $id = $attributes->get('id', $name);
    $tieneError = $errors->has($name);
    $idAyuda = $id.'-ayuda';
    $idError = $id.'-error';
    $describe = trim(($ayuda ? $idAyuda : '').' '.($tieneError ? $idError : ''));
@endphp

<div class="flex flex-col gap-2">
    <label for="{{ $id }}" class="font-mono text-xs text-tenue">{{ $etiqueta }}</label>

    <input
        id="{{ $id }}"
        name="{{ $name }}"
        type="{{ $tipo }}"
        @if ($tipo !== 'password') value="{{ old($name, $valor) }}" @endif
        @if ($describe) aria-describedby="{{ $describe }}" @endif
        @if ($tieneError) aria-invalid="true" @endif
        {{ $attributes->except('id')->merge([
            'class' => 'w-full rounded-campo border bg-superficie px-4 py-3 font-mono text-base text-tinta transition outline-none focus-visible:outline-none focus:border-tinta focus:shadow-[0_0_0_4px_rgb(245_166_35/0.45)] sm:text-sm '
                .($tieneError ? 'border-peligro' : 'border-transparent hover:border-borde-fuerte'),
        ]) }}
    >

    @if ($ayuda)
        <p id="{{ $idAyuda }}" class="font-mono text-xs text-tenue">{{ $ayuda }}</p>
    @endif

    @error($name)
        <p id="{{ $idError }}" class="font-mono text-xs text-peligro">{{ $message }}</p>
    @enderror
</div>
