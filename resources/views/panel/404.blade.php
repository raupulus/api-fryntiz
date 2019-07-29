@extends('panel.layouts.app')

@section('title', 'Panel Admin - Dashboard')

@section('content')
    @include('panel.layouts.breadcrumbs')
    <h1 class="display-1">404</h1>
    <p class="lead">Página no encontrada. Puedes
        <a href="javascript:history.back()">volver</a>
        a la página anterior o ir al
        <a href="{{route('panel-index')}}">dashboard principal</a>.</p>
@endsection


