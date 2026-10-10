<!DOCTYPE html>
<html lang="es">

<head>
    @include('layouts.partes.head')
</head>

{{-- Layout para pantallas sin sesión (login y, más adelante, páginas de error) --}}
<body class="flex min-h-dvh flex-col">
    <main id="contenido" class="flex flex-1 items-center justify-center px-5 py-12">
        @yield('content')
    </main>
</body>

</html>
