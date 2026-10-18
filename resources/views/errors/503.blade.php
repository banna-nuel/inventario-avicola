{{-- 503: la app está en mantenimiento (php artisan down) --}}
@extends('layouts.invitado')
@section('title', 'En mantenimiento')
@section('content')
    <x-pagina-error codigo="503" titulo="Estamos en mantenimiento"
        mensaje="La aplicación vuelve en unos minutos. Gracias por la paciencia." />
@endsection
