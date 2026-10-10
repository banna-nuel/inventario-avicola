@extends('layouts.app')
@section('title', 'categorias')
@section('content')
<form action="/categorias" method="POST">
    @csrf
    <input type="text" name="nombre" value="{{ old('nombre') }}">
    @error('nombre')
    <p class="text-black"> {{ $message }} </p>
    @enderror
    <button type="submit">Enviar</button>
</form>
@endsection