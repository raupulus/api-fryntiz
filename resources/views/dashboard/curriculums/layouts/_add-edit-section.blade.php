@extends('adminlte::page')

@section('title', 'Curriculum ' . $modelName::$plural)

@section('content_header')
    <h1>
        <i class="fas fa-globe"></i>
        {{$modelName::$plural}} asociados al curriculum
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

                <div class="col-12">

                    <h2>
                        <a style="font-size: 2rem;"
                           href="{{route('dashboard.cv.edit', $cv->id)}}">
                            <i class="fa fa-arrow-left"></i>

                        </a>

                        Añade un nuevo {{$modelName::$singular}}
                    </h2>
                </div>

                {{-- Repositorios --}}
                <div class="col-12">
                    <div class="card card-info">
                        <div class="card-header">
                            <h3 class="card-title">
                                {{$modelName::$plural}}
                            </h3>
                        </div>

                        <div class="card-body">
                            <table class="table table-bordered table-dark">
                                <thead>
                                <tr>
                                    <th class="text-center">Imagen</th>
                                    <th class="text-center">Nombre</th>
                                    <th class="text-center">URL</th>
                                    <th class="text-center">Descripción</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                                </thead>

                                <tbody>

                                @foreach($models as $ele)
                                    <tr>
                                        <td class="text-center">
                                            <img
                                                src="{{$ele->urlThumbnail('micro')}}"
                                                alt="{{$ele->name}}"
                                                style="width: 40px;">

                                        </td>

                                        <td class="align-middle">
                                            {{$ele->name}}
                                        </td>

                                        <td class="align-middle">
                                            <a href="{{$ele->url}}"
                                               target="_blank">
                                                {{$ele->url}}
                                            </a>
                                        </td>
                                        <td class="align-middle">{{$ele->description}}</td>
                                        <td class="align-middle text-center">
                                            <a href="{{route($ele::$routesDashboard['edit'], $ele->id)}}"
                                               class="btn btn-warning btn-sm">
                                                <i class="fa fa-edit"></i>
                                            </a>

                                            &nbsp;

                                            <form method="POST"
                                                  action="{{route($ele::$routesDashboard['destroy'], $ele->id)}}"
                                                  class="d-inline-block">

                                                @csrf

                                                <input type="hidden"
                                                       name="id"
                                                       value="{{$ele->id}}" />

                                                <button type="submit"
                                                        class="btn btn-danger btn-sm">
                                                    <i class="fa fa-trash"></i>
                                                </button>
                                            </form>

                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>

                            <hr/>


                            <div class="card card-secondary">
                                <div class="card-header">
                                    <h4 class="card-title">
                                        Añadir nuevo {{$modelName::$singular}}
                                    </h4>
                                </div>

                                <div class="card-body">
                                    <form action="{{$action}}"
                                          method="POST"
                                          enctype="multipart/form-data">

                                        @csrf

                                        @if ($model && $model->id)
                                            <input type="hidden" name="model_id"
                                                   value="{{$model->id}}">
                                        @endif


                                    <div class="form-group">
                                        <label for="image">
                                            Imagen
                                        </label>

                                        <div class="input-group">
                                            <img
                                                src="{{
                                                $model ?
                                                $model->urlThumbnail('small') :
                                                \App\Models\File::urlDefaultImage('small') }}"
                                                alt="Curriculum Image"
                                                id="cv-image-preview"
                                                style="width: 80px; margin-right: 10px;"/>

                                            <div class="custom-file">

                                                <input type="file"
                                                       name="image"
                                                       id="cv-image-input"
                                                       accept="image/*"
                                                       class="form-control-file">

                                                <label class="custom-file-label"
                                                       id="cv-image-label"
                                                       for="cv-image-input">

                                                    Añadir archivo
                                                </label>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label>
                                            URL
                                        </label>

                                        <div class="input-group mb-3">

                                            <div class="input-group-prepend">
                                                <span class="input-group-text">
                                                    <i class="fas fa-link"></i>
                                                </span>
                                            </div>

                                            <input type="url"
                                                   value="{{old('url',
                                                   $model ?
                                                   $model->url : '') }}"
                                                   name="url"
                                                   class="form-control"
                                                   placeholder="https://fryntiz.es">
                                        </div>
                                    </div>


                                    <div class="form-group">
                                        <label>
                                            Nombre
                                        </label>

                                        <div class="input-group mb-3">

                                            <div class="input-group-prepend">
                                                <span class="input-group-text">
                                                    <i class="fas fa-heading"></i>
                                                </span>
                                            </div>

                                            <input type="text"
                                                   value="{{old('name',
                                                   $model ?
                                                   $model->name : '') }}"
                                                   name="name"
                                                   class="form-control"
                                                   placeholder="Mumble">
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label>
                                            Descripción
                                        </label>

                                        <div class="input-group mb-3">

                                            <textarea name="description"
                                                      class="form-control"
                                                      rows="5"
                                                      placeholder="Descripción del servicio">{{old('description',
                                                   $model ?
                                                   $model->description : '') }}</textarea>
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        @if ($model && $model->id)
                                            <button type="submit"
                                                    class="btn btn-primary">
                                                <i class="fas fa-save"></i>
                                                Guardar
                                            </button>
                                        @else
                                            <button type="submit"
                                                    class="btn btn-primary">
                                                <i class="fas fa-plus"></i>
                                                Añadir
                                            </button>
                                        @endif
                                    </div>
                                    </form>
                                </div>
                            </div>


                        </div>
                    </div>

                </div>

        </div>
    </div>
@stop

@section('js')
    <script src="{{ mix('dashboard/js/dashboard.js') }}"></script>


    <script>
        window.document.addEventListener('click', () => {
            /********** Cambiar Imagen al subirla **********/
            const avatarInput = document.getElementById('cv-image-input');
            const imageView = document.getElementById('cv-image-preview');
            const imageLabel = document.getElementById('cv-image-label');

            if(avatarInput && imageView && imageLabel) {
                avatarInput.onchange = () => {
                    const reader = new FileReader();

                    reader.onload = () => {
                        imageView.src = reader.result;
                    }

                    if(avatarInput.files && avatarInput.files[0]) {
                        reader.readAsDataURL(avatarInput.files[0]);

                        if(imageLabel) {
                            imageLabel.textContent = avatarInput.files[0].name;
                        }
                    }
                };
            }

        });
    </script>
@stop
