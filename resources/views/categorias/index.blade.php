@extends('layouts.app')
@section('title', 'categorias')
@section('content')

<h1>categorias</h1>
<nav>
    <a href="/categorias/create">Crear</a> <br>
    <ol>
    @foreach ($categorias as $categoria)
        <li>{{ $categoria->nombre }}</li>
    @endforeach
    </ol>
</nav>
@if(session('success'))
{{ session('success') }}
@endif
@endsection