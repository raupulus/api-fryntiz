@extends('adminlte::page')

@section('title', $model::getModelTitles()['plural'])

@section('content_header')
    <h1>
        <i class="fas fa-file-pdf"></i>
        {{$model::getModelTitles()['plural']}}
    </h1>
@stop

@section('content')

    <div class="row" id="app">

        <div class="col-12">

            <v-table-component title="Listado de {{$model::getModelTitles()['plural']}}"
                               :editable="false"
                               :show-id="true"
                               :searchable="true"
                               :sortable="true"
                               csrf="{{csrf_token()}}"
                               :actions='{!! $model::getTableActionsInfoJson() !!}'
                               url="{{ route($model::getTableAjaxRoutes()['get']) }}" />

        </div>
    </div>

@stop
