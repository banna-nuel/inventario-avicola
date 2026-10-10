@extends('layouts.invitado')
@section('title', 'Página no encontrada')
@section('content')
    <x-pagina-error codigo="404" titulo="No encontramos esta página"
        mensaje="Puede que el enlace esté mal escrito o que el contenido ya no exista." />
@endsection
