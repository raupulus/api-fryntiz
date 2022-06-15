{{--

@extends('dashboard.layouts.app')


@section('content')
    <h1>
        Página principal
    </h1>
@endsection
--}}

{{-- https://adminlte.io/themes/v3/ --}}

@extends('adminlte::page')

@section('title', 'Dashboard')

@section('content_header')
    <h1>Dashboard</h1>
@stop

@section('content')
    <p>Welcome to this beautiful admin panel.</p>

    <div class="row">
        <div class="col-md-4">

            <i class="fa fa-edit"></i>
            Tag Add Edit
        </div>
    </div>

@stop

@section('css')
    <link rel="stylesheet" href="/css/admin_custom.css">
@stop

@section('js')

@stop
