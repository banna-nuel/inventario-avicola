<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <title>@yield('title')</title>
</head>

<body>
    @yield('content')
    <footer>
        <nav>
            <a href="/home">home</a>
            <a href="/dashboard">dashboard</a>
            <a href="/search">search</a>
        </nav>
    </footer>
</body>

</html>