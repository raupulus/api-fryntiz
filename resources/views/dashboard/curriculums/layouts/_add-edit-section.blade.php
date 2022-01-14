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
                                @foreach($modelName::getTableHeads() as $head => $key)
                                    <th>{{$head}}</th>
                                @endforeach
                                <th class="text-center">Acciones</th>
                            </tr>
                            </thead>

                            <tbody>

                            @foreach($models as $ele)
                                <tr>
                                    @php($datas = $modelName::getTableCellsInfo())
                                    @foreach($modelName::getTableHeads() as $cell)
                                        @if ($datas[$cell]['type'] === 'image')
                                            <td class="text-center">
                                                <img
                                                    src="{{$ele->urlThumbnail('micro')}}"
                                                    alt="{{$ele->name}}"
                                                    style="width: 40px;">

                                            </td>
                                        @elseif ($datas[$cell]['type'] === 'text')
                                            <td class="align-middle">
                                                @if (isset($datas[$cell]['relation']) && $datas[$cell]['relation'])
                                                    {{($ele->$cell->{$datas[$cell]['relation_field']})}}
                                                @else
                                                    {{$ele->$cell}}
                                                @endif
                                            </td>
                                        @elseif ($datas[$cell]['type'] === 'link')
                                            <td class="align-middle">
                                                <a href="{{$ele->$cell}}"
                                                   target="_blank">
                                                    {{$ele->$cell}}
                                                </a>
                                            </td>
                                        @elseif ($datas[$cell]['type'] === 'badge')
                                            <td class="text-center align-middle">
                                                <span class="badge
                                                badge-primary">
                                                    {{$ele->$cell}}
                                                </span>
                                            </td>
                                        @endif
                                    @endforeach

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
                                                   value="{{$ele->id}}"/>

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

                                    @yield('form_inputs')
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
        window.document.addEventListener('DOMContentLoaded', function() {
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
