@extends('layouts.invitado')
@section('title', 'Sesión expirada')
@section('content')
    {{-- 419: el token del formulario venció, normalmente porque la sesión expiró (30 minutos, RNF-04) --}}
    <x-pagina-error codigo="419" titulo="Tu sesión expiró"
        mensaje="Por seguridad, la sesión se cierra tras 30 minutos sin actividad. Vuelve a iniciar sesión para continuar."
        :enlace="route('login')" texto-enlace="Iniciar sesión" />
@endsection
