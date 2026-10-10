<!DOCTYPE html>
<html lang="es">

<head>
    @include('layouts.partes.head')
</head>

<body class="min-h-dvh">
    <a href="#contenido"
        class="sr-only focus:not-sr-only focus:fixed focus:top-3 focus:left-3 focus:z-50 focus:rounded-full focus:bg-tinta focus:px-4 focus:py-2 focus:text-superficie">
        Saltar al contenido
    </a>

    {{-- Encabezado: volver (opcional) · logotipo · cerrar sesión --}}
    <header class="mx-auto flex h-16 w-full max-w-5xl items-center justify-between gap-2 px-5 pt-4">
        <div class="size-10">
            @hasSection('volver')
                <a href="@yield('volver')" aria-label="Volver" title="Volver"
                    class="flex size-10 items-center justify-center rounded-full transition hover:bg-superficie">
                    <x-icono nombre="flecha-izquierda" />
                </a>
            @endif
        </div>

        <a href="/home" class="font-display text-lg tracking-titulo whitespace-nowrap sm:text-xl">Inventario Avícola</a>

        <form action="/logout" method="POST" class="size-10">
            @csrf
            <button type="submit" aria-label="Cerrar sesión" title="Cerrar sesión"
                class="flex size-10 items-center justify-center rounded-full transition hover:bg-superficie">
                <x-icono nombre="salir" />
            </button>
        </form>
    </header>

    <main id="contenido" class="mx-auto w-full max-w-5xl px-5 pb-40">
        {{-- Mensajes flash: los controladores usan ->with('success', ...) o ->with('error', ...) --}}
        @if (session('success'))
            <x-alerta tipo="exito" class="mt-6">{{ session('success') }}</x-alerta>
        @endif
        @if (session('error'))
            <x-alerta tipo="error" class="mt-6">{{ session('error') }}</x-alerta>
        @endif

        @yield('content')
    </main>

    {{-- Navegación flotante tipo píldora (BottomNav del Figma) --}}
    @php
        $enlaces = [
            ['url' => '/home', 'etiqueta' => 'Inicio', 'icono' => 'casa', 'activo' => request()->is('home')],
            ['url' => '/dashboard', 'etiqueta' => 'Dashboard', 'icono' => 'panel', 'activo' => request()->is('dashboard')],
            ['url' => '/search', 'etiqueta' => 'Buscar', 'icono' => 'buscar', 'activo' => request()->is('search')],
        ];
    @endphp
    <nav aria-label="Navegación principal"
        class="pointer-events-none fixed inset-x-0 bottom-0 z-40 flex justify-center px-3 pb-[max(2rem,env(safe-area-inset-bottom))]">
        <ul class="pointer-events-auto flex items-center gap-6 rounded-full bg-tinta/60 p-2 shadow-nav backdrop-blur-lg">
            @foreach ($enlaces as $enlace)
                <li>
                    <a href="{{ $enlace['url'] }}" aria-label="{{ $enlace['etiqueta'] }}" title="{{ $enlace['etiqueta'] }}"
                        @if ($enlace['activo']) aria-current="page" @endif
                        class="relative flex size-[50px] items-center justify-center rounded-full text-superficie transition active:scale-90 {{ $enlace['activo'] ? 'bg-tinta' : 'hover:bg-superficie/15' }}">
                        <x-icono :nombre="$enlace['icono']" />
                        @if ($enlace['activo'])
                            {{-- Acento naranja mínimo del ítem activo --}}
                            <span class="absolute bottom-2 size-1 rounded-full bg-acento" aria-hidden="true"></span>
                        @endif
                    </a>
                </li>
            @endforeach
        </ul>
    </nav>
</body>

</html>
