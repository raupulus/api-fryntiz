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


                </div>


                {{-- Selector de secciones para el contenido --}}
                <div class="row">
                    <nav>
                        <div class="nav nav-tabs" id="nav-tab" role="tablist">
                            <a class="nav-item nav-link active"
                               id="nav-home-tab" data-toggle="tab"
                               href="#nav-home" role="tab"
                               aria-controls="nav-home" aria-selected="true">
                                Datos Principales
                            </a>
                            <a class="nav-item nav-link" id="nav-profile-tab"
                               data-toggle="tab" href="#nav-profile" role="tab"
                               aria-controls="nav-profile"
                               aria-selected="false">
                                Páginas
                            </a>
                            <a class="nav-item nav-link" id="nav-contact-tab"
                               data-toggle="tab" href="#nav-contact" role="tab"
                               aria-controls="nav-contact"
                               aria-selected="false">
                                Metadatos
                            </a>
                            <a class="nav-item nav-link" id="nav-o-tab"
                               data-toggle="tab" href="#nav-o" role="tab"
                               aria-controls="nav-contact"
                               aria-selected="false">
                                SEO
                            </a>
                        </div>
                    </nav>


                </div>

                <div class="row">
                    <div class="col-12">
                        <div class="tab-content" id="nav-tabContent">
                            <div class="tab-pane fade show active" id="nav-home"
                                 role="tabpanel" aria-labelledby="nav-home-tab">

                                {{-- Sección con datos principales --}}
                                @include('dashboard.content.layouts._main')
                            </div>
                            <div class="tab-pane fade" id="nav-profile"
                                 role="tabpanel" aria-labelledby="nav-profile-tab">
                                @include('dashboard.content.layouts._pages')
                            </div>
                            <div class="tab-pane fade" id="nav-contact"
                                 role="tabpanel" aria-labelledby="nav-contact-tab">
                                ...
                            </div>
                            <div class="tab-pane fade" id="nav-o"
                                 role="tabpanel" aria-labelledby="nav-o-tab">
                                ...
                            </div>
                        </div>
                    </div>
                </div>




                {{--test editor --}}
                <div class="row">
                    <div class="col-12">
                        <span class="btn btn-success">
                            Añadir Página
                        </span>
                    </div>
                    <div class="nav flex-column nav-pills" id="v-pills-tab"
                         role="tablist" aria-orientation="vertical">
                        <a class="nav-link active" id="v-pills-home-tab"
                           data-toggle="pill" href="#v-pills-home" role="tab"
                           aria-controls="v-pills-home"
                           aria-selected="true">Página 1</a>
                        <a class="nav-link" id="v-pills-profile-tab"
                           data-toggle="pill" href="#v-pills-profile" role="tab"
                           aria-controls="v-pills-profile"
                           aria-selected="false">Página 2</a>
                        <a class="nav-link" id="v-pills-messages-tab"
                           data-toggle="pill" href="#v-pills-messages"
                           role="tab" aria-controls="v-pills-messages"
                           aria-selected="false">Página 3</a>
                        <a class="nav-link" id="v-pills-settings-tab"
                           data-toggle="pill" href="#v-pills-settings"
                           role="tab" aria-controls="v-pills-settings"
                           aria-selected="false">Página 4</a>
                    </div>
                    <div class="tab-content" id="v-pills-tabContent">
                        <div class="tab-pane fade show active"
                             id="v-pills-home" role="tabpanel"
                             aria-labelledby="v-pills-home-tab">
                            <p>
                                111111111111111111
                            </p>


                            <!-- Create the toolbar container -->
                        {{--
                        <div id="toolbar">
                            <button class="ql-bold">Bold</button>
                            <button class="ql-italic">Italic</button>
                        </div>
                        --}}

                        <!-- Create the editor container -->
                            <div id="editor">
                                <p>Hello World!</p>
                            </div>
                        </div>
                        <div class="tab-pane fade" id="v-pills-profile"
                             role="tabpanel"
                             aria-labelledby="v-pills-profile-tab">
                            2222222222222222222
                        </div>
                        <div class="tab-pane fade" id="v-pills-messages"
                             role="tabpanel"
                             aria-labelledby="v-pills-messages-tab">
                            3333333333333333333
                        </div>
                        <div class="tab-pane fade" id="v-pills-settings"
                             role="tabpanel"
                             aria-labelledby="v-pills-settings-tab">
                            4444444444444444444
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




    {{-- Editor, cambiar a librería con npm --}}
    {{-- https://github.com/quilljs/quill --}}
    {{-- https://quilljs.com/docs/ --}}

    <!-- Theme included stylesheets -->
    <link href="//cdn.quilljs.com/1.0.0/quill.snow.css" rel="stylesheet"/>
    <link href="//cdn.quilljs.com/1.0.0/quill.bubble.css" rel="stylesheet"/>

    <!-- Core build with no theme, formatting, non-essential modules -->
    <link href="//cdn.quilljs.com/1.0.0/quill.core.css" rel="stylesheet"/>
    <script src="//cdn.quilljs.com/1.0.0/quill.core.js"></script>

    <!-- Main Quill library -->
    <script src="//cdn.quilljs.com/1.0.0/quill.js"></script>
    <script src="//cdn.quilljs.com/1.0.0/quill.min.js"></script>

    <!-- Initialize Quill editor -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {

            /*
             var editor = new Quill('#editor', {
             //modules: { toolbar: '#toolbar' },
             theme: 'snow',
             });

             */
            var Delta = Quill.import('delta');
            var quill = new Quill('#editor', {
                modules:{
                    toolbar:true
                },
                placeholder:'Compose an epic...',
                theme:'snow'
            });

// Store accumulated changes
            var change = new Delta();
            quill.on('text-change', function(delta) {
                change = change.compose(delta);
            });

// Save periodically
            setInterval(function() {
                if(change.length() > 0) {
                    console.log('Saving changes', change);
                    /*
                     Send partial changes
                     $.post('/your-endpoint', {
                     partial: JSON.stringify(change)
                     });

                     Send entire document
                     $.post('/your-endpoint', {
                     doc: JSON.stringify(quill.getContents())
                     });
                     */
                    change = new Delta();
                }
            }, 5 * 1000);

// Check for unsaved data
            window.onbeforeunload = function() {
                if(change.length() > 0) {
                    return 'There are unsaved changes. Are you sure you want to leave?';
                }
            }
        });
    </script>
@stop
