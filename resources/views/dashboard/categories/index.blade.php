@extends('adminlte::page')

@section('title', 'Categorías')

@section('content_header')
    <h1>
        <i class="fas fa-file-pdf"></i>
        Categorías
    </h1>
@stop

@section('content')

    <div class="row" id="app">

        <div class="col-12">
            <h2>
                <a href="{{ route('dashboard.category.create') }}"
                   class="btn btn-primary float-right">
                    <i class="fas fa-plus"></i>
                    Nueva
                </a>
            </h2>
        </div>

        <div class="col-12">

            <v-table-component title="Listado de Categorías"
                               :editable="true"
                               :show-id="false"
                               :searchable="true"
                               :sortable="true"
                               csrf="{{csrf_token()}}"
                               url-edit-hot='{{route('dashboard.category.ajax.table.actions')}}'
                               :actions='{!!\App\Models\Category::getTableActionsInfoJson() !!}'
                               url="{{route('dashboard.category.ajax.table.get')}}" />

        </div>
    </div>

@stop

@section('js')
    <script src="{{ mix('dashboard/js/dashboard.js') }}"></script>
@stop
