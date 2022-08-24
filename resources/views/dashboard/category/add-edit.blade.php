@extends('adminlte::page')

@section('title', 'Añadir ' . $model::getModelTitles()['singular'])

@section('content_header')
    <h1>
        <i class="fas fa-globe"></i>
        {{\Illuminate\Support\Str::ucfirst($model::getModelTitles()['singular'])}}
    </h1>
@stop

@section('content')

    <div class="row" id="app">
        <div class="col-12">
            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>

        <div class="col-12">
            <form
                    action="{{$model && $model->id ? route($model::getCrudRoutes()['update'], $model->id) : route($model::getCrudRoutes()['store'])}}"
                    enctype="multipart/form-data"
                    method="POST">

                @csrf

                <input type="hidden" name="id" value="{{$model->id}}">

                <div class="row">
                    <div class="col-12">
                        <h2 style="display: inline-block;">
                            {{(isset($model) && $model && $model->id) ? 'Editar ' . $model::getModelTitles()['singular'] : 'Creando ' . $model::getModelTitles()['singular']}}
                        </h2>

                        <div class="float-right">
                            <button type="submit"
                                    class="btn btn-success float-right">
                                <i class="fas fa-save"></i>
                                Guardar
                            </button>
                        </div>
                    </div>

                    <div class="col-md-12">
                        <div class="card card-primary">
                            <div class="card-header">
                                <h3 class="card-title">
                                    Datos principales
                                </h3>
                            </div>

                            <div class="card-body" style="min-height: 160px;">
                                <div class="form-group">
                                    <label for="name">
                                        Nombre
                                    </label>

                                    <input type="text"
                                           class="form-control"
                                           name="name"
                                           value="{{ old('name', $model->name) }}"
                                           placeholder="Nombre de la categoría">
                                </div>

                                <div class="form-group">
                                    <label for="slug">
                                        Slug
                                    </label>

                                    <input type="text"
                                           class="form-control"
                                           name="slug"
                                           value="{{ old('slug', $model->slug) }}"
                                           placeholder="Slug-de-la-categoría">
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Descripción --}}
                    <div class="col-12">
                        <div class="card card-info">
                            <div class="card-header">
                                <h3 class="card-title">
                                    Descripción
                                </h3>
                            </div>

                            <div class="card-body">
                                <div class="form-group">
                                    <label></label>
                                    <textarea class="form-control"
                                              rows="5"
                                              name="description"
                                              placeholder="Descripción de la categoría...">{{ old('description', $model->description) }}</textarea>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>

            </form>
        </div>
    </div>
@stop

@section('js')
    <script src="{{ mix('dashboard/js/dashboard.js') }}"></script>
@stop
