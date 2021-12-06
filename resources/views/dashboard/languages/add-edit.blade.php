@extends('adminlte::page')

@section('title', 'Añadir Lenguaje')

@section('content_header')
    <h1>
        <i class="fas fa-globe"></i>
        Añadir nuevo Lenguaje
    </h1>
@stop

@section('content')

    <div class="row" id="app">
        <div class="col-12">
            <h2>
                Añade un nuevo lenguaje
                <a href="{{ route('dashboard.language.store') }}"
                   class="btn btn-success float-right">
                    <i class="fas fa-save"></i>
                    Guardar
                </a>
            </h2>
        </div>

        <div class="col-md-6">
            <!-- general form elements -->
            <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title">
                        Descripción del idioma
                    </h3>
                </div>
                <!-- /.card-header -->
                <!-- form start -->
                <form>
                    <div class="card-body">
                        <div class="form-group">
                            <label for="exampleInputEmail1">
                                Locale
                            </label>
                            <input type="text" class="form-control"
                                   placeholder="es_ES">
                        </div>

                        <div class="form-group">
                            <label for="exampleInputEmail1">
                                ISO Locale
                            </label>
                            <input type="text" class="form-control"
                                   placeholder="es-ES">
                        </div>

                        <div class="form-group">
                            <label for="exampleInputFile">
                                Icono/Bandera (png, min 128px 1:1)
                            </label>
                            <div class="input-group">
                                <div class="custom-file">
                                    <input type="file" class="custom-file-input"
                                           id="exampleInputFile">
                                    <label class="custom-file-label"
                                           for="exampleInputFile">
                                        Cambiar archivo
                                    </label>
                                </div>
                                <div class="input-group-append">
                                    <span class="input-group-text">Upload</span>
                                </div>
                            </div>
                        </div>
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input"
                                   id="exampleCheck1">
                            <label class="form-check-label" for="exampleCheck1">Check
                                me out</label>
                        </div>
                    </div>
                    <!-- /.card-body -->

                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary">Submit
                        </button>
                    </div>
                </form>
            </div>
            <!-- /.card -->


        </div>


        <div class="col-md-6">
            <div class="card card-warning">
                <div class="card-header">
                    <h3 class="card-title">Opciones del Idioma</h3>
                </div>
                <!-- /.card-header -->
                <div class="card-body">
                    <form>
                        <div class="row">
                            <div class="col-sm-6">
                                <!-- text input -->
                                <div class="form-group">
                                    <label>ISO 2</label>
                                    <input type="text" class="form-control"
                                           placeholder="Enter ...">
                                </div>
                            </div>

                            <div class="col-sm-6">
                                <!-- text input -->
                                <div class="form-group">
                                    <label>ISO 3</label>
                                    <input type="text" class="form-control"
                                           placeholder="Enter ...">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-sm-12">
                                <!-- textarea -->
                                <div class="form-group">
                                    <label>Descripción</label>
                                    <textarea class="form-control" rows="4"
                                              placeholder="Enter ..."></textarea>
                                </div>
                            </div>
                        </div>

                        <!-- input states -->
                        <div class="form-group">
                            <label class="col-form-label" for="inputSuccess"><i
                                    class="fas fa-check"></i> Input with
                                success</label>
                            <input type="text" class="form-control is-valid"
                                   id="inputSuccess" placeholder="Enter ...">
                        </div>
                        <div class="form-group">
                            <label class="col-form-label" for="inputWarning"><i
                                    class="far fa-bell"></i> Input with
                                warning</label>
                            <input type="text" class="form-control is-warning"
                                   id="inputWarning" placeholder="Enter ...">
                        </div>
                        <div class="form-group">
                            <label class="col-form-label" for="inputError"><i
                                    class="far fa-times-circle"></i> Input with
                                error</label>
                            <input type="text" class="form-control is-invalid"
                                   id="inputError" placeholder="Enter ...">
                        </div>

                        <div class="row">
                            <div class="col-sm-6">
                                <!-- checkbox -->
                                <div class="form-group">
                                    <div class="form-check">
                                        <input class="form-check-input"
                                               checked
                                               name="enabled"
                                               type="checkbox">
                                        <label class="form-check-label">
                                            Habilitado
                                        </label>
                                    </div>

                                </div>
                            </div>
                            <div class="col-sm-6">
                                <!-- radio -->
                                <div class="form-group">
                                    <div class="form-check">
                                        <input class="form-check-input"
                                               type="radio" name="radio1">
                                        <label
                                            class="form-check-label">Radio</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input"
                                               type="radio" name="radio1"
                                               checked="">
                                        <label class="form-check-label">Radio
                                            checked</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input"
                                               type="radio" disabled="">
                                        <label class="form-check-label">Radio
                                            disabled</label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-sm-6">
                                <!-- select -->
                                <div class="form-group">
                                    <label>Select</label>
                                    <select class="form-control">
                                        <option>option 1</option>
                                        <option>option 2</option>
                                        <option>option 3</option>
                                        <option>option 4</option>
                                        <option>option 5</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label>Select Disabled</label>
                                    <select class="form-control" disabled="">
                                        <option>option 1</option>
                                        <option>option 2</option>
                                        <option>option 3</option>
                                        <option>option 4</option>
                                        <option>option 5</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-sm-6">
                                <!-- Select multiple-->
                                <div class="form-group">
                                    <label>Select Multiple</label>
                                    <select multiple="" class="form-control">
                                        <option>option 1</option>
                                        <option>option 2</option>
                                        <option>option 3</option>
                                        <option>option 4</option>
                                        <option>option 5</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label>Select Multiple Disabled</label>
                                    <select multiple="" class="form-control"
                                            disabled="">
                                        <option>option 1</option>
                                        <option>option 2</option>
                                        <option>option 3</option>
                                        <option>option 4</option>
                                        <option>option 5</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <!-- /.card-body -->
            </div>
        </div>

        @stop

        @section('js')
            <script src="{{ mix('dashboard/js/dashboard.js') }}"></script>
@stop
