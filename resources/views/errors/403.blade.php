@extends('layouts.invitado')
@section('title', 'Sin permiso')
@section('content')
    <x-pagina-error codigo="403" titulo="No tienes permiso"
        mensaje="Esta sección es solo para administradores. Si crees que deberías tener acceso, habla con un administrador." />
@endsection
