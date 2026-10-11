{{--
    Respaldo para los errores 4xx sin vista propia (por ejemplo 405: la dirección existe pero
    no admite esa acción, como abrir /productos/5 cuando solo se puede editar).
    Laravel la usa sola cuando no encuentra errors/{código}.blade.php.
--}}
@extends('layouts.invitado')
@section('title', 'No disponible')
@section('content')
    <x-pagina-error :codigo="$exception->getStatusCode()" titulo="No pudimos abrir esta página"
        mensaje="La dirección no existe o no admite esta acción. Vuelve al inicio y entra desde el menú." />
@endsection
