{{-- Pantalla temporal de los módulos que llegan en una fase posterior (ProximamenteController) --}}
@extends('layouts.app')
@section('title', $modulo)
@section('volver', route('home'))
@section('content')
    <x-encabezado :titulo="$modulo" subtitulo="Próximamente" />

    <x-estado-vacio titulo="Este módulo está en construcción"
        mensaje="Llega en una próxima fase del proyecto. Mientras tanto, puedes volver al inicio.">
        <x-boton :href="route('home')" variante="secundario">Volver al inicio</x-boton>
    </x-estado-vacio>
@endsection
