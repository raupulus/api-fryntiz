@extends('adminlte::page')

@section('plugins.momentjs', true)
@section('plugins.tempusdominusBootstrap', true)
@section('plugins.select2', true)
@section('plugins.bootstrapDualListbox', true)
@section('plugins.quill', true)
@section('plugins.summernote', true)
@section('plugins.editorjs', true)
@section('plugins.editors', true)

@section('title', 'Añadir ' . $model::getModelTitles()['singular'])

@section('content_header')
    <script>
        window.urlContentCreateTag = "{{ route('dashboard.content.ajax.tag.create') }}";
    </script>

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

                @php($navbarSectionID = request()->get('currentPage') ? 2 : 1)

                <nav>
                    <div class="nav nav-tabs" id="nav-tab" role="tablist">
                        <a class="nav-item nav-link {{$navbarSectionID === 1 ? 'active' : ''}}"
                           id="nav-home-tab" data-toggle="tab"
                           href="#nav-home" role="tab"
                           aria-controls="nav-home" aria-selected="{{$navbarSectionID === 1 ? 'true' : 'false'}}">
                            Datos Principales
                        </a>

                        @if ($model->id)
                            <a class="nav-item nav-link {{$navbarSectionID === 2 ? 'active' : ''}}"
                               id="nav-pages-tab"
                               data-toggle="tab" href="#nav-pages" role="tab"
                               aria-controls="nav-pages"
                               aria-selected="{{$navbarSectionID === 2 ? 'true' : 'false'}}">
                                Páginas
                            </a>
                        @endif

                        <a class="nav-item nav-link {{$navbarSectionID === 3 ? 'active' : ''}}" id="nav-contact-tab"
                           data-toggle="tab" href="#nav-contact" role="tab"
                           aria-controls="nav-contact"
                           aria-selected="{{$navbarSectionID === 3 ? 'true' : 'false'}}">
                            Metadatos
                        </a>

                        <a class="nav-item nav-link {{$navbarSectionID === 4 ? 'active' : ''}}" id="nav-o-tab"
                           data-toggle="tab" href="#nav-o" role="tab"
                           aria-controls="nav-contact"
                           aria-selected="{{$navbarSectionID === 4 ? 'true' : 'false'}}">
                            SEO
                        </a>
                    </div>
                </nav>
            </div>

            <div class="row">
                <div class="col-12">
                    <div class="tab-content" id="nav-tabContent">

                        {{-- Sección con datos principales del contenido --}}
                        <div class="tab-pane fade {{$navbarSectionID === 1 ? 'show active' : ''}}" id="nav-home"
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

                        {{-- Páginas con el editor de contenidos --}}
                        <div class="tab-pane fade {{$navbarSectionID === 2 ? 'show active' : ''}}" id="nav-pages"
                             role="tabpanel"
                             aria-labelledby="nav-profile-tab">
                            @includeWhen(isset($pages), 'dashboard.content.layouts._pages')
                        </div>

                        {{-- Metadatos --}}
                        <div class="tab-pane fade {{$navbarSectionID === 3 ? 'show active' : ''}}" id="nav-contact"
                             role="tabpanel"
                             aria-labelledby="nav-contact-tab">
                            ...
                        </div>

                        {{-- Seo --}}
                        <div class="tab-pane fade {{$navbarSectionID === 4 ? 'show active' : ''}}" id="nav-o"
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

    <script src="https://cdnjs.cloudflare.com/ajax/libs/multi-select/0.9.12/js/jquery.multi-select.min.js"
            integrity="sha512-vSyPWqWsSHFHLnMSwxfmicOgfp0JuENoLwzbR+Hf5diwdYTJraf/m+EKrMb4ulTYmb/Ra75YmckeTQ4sHzg2hg=="
            crossorigin="anonymous" referrerpolicy="no-referrer"></script>

    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/multi-select/0.9.12/css/multi-select.min.css"
          integrity="sha512-3lMc9rpZbcRPiC3OeFM3Xey51i0p5ty5V8jkdlNGZLttjj6tleviLJfHli6p8EpXZkCklkqNt8ddSroB3bvhrQ=="
          crossorigin="anonymous" referrerpolicy="no-referrer"/>
    <script>

        document.addEventListener('DOMContentLoaded', () => {
            /*** Selector datetimepicker programar publicación ***/
            $('#programated_at').datetimepicker({
                minDate: new Date(),
                icons:{time:'far fa-clock'}
            });

            /*** Select 2 ***/
            $('.select2').select2();
            $('.select2bs4').select2({
                theme:'bootstrap4'
            });


            /*** Selectores Dual Listbox ***/
            var dualListBox = $('.duallistbox').bootstrapDualListbox(bootstrapDualListboxOptions);

            const dualListBoxContentRelated = $('#contentRelated').multiSelect({
                selectableHeader:"<div class='multiselect-header'>Disponible</div>",
                selectionHeader:"<div " +
                    "class='multiselect-header'>Seleccionado</div>",
                selectableFooter:"",
                selectionFooter:"",
                beforeInit:function(algo) {
                    console.log(algo);
                },
                afterInit:function(container) {
                    // after init
                    console.log('Tras iniciar', container);
                },

                afterSelect:function(values) {

                    if(values && typeof values === 'object') {
                        Array.from(values).forEach(value => {
                            console.log('Valor', value);
                            let option = document.querySelector('#contentRelated option[value="' + value + '"]');
                            option.setAttribute('selected', 'selected');
                        });
                    }

                },
                afterDeselect:function(values) {
                    if(values && typeof values === 'object') {
                        Array.from(values).forEach(value => {
                            console.log('Valor', value);
                            let option = document.querySelector('#contentRelated option[value="' + value + '"]');
                            option.removeAttribute('selected');
                        });
                    }
                }
            });


            // Fuerzo seleccionar todo el contenido que debería estar seleccionado
            if(document.getElementById('contentRelated')) {
                let nd = document.querySelectorAll('#contentRelated option');
                if(nd && nd.length) {
                    Array.from(nd).forEach((ele) => {
                        ele.setAttribute('selected', 'selected');
                    });
                }
            }


            // Evento para que al buscar obtenga los nuevos resultados.
            var searchContentTimeout = null;

            const searchContentRelated = document.getElementById('searchContentRelated');

            function getContentRelatedFiltered(e) {
                let search = searchContentRelated.value;

                if(search.length < 2) {
                    return;
                }

                let platformIdInput = document.getElementById('platform_id');
                let platformId = platformIdInput.value ?? "{{old('platform_id', $model->platform_id)}}";
                let url = '{{route('dashboard.content.ajax.get.content.related.filtered', ':platform')}}';
                url = url.replace(':platform', platformId);

                let contentId = "{{$model->id}}";


                fetch(url, {
                    method:'POST',
                    headers:{
                        'Content-Type':'application/json',
                        'X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body:JSON.stringify({
                        content_related_search:search,
                        contentId:contentId,
                    })

                }).then(response => response.json())
                    .then(data => {
                        if(data && data.contents.length) {
                            let node = document.getElementById('contentRelated');

                            /*
                             while (node.firstChild) {
                             node.removeChild(node.firstChild);
                             }
                             */

                            let nodesNotSelected = node.querySelectorAll('option:not(option[selected="selected"])');

                            console.log(nodesNotSelected);

                            // Limpio nodos no seleccionados
                            if(nodesNotSelected.length) {
                                nodesNotSelected.forEach(node => {
                                    node.remove();
                                });
                            }


                            data.contents.forEach(content => {
                                let oldNode = node.querySelector
                                ('option[value="' + content.id + '"]');

                                console.log('oldNode:', oldNode);

                                if(!oldNode) {
                                    let option = document.createElement('option');
                                    option.value = content.id;
                                    option.innerText = content.title;

                                    node.appendChild(option);
                                }

                            });

                            /*
                             data.contentsRelated.forEach(content => {
                             let option = document.createElement('option');
                             option.value = content.id;
                             option.innerText = content.title;
                             option.selected = true;

                             node.appendChild(option);
                             });

                             */
                        }

                        //dualListBoxContentRelated.reload();
                        dualListBoxContentRelated.multiSelect('refresh');

                    });

            }

            searchContentRelated.addEventListener('keyup', (e) => {
                clearTimeout(searchContentTimeout);
                searchContentTimeout = null;

                searchContentTimeout = setTimeout((e) => getContentRelatedFiltered(e), 500);
            });


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

            /**
             * Al cambiar de plataforma, borra todo el contenido relacionado
             * tanto marcado como en búsqueda para añadir los de la plataforma
             * actual.
             *
             * @param contentsRelated
             */
            function changePlatformRelatedContent(contentsRelated) {
                let currentContentId = parseInt("{{$model->id}}");
                let node = document.getElementById('contentRelated');
                let searchContentRelated = document.getElementById('searchContentRelated');

                // Limpio el campo de búsqueda.
                searchContentRelated.value = '';

                if(node) {
                    node.innerHTML = '';

                    contentsRelated.forEach(content => {
                        if(parseInt(content.id) !== currentContentId) {
                            let option = document.createElement('option');
                            option.value = content.id;
                            option.innerText = content.title;
                            option.selected = true;

                            node.appendChild(option);
                        }
                    });
                }

                dualListBoxContentRelated.multiSelect('refresh');
            }

            if(platformIdNode) {
                platformIdNode.addEventListener('change', (e) => {
                    let platformId = e.target.value ?? "{{old('platform_id', $model->platform_id)}}";

                    let url = '{{route('dashboard.content.ajax.get.select.info.from.platform', ':platform')}}';

                    url = url.replace(':platform', platformId);

                    fetch(url,
                        {
                            method:'POST',
                            headers:{
                                'Content-Type':'application/json',
                                'X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]').content,
                            },
                            body:JSON.stringify({
                                contentId:"{{$model->id}}",
                                platformId:platformId
                            })
                        })
                        .then(response => response.json())
                        .then(data => {

                            if(data.contentsRelated) {
                                changePlatformRelatedContent(data.contentsRelated);
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
            //initQuillEditor('#editor', '#form', '#textarea');


            // Inicializo editor summernote
            //$('#summernote').summernote(editorSummernoteOptions);









        });






        // Set title form to slug
        let title = document.querySelector('input[data-slug_provider="title"]');
        let slug = document.querySelector('input[data-sluggable="title"]');

        console.log('title:', title, slug);

        if(title && slug) {
            title.addEventListener('focusout', () => {
                console.log('event focusout');
                // TODO check if slug is empty

                if(slug.value == '') {
                    slug.value = slugify(title.value);
                }

            });
        }
    </script>

@stop
