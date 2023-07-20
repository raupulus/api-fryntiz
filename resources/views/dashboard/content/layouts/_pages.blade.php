
<div class="row">

    {{-- Listado de Páginas creadas --}}
    <div id="box-page-selector" class="col-12 col-md-3 col-xl-2 mt-3">

        <div class="nav flex-column nav-pills" id="v-pills-tab"
             role="tablist" aria-orientation="vertical">

            @foreach($pages as $page)

                @php($active = $idPageActive ? $idPageActive === $page->id : $page->order === 1)

                <a class="btn-change-page nav-link {{$active ? 'active' : '' }}"
                   id="ref-page-{{$page->id}}-tab"
                   data-toggle="pill"
                   href="#ref-page-{{$page->id}}"
                   role="tab"
                   aria-controls="ref-page-{{$page->id}}"
                   data-id="{{$page->id}}"
                   aria-selected="{{$active ? 'true' : 'false'}}">Página {{$page->order}}</a>
            @endforeach

            <span class="btn-add-page btn btn-dark">
                +
            </span>
        </div>
    </div>


    {{-- Temporal, para dinamizar la descarga de contenido al cambiar de pághina --}}
    <div class="d-none">
        {{--
        - Al pulsar una nueva página, se descarga "$page->content" y se añade al siguiente textarea más abajo
        - Detectar el editor y cargar el contenido en el textarea correspondiente
        - Crear evento para que al pulsar un editor distinto, se aplique al textarea
        --}}

        <textarea id="textarea-content-editable" name="content">
            {{ $pageSelected->content }}
        </textarea>
    </div>

    <div class="col-12 col-md-9 col-xl-10 mt-3">

        <div class="tab-content" id="v-pills-tabContent">

            {{-- Selector de editores --}}
            {{--
            <div>
                <div class="btn-group" role="group"
                     aria-label="Basic example"
                     style="">
                    <button type="button"
                            class="btn btn-secondary disabled btn-success btn-change-editor"
                            data-editor="quill">
                        Quilljs
                    </button>

                    <button type="button"
                            data-editor="summernote"
                            class="btn btn-secondary btn-info btn-change-editor">
                        Summernote
                    </button>

                    <button type="button"
                            data-editor="grapesjs"
                            class="btn btn-secondary btn-info btn-change-editor">
                        GrapesJS
                    </button>

                    <button type="button"
                            data-editor="gutenberg"
                            class="btn btn-secondary btn-info btn-change-editor">
                        Gutenberg
                    </button>
                </div>
            </div>
            --}}

            {{-- Sección por cada página --}}
            @foreach($pages as $page)
                @php($active = $idPageActive ? $idPageActive === $page->id : $page->order === 1)

                <div class="box-page-form tab-pane fade show {{$active ? 'active' : ''}}"
                     id="ref-page-{{$page->id}}" role="tabpanel"
                     data-id="{{$page->id}}"
                     aria-labelledby="ref-page-{{$page->id}}-tab">
                    <div class="row">
                        <div class="col-12 text-right">
                            <span class="btn-save-page btn btn-success" data-id="{{$page->id}}">
                                Guardar Página
                            </span>
                        </div>
                    </div>

                    @include('dashboard.content.layouts._page')
                </div>
            @endforeach

        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-12">
        <div id="editor" class="box-editor">
            <p id="editor-title"></p>
        </div>
    </div>
</div>



