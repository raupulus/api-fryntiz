@extends('adminlte::page')

@section('title', 'Añadir Curriculum Vitae')

@section('content_header')
    <h1>
        <i class="fas fa-globe"></i>
        Curriculum Vitae
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
            <form action="{{$cv && $cv->id ? route('dashboard.cv.update', $cv->id) : route('dashboard.cv.store')}}"
                  enctype="multipart/form-data"
                  method="POST">

                @csrf

                <input type="hidden" name="cv_id" value="{{$cv->id}}">

                <div class="row">
                    <div class="col-12">
                        <h2 style="display: inline-block;">
                            {{(isset($cv) && $cv && $cv->id) ? 'Editar ' .
                            $cv->title : 'Creando nuevo Curriculum Vitae'}}


                        </h2>

                        <div class="float-right">
                            <button type="submit" class="btn btn-success float-right">
                                <i class="fas fa-save"></i>
                                Guardar
                            </button>

                            {{--
                            @if (isset($cv) && $cv && $cv->id)

                                &nbsp;

                                <form action="{{route('dashboard.cv.destroy', $cv->id)}}"
                                      method="POST"
                                      style="display: inline-block;">

                                    @csrf

                                    <input type="hidden" name="id"
                                           value="{{$cv->id}}">

                                    <button type="submit" class="btn btn-danger">
                                        <i class="fas fa-trash"></i>
                                        Eliminar
                                    </button>

                                </form>
                            @endif
                            --}}
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
                                        <img src="{{ $cv->urlImageThumbnailSmall }}"
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

                                                @if ($cv->image)
                                                    {{$cv->image->original_name}}
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

                    <div class="col-md-6">
                        <div class="card card-primary">
                            <div class="card-header">
                                <h3 class="card-title">
                                    Datos principales
                                </h3>
                            </div>

                            <div class="card-body" style="min-height: 160px;">
                                <div class="form-group">
                                    <label for="exampleInputEmail1">
                                        Título
                                    </label>
                                    <input type="text"
                                           class="form-control"
                                           name="title"
                                           value="{{ old('title', $cv->title) }}"
                                           placeholder="Título para identificarlo">
                                </div>
                            </div>
                        </div>
                    </div>


                    <div class="col-md-6">
                        <div class="card card-warning">
                            <div class="card-header">
                                <h3 class="card-title">
                                    Opciones del
                                    Curriculum
                                </h3>
                            </div>

                            <div class="card-body" style="min-height: 160px;">

                                <div class="form-group">
                                    <div
                                        class="custom-control custom-switch">
                                        <input type="checkbox"
                                               class="custom-control-input"
                                               name="is_active"
                                               id="is_active"
                                               {{(old('is_active',  $cv->is_active) || !$cv->id) ? 'checked' : ''}} >
                                        <label class="custom-control-label"
                                               for="is_active">
                                            Habilitado
                                        </label>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <!-- Default checked -->
                                    <div
                                        class="custom-control custom-switch">
                                        <input type="checkbox"
                                               class="custom-control-input"
                                               name="is_downloadable"
                                               id="is_downloadable"
                                               {{(old('is_downloadable',  $cv->is_downloadable) || !$cv->id) ? 'checked' : ''}} >
                                        <label class="custom-control-label"
                                               for="is_downloadable">
                                            Permitir descarga
                                        </label>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <!-- Default checked -->
                                    <div
                                        class="custom-control custom-switch">
                                        <input type="checkbox"
                                               class="custom-control-input"
                                               name="is_default"
                                               id="is_default"
                                            {{(old('is_default',  $cv->is_default) || !$cv->id) ? 'checked' : ''}} >
                                        <label class="custom-control-label"
                                               for="is_default">
                                            Curriculum por defecto

                                            <small class="text-muted">
                                                <i>
                                                    (Solo puede haber uno por usuario)
                                                </i>
                                            </small>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Presentación --}}
                    <div class="col-12">
                        <div class="card card-info">
                            <div class="card-header">
                                <h3 class="card-title">
                                    Carta de Presentación
                                </h3>
                            </div>

                            <div class="card-body">
                                <div class="form-group">
                                    <label>Contenido de la presentación</label>
                                    <textarea class="form-control"
                                              rows="5"
                                              name="presentation"
                                              placeholder="Añade tu presentación...">{{ old('presentation', $cv->presentation) }}</textarea>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>

            </form>
        </div>


        @if (isset($cv) && $cv && $cv->id)
            <div class="col-12">
                <div class="card card-info">
                    <div class="card-header">
                        <h3 class="card-title">
                            Contenido
                        </h3>
                    </div>

                    <div class="card-body">
                        <div class="btn-group">
                            <a href="{{route('dashboard.cv.repository.index', $cv->id)}}"
                               class="btn btn-info">
                                <i class="fas fa-folder-open"></i>
                                Repositorios
                            </a>

                            &nbsp;

                            <a href="#"
                               class="btn btn-info">
                                <i class="fas fa-folder-open"></i>
                                Servicios
                            </a>

                            &nbsp;

                            <a href="#"
                               class="btn btn-info">
                                <i class="fas fa-folder-open"></i>
                                ...
                            </a>

                            &nbsp;

                            <a href="#"
                               class="btn btn-info">
                                <i class="fas fa-folder-open"></i>
                                ...
                            </a>

                            &nbsp;

                            <a href="#"
                               class="btn btn-info">
                                <i class="fas fa-folder-open"></i>
                                ...
                            </a>

                            &nbsp;

                            <a href="#"
                               class="btn btn-info">
                                <i class="fas fa-folder-open"></i>
                                ...
                            </a>

                            &nbsp;

                            <a href="#"
                               class="btn btn-info">
                                <i class="fas fa-folder-open"></i>
                                ...
                            </a>

                            &nbsp;

                            <a href="#"
                               class="btn btn-info">
                                <i class="fas fa-folder-open"></i>
                                ...
                            </a>
                        </div>
                    </div>

                </div>
            </div>

        @endif
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

            if (avatarInput) {
                avatarInput.onchange = () => {
                    const reader = new FileReader();

                    reader.onload = () => {
                        imageView.src = reader.result;
                    }

                    if (avatarInput.files && avatarInput.files[0]) {
                        reader.readAsDataURL(avatarInput.files[0]);

                        if (imageLabel) {
                            imageLabel.textContent = avatarInput.files[0].name;
                        }
                    }
                };
            }

        });
    </script>
@stop
