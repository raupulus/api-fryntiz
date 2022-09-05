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
                                 role="tabpanel"
                                 aria-labelledby="nav-profile-tab">
                                @include('dashboard.content.layouts._pages')
                            </div>
                            <div class="tab-pane fade" id="nav-contact"
                                 role="tabpanel"
                                 aria-labelledby="nav-contact-tab">
                                ...
                            </div>
                            <div class="tab-pane fade" id="nav-o"
                                 role="tabpanel" aria-labelledby="nav-o-tab">
                                ...
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

    @section('plugins.momentjs', true)
    @section('plugins.tempusdominusBootstrap', true)
    @section('plugins.select2', true)
    @section('plugins.bootstrapDualListbox', true)



    <script>

        document.addEventListener('DOMContentLoaded', () => {
            /*** Selector datetimepicker programar publicación ***/
            $('#programated_at').datetimepicker({icons:{time:'far fa-clock'}});

            /*** Select 2 ***/
            $('.select2').select2();
            $('.select2bs4').select2({
                theme:'bootstrap4'
            });

            //Bootstrap Duallistbox
            $('.duallistbox').bootstrapDualListbox({
                nonSelectedListLabel:'No seleccionados',
                selectedListLabel:'Seleccionados',
                preserveSelectionOnMove:'moved',
                moveOnSelect:false,
                nonSelectedFilter:'',
                infoText:'Mostrando todos {0}',
                infoTextFiltered:'<span class="badge badge-warning">Filtrado</span> {0} de {1}',
                infoTextEmpty:'Lista vacía',
                filterPlaceHolder:'Filtrar',
                filterTextClear:'Mostrar todos',
                moveSelectedLabel:'Mover seleccionados',
                moveAllLabel:'Mover todos',
                removeSelectedLabel:'Eliminar seleccionados',
                removeAllLabel:'Eliminar todos',
                selectorMinimalHeight:160,
                moveOnDoubleClick:true,
                showFilterInputs:true,
                showMoveAll:false,
                showRemoveAll:false,
                showSelectedList:true,
                showNonSelectedList:true,
                showAvailableOptions:true,
                showSelectedOptions:true,
            });
        });

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
