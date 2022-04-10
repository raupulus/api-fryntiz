@extends('adminlte::page')

@section('title', 'Hardware')

@section('content_header')
    <h1>
        <i class="fas fa-file-pdf"></i>
        Usuarios
    </h1>
@stop

@section('content')

    <div class="row" id="app">
        <div class="col-12">
            <h2>
                Listado de Usuarios
                <a href="{{ route('dashboard.hardware.device.create') }}"
                   class="btn  btn-primary float-right">
                    <i class="fas fa-plus"></i>
                    Nuevo
                </a>
            </h2>
        </div>


    </div>

@stop

@section('js')
    <script src="{{ mix('dashboard/js/dashboard.js') }}"></script>
@stop
