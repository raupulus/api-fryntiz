@extends('adminlte::page')

@section('title', 'Etiquetas')

@section('content_header')
    <h1>
        <i class="fas fa-file-pdf"></i>
        Plataformas
    </h1>
@stop

@section('content')

    <div class="row" id="app">

        <div class="col-12">
            <h2>
                <a href="{{ route($model::CRUD_ROUTES['create']) }}"
                   class="btn btn-primary float-right">
                    <i class="fas fa-plus"></i>
                    Nueva
                </a>
            </h2>
        </div>

        <div class="col-12">

            <v-table-component title="Listado de Plataformas"
                               :editable="true"
                               :show-id="false"
                               :searchable="true"
                               :sortable="true"
                               csrf="{{csrf_token()}}"
                               url-edit-hot='{{ route($model::TABLE_AJAX_ROUTES['actions']) }}'
                               :actions='{!! $model::getTableActionsInfoJson() !!}'
                               url="{{ route($model::TABLE_AJAX_ROUTES['get']) }}" />

        </div>
    </div>

@stop

@section('js')
    <script src="{{ mix('dashboard/js/dashboard.js') }}"></script>
@stop
