@extends('layouts.invitado')
@section('title', 'Iniciar sesión')
@section('content')
    <div class="flex w-full max-w-sm flex-col gap-10">
        <div class="flex flex-col items-center gap-4 text-center">
            <p class="font-mono text-xs text-tenue">Iniciar sesión</p>
            <h1 class="font-display text-titulo font-light text-balance">Inventario Avícola</h1>
            <p class="font-mono text-xs text-tenue">Control de stock, entradas y salidas</p>
        </div>

        <form action="/login" method="POST" class="flex flex-col gap-5" novalidate>
            @csrf

            <x-campo etiqueta="Correo electrónico" name="email" tipo="email" autocomplete="email" autofocus required />

            <x-campo etiqueta="Contraseña" name="password" tipo="password" autocomplete="current-password" required />

            <x-boton class="mt-3 w-full" cargando="Entrando…">Entrar</x-boton>
        </form>
    </div>
@endsection
