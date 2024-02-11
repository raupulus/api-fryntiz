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


                    {{-- Datos principales --}}
                    <div class="col-md-6">
                        <div class="col-md-12">
                            <div class="card card-success">
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
                                               id="name"
                                               name="name"
                                               data-slug_provider="title"
                                               value="{{ old('name', $model->name) }}"
                                               placeholder="Nombre de la tecnología">
                                    </div>

                                    <div class="form-group">
                                        <label for="slug">
                                            Slug
                                        </label>

                                        <input type="text"
                                               id="slug"
                                               class="form-control"
                                               name="slug"
                                               data-sluggable="title"
                                               value="{{ old('slug', $model->slug) }}"
                                               placeholder="slug-de-la-tecnologia">
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Descripción --}}
                        <div class="col-md-12">
                            <div class="card card-info">
                                <div class="card-body">
                                    <div class="form-group">
                                        <label for="description">
                                            Descripción
                                        </label>
                                        <textarea class="form-control"
                                                  rows="5"
                                                  id="description"
                                                  name="description"
                                                  placeholder="Descripción de la plataforma...">{{ old('description', $model->description) }}</textarea>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        {{-- Imagen --}}
                        <div class="col-md-12">
                            <div class="card card-info">
                                <div class="card-body">

                                    {{-- Selector Cropper de imágenes --}}
                                    <div class="form-group">
                                        <div class="input-group">
                                            <div class="col-12">
                                                <div
                                                    style="height: 100%; max-height: 220px; margin: auto; overflow: hidden; box-sizing: border-box;">
                                                    <v-image-cropper
                                                        default-image="{{ $model->urlImage }}"
                                                        name="image"
                                                        :aspect-ratios-restriction="[1,1]"
                                                    ></v-image-cropper>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </div>

                        <div class="col-md-12">
                            <div class="card card-info">
                                <div class="card-body">
                                    <div class="form-group">
                                        <label for="color">
                                            Color
                                        </label>

                                        <input class="form-control"
                                               id="color"
                                               name="color"
                                               style="height: 8rem;"
                                               type="color" value="{{old('color', $model->color)}}">
                                    </div>
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
    <script>
        // Set title form to slug
        let title = document.querySelector('input[data-slug_provider="title"]');
        let slug = document.querySelector('input[data-sluggable="title"]');

        //console.log('title:', title, slug);

        if (title && slug) {
            title.addEventListener('focusout', () => {
                //console.log('event focusout');
                // TODO check if slug is empty

                if (slug.value == '') {
                    slug.value = slugify(title.value);
                }

            });
        }
    </script>
@endsection
