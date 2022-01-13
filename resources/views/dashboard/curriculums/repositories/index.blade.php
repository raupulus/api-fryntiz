@extends('dashboard.curriculums.layouts._add-edit-section')

@section('form_inputs')
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
        <label>Tipo de Repositorio</label>
        <select class="form-control"
                name="repository_type_id">
            @foreach(\App\Models\CV\CurriculumAvailableRepositoryType::all() as $type)
                @php($selected = '')

                @if ($model && $model->repository_type_id == $type->id)
                    @php($selected = 'selected')
                @endif

                <option value="{{ $type->id }}"
                    {{$selected}}>
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

            <input type="url"
                   value="{{old('url',
                       $model ?
                       $model->url : '') }}"
                   name="url"
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
                   value="{{old('title',
                               $model ?
                               $model->title : '') }}"
                   name="title"
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
                   value="{{old('name',
                               $model ?
                               $model->name : '') }}"
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
                      placeholder="Descripción del repositorio">{{old('description',
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
@stop

{{--
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
            @php($action = $repository && $repository->id ?
                route('dashboard.cv.repository.update', $repository->id) :

                route('dashboard.cv.repository.store', $cv->id))

            <form action="{{$action}}"
                  method="POST"
                  enctype="multipart/form-data">

                @csrf

                @if ($repository && $repository->id)
                    <input type="hidden" name="repository_id" value="{{$repository->id}}">
                @endif

                <div class="col-12">

                    <h2>
                        <a style="font-size: 2rem;"
                           href="{{route('dashboard.cv.edit', $cv->id)}}">
                            <i class="fa fa-arrow-left"></i>

                        </a>

                        Añade un nuevo Repositorio
                    </h2>
                </div>

                <div class="col-12">
                    <div class="card card-info">
                        <div class="card-header">
                            <h3 class="card-title">Repositorios</h3>
                        </div>

                        <div class="card-body">
                            <table class="table table-bordered table-dark">
                                <thead>
                                <tr>
                                    <th class="text-center">Imagen</th>
                                    <th class="text-center">Título</th>
                                    <th class="text-center">Tipo</th>
                                    <th class="text-center">URL</th>
                                    <th class="text-center">descripción</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                                </thead>

                                <tbody>

                                @foreach($repositories as $rep)
                                    <tr>
                                        <td class="text-center">
                                            <img
                                                src="{{$rep->urlThumbnail('micro')}}"
                                                alt="{{$rep->title}}"
                                                style="width: 40px;">

                                        </td>

                                        <td class="align-middle">
                                            {{$rep->title}}
                                        </td>

                                        <td class="align-middle text-center">
                                            @if ($rep->type)
                                                <a href="{{$rep->type->url}}"
                                                   target="_blank">
                                                    <span class="badge badge-success">
                                                        @if ($rep->type->image)
                                                            <img
                                                                src="{{$rep->type->urlThumbnail('micro')}}"
                                                                alt="{{$rep->type->image->name}}"
                                                                style="width: 20px;"
                                                            />
                                                        @endif

                                                        {{$rep->type->title}}
                                                    </span>
                                                </a>


                                            @else
                                                'n/d'
                                            @endif
                                        </td>
                                        <td class="align-middle">
                                            <a href="{{$rep->url}}"
                                               target="_blank">
                                                {{$rep->url}}
                                            </a>
                                        </td>
                                        <td class="align-middle">{{$rep->description}}</td>
                                        <td class="align-middle text-center">
                                            <a href="{{route('dashboard.cv.repository.edit', $rep->id)}}"
                                               class="btn btn-warning btn-sm">
                                                <i class="fa fa-edit"></i>
                                            </a>

                                            &nbsp;

                                            <form method="POST"
                                                  action="{{route('dashboard.cv.repository.destroy', $rep->id)}}"
                                                  class="d-inline-block">

                                                @csrf

                                                <input type="hidden"
                                                       name="id"
                                                       value="{{$rep->id}}" />

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
                                        Añadir nuevo repositorio
                                    </h4>
                                </div>

                                <div class="card-body">
                                    <div class="form-group">
                                        <label for="image">
                                            Imagen
                                        </label>

                                        <div class="input-group">
                                            <img
                                                src="{{
                                                $repository ?
                                                $repository->urlThumbnail('small') :
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
                                        <label>Tipo de Repositorio</label>
                                        <select class="form-control"
                                                name="repository_type_id">
                                            @foreach($availableRepositories as $type)
                                                @php($selected = '')

                                                @if ($repository && $repository->repository_type_id == $type->id)
                                                    @php($selected = 'selected')
                                                @endif

                                                <option value="{{ $type->id }}"
                                                        {{$selected}}>
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

                                            <input type="url"
                                                   value="{{old('url',
                                                   $repository ?
                                                   $repository->url : '') }}"
                                                   name="url"
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
                                                   value="{{old('title',
                                                   $repository ?
                                                   $repository->title : '') }}"
                                                   name="title"
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
                                                   value="{{old('name',
                                                   $repository ?
                                                   $repository->name : '') }}"
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
                                                      placeholder="Descripción del repositorio">{{old('description',
                                                   $repository ?
                                                   $repository->description : '') }}</textarea>
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        @if ($repository && $repository->id)
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
--}}
