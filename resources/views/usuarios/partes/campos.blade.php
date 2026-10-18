{{--
    Campos comunes de crear y editar usuario (el nombre va aparte, en el campo grande).
    $usuario es null al crear; $esSuPropiaCuenta solo existe al editar.
--}}
@use('App\Models\User')
<div class="flex flex-col gap-5">
    {{-- autocomplete="off": que el navegador no rellene aquí el correo de quien está usando la app --}}
    <x-campo name="email" etiqueta="Correo electrónico" tipo="email" autocomplete="off" :valor="$usuario?->email" />

    @if ($esSuPropiaCuenta ?? false)
        {{-- RN-07: un administrador no puede quitarse el rol (el servidor también lo valida) --}}
        <input type="hidden" name="rol" value="admin">
        <div class="flex flex-col gap-2">
            <p class="font-mono text-xs text-tenue">Rol</p>
            <p class="rounded-campo border border-borde px-4 py-3 font-mono text-base sm:text-sm">Administrador</p>
            <p class="font-mono text-xs text-tenue">Es tu cuenta: no puedes quitarte el rol de administrador.</p>
        </div>
    @else
        <x-select name="rol" etiqueta="Rol" :opciones="User::ROLES" vacio="Elige un rol" :valor="$usuario?->rol" />
    @endif

    {{-- Al crear, la define el administrador; al editar, vacía no la cambia (sin recuperación por correo en la v1) --}}
    <x-campo name="password" tipo="password" autocomplete="new-password"
        :etiqueta="$usuario ? 'Nueva contraseña (opcional)' : 'Contraseña inicial'"
        :ayuda="$usuario ? 'Déjala vacía para no cambiarla. Mínimo 8 caracteres.' : 'Mínimo 8 caracteres. Compártela con la persona por un medio seguro.'" />
</div>
