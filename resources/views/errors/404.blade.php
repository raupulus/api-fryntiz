@extends('layouts.app')

@section('title', 'Error 404')

@section('content')
    @include('layouts.breadcrumbs')
    <div class="row">
        <div class="col-12 mb-5">
            <h1 class="display-1">404</h1>
            <p class="lead">Página no encontrada. Puedes
                <a href="javascript:history.back()">volver</a>
                a la página anterior o ir a la
                <a href="{{route('index')}}">página principal</a>.</p>
        </div>
    </div>
@endsection


