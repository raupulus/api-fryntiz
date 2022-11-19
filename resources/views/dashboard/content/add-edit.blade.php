@extends('adminlte::page')

@section('plugins.momentjs', true)
@section('plugins.tempusdominusBootstrap', true)
@section('plugins.select2', true)
@section('plugins.bootstrapDualListbox', true)
@section('plugins.quill', true)
@section('plugins.summernote', true)

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

                            <form action="{{$model && $model->id ? route($model::getCrudRoutes()['update'], $model->id) : route($model::getCrudRoutes()['store'])}}"
                                  enctype="multipart/form-data"
                                  method="POST">

                                @csrf

                                <input type="hidden" name="id"
                                       value="{{$model->id}}">

                                <input type="hidden" name="plaform_id"
                                       value="{{$model->plaform_id}}">

                                {{-- Sección con datos principales --}}
                                @include('dashboard.content.layouts._main')

                            </form>

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

        </div>
    </div>
@stop

@section('js')

    <script>

        document.addEventListener('DOMContentLoaded', () => {
            /*** Selector datetimepicker programar publicación ***/
            $('#programated_at').datetimepicker({icons:{time:'far fa-clock'}});

            /*** Select 2 ***/
            $('.select2').select2();
            $('.select2bs4').select2({
                theme:'bootstrap4'
            });


            /*** Selectores Dual Listbox ***/
            var dualListBox = $('.duallistbox').bootstrapDualListbox(bootstrapDualListboxOptions);

            let platformIdNode = document.getElementById('platform_id');

            function changePlatformCategories(categories, selectedCategories) {
                let node = document.getElementById('categories');

                if(node) {
                    node.innerHTML = '';

                    const selectedIds = selectedCategories.map(ele => ele.id);

                    categories.forEach(category => {
                        let option = document.createElement('option');
                        option.value = category.id;
                        option.innerText = category.name;

                        if(selectedIds.length && (selectedIds.includes(category.id))) {
                            option.selected = true;
                        }

                        node.appendChild(option);
                    });
                }
            }

            function changePlatformTags(tags, tagsSelected) {
                let node = document.getElementById('tags');

                if(node) {
                    node.innerHTML = '';

                    const selectedIds = tagsSelected.map(ele => ele.id);



                    tags.forEach(tag => {
                        let option = document.createElement('option');
                        option.value = tag.id;
                        option.innerText = tag.name;

                        if(selectedIds.length && (selectedIds.includes(tag.id))) {
                            option.selected = true;
                        }

                        node.appendChild(option);
                    });
                }
            }

            function changePlatformRelatedContent(contents, contentsRelated) {
                let currentContentId = "{{$model->id}}";
                let node = document.getElementById('contentRelated');

                let contentsRelatedIds = contentsRelated.map(content => content.id);

                if(node) {
                    node.innerHTML = '';

                    contents = contents.filter(content => content.id !== currentContentId);

                    contents = [...contents, ...contentsRelated];


                    // TODO: Al cambiar, marcar seleccionados los contenidos
                    //  que ya estaban seleccionadas antes de cambiar de
                    //  plataforma


                    contents.forEach(content => {
                        let option = document.createElement('option');
                        option.value = content.id;
                        option.innerText = content.title;

                        if(contentsRelatedIds && (contentsRelatedIds.includes(content.id))) {
                            option.selected = true;
                        }

                        node.appendChild(option);

                    });
                }

                dualListBox.bootstrapDualListbox('refresh');
            }

            if(platformIdNode) {
                platformIdNode.addEventListener('change', (e) => {
                    let platformId = e.target.value ?? "{{old('platform_id', $model->platform_id)}}";

                    let url = '{{route('dashboard.content.ajax.get.select.info.from.platform', ':platform')}}';

                    url = url.replace(':platform', platformId);


                    fetch(url,
                        {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]').content,
                            },
                            body: JSON.stringify({
                                contentId: "{{$model->id}}"
                            })
                        })
                        .then(response => response.json())
                        .then(data => {

                            console.debug(data);

                            if(data.contents) {
                                changePlatformRelatedContent(data.contents, data.contentsRelated);
                            }

                            if(data.categories) {
                                changePlatformCategories(data.categories, data.categoriesSelected);
                            }

                            if(data.tags) {
                                changePlatformTags(data.tags, data.tagsSelected);
                            }

                        });

                });
            }


            // Previsualización de imagen para apartado principal.
            preparePreviewImage('#main-image-input', '#main-image-preview', '#main-image-label');


            // TODO: Dinamizar por cada página que exista, externalizar a una
            // función en un archivo JS y realizar aquí las llamadas en bucle.

            // Inicializo editor Quill
            initQuillEditor('#editor', '#form', '#textarea');


            // Inicializo editor summernote
            $('#summernote').summernote(editorSummernoteOptions);
        });
    </script>

@stop
