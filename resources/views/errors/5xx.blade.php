{{--
    Errores del servidor (500, 503...) en español. Laravel la usa sola cuando no encuentra
    errors/{código}.blade.php. Con APP_DEBUG=false es lo único que ve la persona: sin detalles
    internos. Usa el layout de invitado para no depender de la sesión ni de la base de datos.
--}}
@extends('layouts.invitado')
@section('title', 'Algo salió mal')
@section('content')
    <x-pagina-error :codigo="$exception->getStatusCode()" titulo="Algo salió mal"
        mensaje="Tuvimos un problema al procesar la solicitud. Intenta de nuevo en un momento; si se repite, avisa a un administrador." />
@endsection
