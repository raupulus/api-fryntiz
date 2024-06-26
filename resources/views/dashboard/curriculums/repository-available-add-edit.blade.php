@extends('adminlte::page')

@section('title', 'Repositorios disponibles')

@section('content_header')
    <h1>
        <i class="fas fa-globe"></i>
        Curriculum - Repositorios disponibles
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
                action="{{$repositoryType && $repositoryType->id ? route
                ('dashboard.cv.repository_available_type.update',
                $repositoryType->id) : route('dashboard.cv.repository_available_type.store')}}"
                enctype="multipart/form-data"
                method="POST">

                @csrf

                <input type="hidden" name="repositoryType_id"
                       value="{{$repositoryType->id}}">

                <div class="row">
                    <div class="col-12">
                        <h2 style="display: inline-block;">
                            {{(isset($repositoryType) && $repositoryType && $repositoryType->id) ? 'Editar ' .
                            $repositoryType->title : 'Creando nuevo Tipo de repositorio'}}


                        </h2>

                        <div class="float-right">
                            <button type="submit"
                                    class="btn btn-success float-right">
                                <i class="fas fa-save"></i>
                                Guardar
                            </button>
                        </div>
                    </div>

                    {{-- Imagen --}}
                    <div class="col-12">
                        <div class="card card-info">
                            <div class="card-header">
                                <h3 class="card-title">
                                    Imagen Adjunta
                                </h3>
                            </div>

                            <div class="card-body">
                                <div class="form-group">
                                    <label for="image">
                                        Imagen
                                    </label>

                                    <div class="input-group">
                                        <img
                                            src="{{ $repositoryType->urlThumbnail ('small') }}"
                                            alt="Imagen para el repositorio"
                                            id="repository-image-preview"
                                            style="width: 80px; margin-right: 10px;"/>

                                        <div class="custom-file">

                                            <input type="file"
                                                   name="image"
                                                   id="repository-image-input"
                                                   accept="image/*"
                                                   class="form-control-file">

                                            <label class="custom-file-label"
                                                   id="repository-image-label"
                                                   for="repository-image-input">

                                                @if ($repositoryType->image)
                                                    {{$repositoryType->image->original_name}}
                                                @else
                                                    Añadir archivo
                                                @endif
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>

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
                                    <label for="title">
                                        Título
                                    </label>
                                    <input type="text"
                                           class="form-control"
                                           name="title"
                                           value="{{ old('title', $repositoryType->title) }}"
                                           placeholder="Título para identificarlo">
                                </div>

                                <div class="form-group">
                                    <label for="name">
                                        Nombre
                                    </label>
                                    <input type="text"
                                           class="form-control"
                                           name="name"
                                           value="{{ old('name', $repositoryType->name) }}"
                                           placeholder="Nombre amigable">
                                </div>

                                <div class="form-group">
                                    <label for="slug">
                                        Slug
                                    </label>
                                    <input type="text"
                                           class="form-control"
                                           name="slug"
                                           value="{{ \Str::slug(old('slug', $repositoryType->slug)) }}"
                                           placeholder="Slug, abreviatura para rutas">
                                </div>

                                <div class="form-group">
                                    <label for="url">
                                        Url
                                    </label>
                                    <input type="text"
                                           class="form-control"
                                           name="url"
                                           value="{{ old('url', $repositoryType->url) }}"
                                           placeholder="Enlace al sitio oficial">
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
        window.document.addEventListener('click', () => {
            /********** Cambiar Imagen al subirla **********/
            const avatarInput = document.getElementById('repository-image-input');
            const imageView = document.getElementById('repository-image-preview');
            const imageLabel = document.getElementById('repository-image-label');

            if(avatarInput) {
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
