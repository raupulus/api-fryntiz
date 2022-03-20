@extends('adminlte::page')

@section('title', 'Lista.....')

@section('content_header')
    <h1>Dashboard</h1>
@stop

@section('content')
    <p>Listado</p>

    <div class="row">
        <div class="col-md-4">
            a
          <i class="fa fa-edit"></i>

        </div>
    </div>

@stop

@section('css')
@stop

@section('js')
    <script src="{{ mix('dashboard/js/dashboard.js') }}"></script>
@stop
