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

                                {{-- Imagen --}}
                                <div class="form-group">
                                    {{-- Selector Cropper de imágenes --}}
                                    <div class="col-12">
                                        <div
                                            style="height: 140px; width: 140px; margin: auto; overflow: hidden; box-sizing: border-box;">
                                            <v-image-cropper
                                                default-image="{{ $model->urlImage }}"
                                                name="image"
                                                :aspect-ratios-restriction="[1,1]"
                                            ></v-image-cropper>
                                        </div>
                                    </div>
                                </div>


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


        {{-- Subcategorías --}}
        @if(isset($model) && $model && $model->id)

            <div class="col-12">
                <h2>
                    <i class="fas fa-globe"></i>
                    SubCategorías
                </h2>
            </div>

            @foreach($model->subcategories as $subcategory)
                <div class="col-12">
                    <form
                        action="{{route($model::getCrudRoutes()['update'], $subcategory->id)}}"
                        enctype="multipart/form-data"
                        method="POST">

                        @csrf

                        <input type="hidden" name="id" value="{{$subcategory->id}}">
                        <input type="hidden" name="parent_id" value="{{$model->id}}">

                        <div class="row">


                            <div class="col-12">
                                <div class="card card-warning">
                                    <div class="card-header">

                                        <h3 style="display: inline-block;">
                                            {{$subcategory->name}}
                                        </h3>

                                        <div class="float-right">
                                            <button type="submit"
                                                    class="btn btn-success float-right">
                                                <i class="fas fa-save"></i>
                                                Guardar
                                            </button>
                                        </div>
                                    </div>

                                    <div class="card-body" style="min-height: 160px;">
                                        <div class="row">


                                            {{-- Imagen --}}
                                            <div class="col-12">
                                                <div class="card card-primary">
                                                    <div class="card-header">
                                                        <h3 class="card-title">
                                                            Imagen
                                                        </h3>
                                                    </div>

                                                    <div class="card-body" style="min-height: 160px;">
                                                        <div class="form-group">
                                                            {{-- Selector Cropper de imágenes --}}
                                                            <div class="col-12">
                                                                <div
                                                                    style="height: 140px; width: 140px; margin: auto; overflow: hidden; box-sizing: border-box;">
                                                                    <v-image-cropper
                                                                        default-image="{{ $subcategory->urlImage }}"
                                                                        name="image"
                                                                        :aspect-ratios-restriction="[1,1]"
                                                                    ></v-image-cropper>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>


                                            <div class="col-12 col-md-6">
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
                                                                   value="{{ $subcategory->name }}"
                                                                   placeholder="Nombre de la categoría">
                                                        </div>

                                                        <div class="form-group">
                                                            <label for="slug">
                                                                Slug
                                                            </label>

                                                            <input type="text"
                                                                   class="form-control"
                                                                   name="slug"
                                                                   value="{{ $subcategory->slug }}"
                                                                   placeholder="Slug-de-la-subcategoría">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            {{-- Descripción --}}
                                            <div class="col-12 col-md-6">
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
                                                                      placeholder="Descripción de la subcategoría...">{{ $subcategory->description }}</textarea>
                                                        </div>
                                                    </div>

                                                </div>
                                            </div>


                                        </div>
                                    </div>
                                </div>
                            </div>


                        </div>
                    </form>
                </div>
            @endforeach



            {{-- Crear Nueva Subcategoría --}}
            <div class="col-12">
                <form
                    action="{{route($model::getCrudRoutes()['store'])}}"
                    enctype="multipart/form-data"
                    method="POST">

                    @csrf

                    <input type="hidden" name="parent_id" value="{{$model->id}}">

                    <div class="row">


                        <div class="col-12">
                            <div class="card card-danger">
                                <div class="card-header">

                                    <h3 style="display: inline-block;">
                                        Crear Nueva Categoría
                                    </h3>

                                    <div class="float-right">
                                        <button type="submit"
                                                class="btn btn-success float-right">
                                            <i class="fas fa-save"></i>
                                            Guardar
                                        </button>
                                    </div>
                                </div>

                                <div class="card-body" style="min-height: 160px;">
                                    <div class="row">

                                        {{-- Imagen --}}
                                        <div class="col-12">
                                            <div class="card card-primary">
                                                <div class="card-header">
                                                    <h3 class="card-title">
                                                        Imagen
                                                    </h3>
                                                </div>

                                                <div class="card-body" style="min-height: 160px;">
                                                    <div class="form-group">
                                                        {{-- Selector Cropper de imágenes --}}
                                                        <div class="col-12">
                                                            <div
                                                                style="height: 140px; width: 140px; margin: auto; overflow: hidden; box-sizing: border-box;">
                                                                <v-image-cropper
                                                                    default-image="{{ asset('images/default/large.jpg') }}"
                                                                    name="image"
                                                                    :aspect-ratios-restriction="[1,1]"
                                                                ></v-image-cropper>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>


                                        <div class="col-12 col-md-6">
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
                                                               value=""
                                                               placeholder="Nombre de la categoría">
                                                    </div>

                                                    <div class="form-group">
                                                        <label for="slug">
                                                            Slug
                                                        </label>

                                                        <input type="text"
                                                               class="form-control"
                                                               name="slug"
                                                               value=""
                                                               placeholder="Slug-de-la-subcategoría">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        {{-- Descripción --}}
                                        <div class="col-12 col-md-6">
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
                                                                  placeholder="Descripción de la subcategoría..."></textarea>
                                                    </div>
                                                </div>

                                            </div>
                                        </div>


                                    </div>
                                </div>
                            </div>
                        </div>


                    </div>
                </form>
            </div>
        @endif

    </div>
@stop
