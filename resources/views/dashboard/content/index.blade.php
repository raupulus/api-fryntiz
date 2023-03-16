@extends('adminlte::page')

@section('title', $model::getModelTitles()['plural'])

@section('content_header')
    <h1>
        <i class="fas fa-file-pdf"></i>

        @if($platform)
            {{$model::getModelTitles()['plural']}} de {{$platform->title}}
        @else
            Todos los {{$model::getModelTitles()['plural']}}
        @endif
    </h1>
@stop

@section('content')

    <div class="row" id="app">

        @if ($platform)
            <div class="col-12">
                <h2>
                    <a href="{{ route($model::getCrudRoutes()['create'], $platform->id) }}"
                       class="btn btn-primary float-right">
                        <i class="fas fa-plus"></i>
                        Crear
                    </a>
                </h2>
            </div>
        @endif

        <div class="col-12">

            <v-table-component
                    title="Listado de {{$model::getModelTitles()['plural']}}"
                    :editable="true"
                    :show-id="false"
                    :searchable="true"
                    :sortable="true"
                    csrf="{{csrf_token()}}"
                    url-edit-hot='{{ route($model::getTableAjaxRoutes()['actions']) }}'
                    :actions='{!! $model::getTableActionsInfoJson() !!}'
                    url="{{ route($model::getTableAjaxRoutes()['get']) }}"
                    :conditions="{{$platform ? '[{filter: "where", column: "platform_id", value: ' . $platform->id . '}]' : null}}"
            />

        </div>
    </div>

@stop

