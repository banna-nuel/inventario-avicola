<!DOCTYPE html>
<html lang="es">

<head>
    @include('layouts.partes.head')
</head>

{{-- Con una hoja abierta, el fondo no hace scroll --}}
<body class="min-h-dvh @hasSection('hoja') overflow-hidden @endif">
    <a href="#contenido"
        class="sr-only focus:not-sr-only focus:fixed focus:top-3 focus:left-3 focus:z-50 focus:rounded-full focus:bg-tinta focus:px-4 focus:py-2 focus:text-superficie">
        Saltar al contenido
    </a>

    {{--
        Encabezado (TopBar del Figma): zona segura de 56 px arriba y una barra de 64 px
        con el logotipo a 16 px. A los lados: volver (opcional) y cerrar sesión.
    --}}
    <header class="vt-encabezado w-full px-5 pt-[max(env(safe-area-inset-top),56px)]" @hasSection('hoja') inert @endif>
        <div class="flex h-16 items-start justify-between pt-4">
            <div class="-mt-1.5 size-10">
                @hasSection('volver')
                    <a href="@yield('volver')" aria-label="Volver" title="Volver"
                        class="flex size-10 items-center justify-center rounded-full transition hover:bg-superficie">
                        <x-icono nombre="flecha-izquierda" />
                    </a>
                @endif
            </div>

            <a href="/home" class="font-display text-xl leading-7 tracking-titulo whitespace-nowrap">Inventario Avícola</a>

            <form action="/logout" method="POST" class="-mt-1.5 size-10">
                @csrf
                <button type="submit" aria-label="Cerrar sesión" title="Cerrar sesión"
                    class="flex size-10 items-center justify-center rounded-full transition hover:bg-superficie">
                    <x-icono nombre="salir" />
                </button>
            </form>
        </div>
    </header>

    {{-- Contenido a todo el ancho con márgenes de 20 px, como en el Figma; 128 px abajo para no quedar bajo la barra --}}
    {{-- Con una hoja abierta, lo de atrás queda inerte (sin foco ni clics) --}}
    <main id="contenido" class="vt-contenido w-full px-5 pb-32" @hasSection('hoja') inert @endif>
        {{-- Mensajes flash: los controladores usan ->with('success', ...) o ->with('error', ...) --}}
        @if (session('success'))
            <x-alerta tipo="exito" class="mt-2">{{ session('success') }}</x-alerta>
        @endif
        @if (session('error'))
            <x-alerta tipo="error" class="mt-2">{{ session('error') }}</x-alerta>
        @endif

        @yield('content')
    </main>

    {{-- Hoja de formulario (crear/editar), encima del listado: ver components/hoja.blade.php --}}
    @yield('hoja')

    {{-- Navegación flotante tipo píldora (BottomNav del Figma) --}}
    @php
        $enlaces = [
            ['url' => '/home', 'etiqueta' => 'Inicio', 'icono' => 'casa', 'vt' => 'vt-icono-inicio', 'activo' => request()->is('home')],
            ['url' => '/dashboard', 'etiqueta' => 'Dashboard', 'icono' => 'panel', 'vt' => 'vt-icono-dashboard', 'activo' => request()->is('dashboard')],
            ['url' => '/search', 'etiqueta' => 'Buscar', 'icono' => 'buscar', 'vt' => 'vt-icono-buscar', 'activo' => request()->is('search')],
        ];
        // Posición del ítem activo (0, 1 o 2); false si la página no está en la barra
        $indiceActivo = collect($enlaces)->search(fn ($enlace) => $enlace['activo']);
    @endphp
    <nav aria-label="Navegación principal"
        class="pointer-events-none fixed inset-x-0 bottom-0 z-40 flex justify-center px-2.5 pb-[max(34px,env(safe-area-inset-bottom))]">
        <div class="vt-barra pointer-events-auto relative rounded-full bg-tinta/60 p-2 shadow-nav backdrop-blur-lg">
            @if ($indiceActivo !== false)
                {{--
                    Círculo negro del ítem activo. Es un solo elemento que se corre 74 px por posición
                    (50 px de botón + 24 px de separación). Como se llama igual en todas las páginas
                    (vt-nav-activo), al navegar se desliza hasta el ítem nuevo.
                --}}
                <span class="vt-nav-activo pointer-events-none absolute top-2 left-2 size-[50px] rounded-full bg-tinta"
                    style="transform: translateX({{ $indiceActivo * 74 }}px)" aria-hidden="true">
                    {{-- Acento naranja mínimo del ítem activo --}}
                    <span class="absolute bottom-2 left-1/2 size-1 -translate-x-1/2 rounded-full bg-acento"></span>
                </span>
            @endif

            <ul class="flex items-center gap-6">
                @foreach ($enlaces as $enlace)
                    <li>
                        <a href="{{ $enlace['url'] }}" aria-label="{{ $enlace['etiqueta'] }}" title="{{ $enlace['etiqueta'] }}"
                            @if ($enlace['activo']) aria-current="page" @endif
                            class="relative z-10 flex size-[50px] items-center justify-center rounded-full text-superficie transition active:scale-90 {{ $enlace['activo'] ? '' : 'hover:bg-superficie/15' }}">
                            <x-icono :nombre="$enlace['icono']" :class="$enlace['vt']" />
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
    </nav>
</body>

</html>
