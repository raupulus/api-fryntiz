@extends('adminlte::page')

@section('title', 'Añadir Curriculum Vitae')

@section('content_header')
    <h1>
        <i class="fas fa-globe"></i>
        Añadir nuevo Curriculum Vitae
    </h1>
@stop

@section('content')

    <div class="row" id="app">
        <div class="col-12">
            <h2>
                Añade un nuevo Curriculum Vitae
                <a href="{{ route('dashboard.cv.store') }}"
                   class="btn btn-success float-right">
                    <i class="fas fa-save"></i>
                    Guardar
                </a>
            </h2>
        </div>

        <div class="col-12">
            <form action="{{route('dashboard.cv.store')}}">
                <div class="row">
                    <div class="col-md-6">
                        <div class="card card-primary">
                            <div class="card-header">
                                <h3 class="card-title">
                                    Datos principales
                                </h3>
                            </div>

                            <div class="card-body">
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
                                <h3 class="card-title">Opciones del
                                    Curriculum</h3>
                            </div>
                            <div class="card-body">

                                <div class="form-group">
                                    <div
                                        class="custom-control custom-switch">
                                        <input type="checkbox"
                                               class="custom-control-input"
                                               id="customSwitch1" checked>
                                        <label class="custom-control-label"
                                               for="customSwitch1">
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
                                               id="customSwitch1" checked>
                                        <label class="custom-control-label"
                                               for="customSwitch1">
                                            Permitir descarga
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Imagen --}}
                <div class="col-12">
                    <div class="card card-default">
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
                                    <img src="#" style="width: 80px" />

                                    <div class="custom-file">

                                        <input type="file"
                                               name="image"
                                               id="image"
                                               class="form-control-file">

                                        <label class="custom-file-label"
                                               for="image">
                                            Cambiar archivo
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

                <hr />

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

                            <span class="btn btn-primary">
                                <i class="fas fa-plus"></i>
                                Añadir
                            </span>

                            <div class="form-group">
                                <label for="image">
                                    Imagen
                                </label>
                                <div class="input-group">
                                    <img src="#" style="width: 80px" />

                                    <div class="custom-file">

                                        <input type="file"
                                               name="image"
                                               id="image"
                                               class="form-control-file">

                                        <label class="custom-file-label"
                                               for="image">
                                            Cambiar archivo
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label>Tipo de Repositorio</label>
                                <select class="form-control">
                                    @foreach(\App\Models\CV\CurriculumAvailableRepositoryType::all() as $type)
                                        <option value="{{ $type->id }}">
                                            {{ $type->title }}
                                        </option>
                                    @endforeach

                                    <option>option 1</option>
                                    <option>option 2</option>
                                    <option>option 3</option>
                                    <option>option 4</option>
                                    <option>option 5</option>
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
                                           name="url"
                                           value="{{old('url') }}"
                                           class="form-control"
                                           placeholder="https://gitlab.com/username/...">
                                </div>
                            </div>


                            <div class="form-group">
                                <label>Color picker:</label>
                                <input type="text" class="form-control my-colorpicker1 colorpicker-element" data-colorpicker-id="1" data-original-title="" title="">
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
