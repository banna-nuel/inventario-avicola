@extends('layouts.app')
@section('title', 'home')
@section('content')
<header class="text-black">
    <section class="flex max-w-dvh gap-8 m-2">
        <h1 class="text-[#F5B122]">Inventario Avicola</h1>
        <p class="text-black">{{ auth()->user()->name }}</p>
        <form action="/logout" method="POST">
            @csrf
            <button class="text-[#F5B122]" type="submit">Cerrar sesión</button>
        </form>
    </section>
</header>
<main class="text-black">
    <p>Estado del inventario</p>
</main>
@endsection