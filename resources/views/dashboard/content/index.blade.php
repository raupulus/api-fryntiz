@extends('adminlte::page')

@section('title', $model::getModelTitles()['plural'])

@section('content_header')
    <h1>
        <i class="fas fa-file-pdf"></i>
        {{$model::getModelTitles()['plural']}}
    </h1>
@stop

@section('content')

    {{-- TODO: Crear un listado por cada tipo de "plataforma" y crear selector para buscar entre ellas --}}


    <h1>
        TERMINAR PLATAFORMAS, AÑADIR SELECTOR POR PLATAFORMA y CARGAR
        SOLO EL COMPONENTE PARA ESA PLATAFORMA
    </h1>




    <div class="row" id="app">

        <div class="col-12">
            <h2>
                <a href="{{ route($model::getCrudRoutes()['create']) }}"
                   class="btn btn-primary float-right">
                    <i class="fas fa-plus"></i>
                    Crear
                </a>
            </h2>
        </div>

        <div class="col-12">

            <v-table-component title="Listado de {{$model::getModelTitles()['plural']}}"
                               :editable="true"
                               :show-id="false"
                               :searchable="true"
                               :sortable="true"
                               csrf="{{csrf_token()}}"
                               url-edit-hot='{{ route($model::getTableAjaxRoutes()['actions']) }}'
                               :actions='{!! $model::getTableActionsInfoJson() !!}'
                               url="{{ route($model::getTableAjaxRoutes()['get']) }}" />

        </div>
    </div>

@stop

