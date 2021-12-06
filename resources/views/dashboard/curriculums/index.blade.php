
@extends('adminlte::page')

@section('title', 'Curriculums')

@section('content_header')
    <h1>
        <i class="fas fa-file-pdf"></i>
        Curriculums
    </h1>
@stop

@section('content')

    <div class="row"  id="app">
        <div class="col-12">
            <h2>
                Listado de Curriculums
                <a href="{{ route('dashboard.cv.create') }}" class="btn  btn-primary float-right">
                    <i class="fas fa-plus"></i>
                    Nuevo
                </a>
            </h2>
        </div>

        <div class="col-12">
            {{--
            <v-table-component title="titulo de la tabla"
                               url="{{route('language.ajax.get.languages')}}" />
                               --}}
        </div>
    </div>

@stop

@section('js')
    <script src="{{ mix('dashboard/js/dashboard.js') }}"></script>
@stop
