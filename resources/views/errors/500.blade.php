@extends('layouts.app')

@section('title', 'Error 500')

@section('content')
    @include('layouts.breadcrumbs')

    <div class="row">
        <div class="col-12">
            <h1 class="display-1">500</h1>
            <p class="lead">Error de Servidor, si se repite contacte con el
                administrador. Puedes
                <a href="javascript:history.back()">volver</a>
                a la página anterior o ir a la
                <a href="{{route('index')}}">página principal</a>.</p>
        </div>

        <div>
            @yield('code')
        </div>

        <div>
            @yield('message')
        </div>
    </div>


@endsection


