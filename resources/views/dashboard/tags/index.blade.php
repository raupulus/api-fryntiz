@extends('adminlte::page')

@section('title', 'Etiquetas')

@section('content_header')
    <h1>
        <i class="fas fa-file-pdf"></i>
        Etiquetas
    </h1>
@stop

@section('content')

    <div class="row" id="app">

        {{--
        <div class="col-12">
            <h2>
                Listado de Etiquetas
                <a href="{{ route('dashboard.tag.create') }}"
                   class="btn btn-primary float-right">
                    <i class="fas fa-plus"></i>
                    Nueva
                </a>
            </h2>
        </div>
        --}}

        <div class="col-12">

            <v-table-component title="Listado de Etiquetas"
                               :editable="true"
                               :show-id="false"
                               :searchable="true"
                               :sortable="true"
                               csrf="{{csrf_token()}}"
                               :actions='{!!\App\Models\Tag::getTableActionsInfo()->toJson(JSON_UNESCAPED_UNICODE)!!}'
                               url="{{route('dashboard.tag.ajax.table.get')}}" />
        </div>
    </div>

@stop

@section('js')
    <script src="{{ mix('dashboard/js/dashboard.js') }}"></script>
@stop
