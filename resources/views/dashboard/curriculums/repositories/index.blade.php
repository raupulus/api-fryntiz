@extends('adminlte::page')

@section('title', 'Curriculum Repositorios')

@section('content_header')
        <h1>
        <i class="fas fa-globe"></i>
        Repositorios asociados al curriculum
    </h1>
@stop

@section('content')

    <div class="row" id="app">

        <div class="col-12">
            <form action="{{route('dashboard.cv.repository.store')}}"
                  method="post"
                  enctype="multipart/form-data">

                @csrf

                <div class="col-12">

                    <h2>
                        <a style="font-size: 2rem;"
                           href="{{route('dashboard.cv.edit', $cv->id)}}" >
                            <i class="fa fa-arrow-left"></i>

                        </a>

                        Añade un nuevo Repositorio
                    </h2>
                </div>

                {{-- Repositorios --}}
                <div class="col-12">
                    <div class="card card-info">
                        <div class="card-header">
                            <h3 class="card-title">Repositorios</h3>
                        </div>

                        <div class="card-body">
                            <table class="table table-bordered table-dark">
                                <tr>
                                    <td>data</td>
                                    <td>data</td>
                                    <td>data</td>
                                </tr>
                            </table>

                            <hr />


                            <div class="card card-secondary">
                                <div class="card-header">
                                    <h4 class="card-title">
                                        Añadir nuevo repositorio
                                    </h4>
                                </div>

                                <div class="card-body">
                                    <div class="form-group">
                                        <label for="image">
                                            Imagen
                                        </label>

                                        <div class="input-group">
                                            <img src="{{ \App\Models\File::urlDefaultImage('small') }}"
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
                                        <label>Tipo de Repositorio</label>
                                        <select class="form-control">
                                            @foreach($availableRepositories as $type)
                                                <option value="{{ $type->id }}">
                                                    {{ $type->title }}
                                                </option>
                                            @endforeach
                                        </select>
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

                                            <input type="text"
                                                   value=""
                                                   class="form-control"
                                                   placeholder="https://gitlab.com/username/...">
                                        </div>
                                    </div>


                                    <div class="form-group">
                                        <label>
                                            Título amigable
                                        </label>

                                        <div class="input-group mb-3">

                                            <div class="input-group-prepend">
                                        <span class="input-group-text">
                                            <i class="fas fa-heading"></i>
                                        </span>
                                            </div>

                                            <input type="text"
                                                   value=""
                                                   class="form-control"
                                                   placeholder="Proyecto con Arduino">
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label>
                                            Nombre exacto del repositorio
                                        </label>

                                        <div class="input-group mb-3">

                                            <div class="input-group-prepend">
                                        <span class="input-group-text">
                                            <i class="fas fa-tint"></i>
                                        </span>
                                            </div>

                                            <input name="name"
                                                   type="text"
                                                   value=""
                                                   class="form-control"
                                                   placeholder="arduino-project">
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
                                                      value=""
                                                      placeholder="Descripción del repositorio"></textarea>
                                        </div>
                                    </div>

                                    <div class="form-group">
                                <span class="btn btn-primary">
                                    <i class="fas fa-plus"></i>
                                    Añadir
                                </span>
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
