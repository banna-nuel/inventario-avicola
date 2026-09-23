<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <title>home</title>
</head>

<body class="bg-black">
    <header class="text-white">
        <section class="flex max-w-dvh gap-8 m-2">
            <h1 class="text-[#F5B122]">Inventario Avicola</h1>
            <p class="text-white">{{ auth()->user()->name }}</p>
            <form action="/logout" method="POST">
                @csrf
                <button class="text-[#F5B122]" type="submit">Cerrar sesión</button>
            </form>
        </section>
    </header>
    <main class="text-white">
        <p>Estado del inventario</p>
    </main>
    <footer class="flex gap-4 items-end  text-[#FFFFFF] ">
        <section>
            <a href="/home">home</a>
            <a href="/dashboard">dashboard</a>
            <a href="/search">search</a>
        </section>
    </footer>
</body>

</html>