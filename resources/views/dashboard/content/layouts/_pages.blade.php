<div class="row">

    <div class="col-12" style="margin: 0; padding: 0;">

        {{-- Listado de Páginas creadas --}}
        <div id="box-page-selector" class="mt-1">

            <div class="nav flex-row nav-pills" id="v-pills-tab"
                 role="tablist" aria-orientation="vertical">

                    <span class="btn-add-page btn btn-dark mr-1">
                        +
                        <i class="fa fa-file-alt"></i>
                    </span>

                @foreach($pages as $page)

                    @php($active = $idPageActive ? $idPageActive === $page->id : $page->order === 1)

                    <a class="btn-change-page nav-link {{$active ? 'active' : '' }}"
                       id="ref-page-{{$page->id}}-tab"
                       data-toggle="pill"
                       href="#ref-page-{{$page->id}}"
                       role="tab"
                       aria-controls="ref-page-{{$page->id}}"
                       data-id="{{$page->id}}"
                       aria-selected="{{$active ? 'true' : 'false'}}">

                        <i class="fa fa-file-alt"></i>
                        {{$page->order}}
                    </a>
                @endforeach
            </div>
        </div>

        <div class="tab-content" id="v-pills-tabContent" style="width: 100%;">

            {{-- Sección por cada página --}}
            @foreach($pages as $page)
                @php($active = $idPageActive ? $idPageActive === $page->id : $page->order === 1)

                <div class="box-page-form tab-pane fade show {{$active ? 'active' : ''}}"
                     id="ref-page-{{$page->id}}" role="tabpanel"
                     data-id="{{$page->id}}"
                     aria-labelledby="ref-page-{{$page->id}}-tab">

                    <div
                        style="display: block; text-align: center; position: absolute; right: 0; top: 0; translate: 0 -80px;"
                        class="mt-1">
                            <span class="btn-save-page btn btn-success" data-id="{{$page->id}}" style="margin: auto;">
                                <i class="fa fa-save"></i>
                                Guardar Página
                            </span>

                        <span class="btn-delete-page btn btn-danger ml-1"
                              data-toggle="modal"
                              data-target="#modal-delete-page-{{$page->id}}"
                              data-id="{{$page->id}}"
                              style="margin: auto;">
                                <i class="fa fa-trash"></i>
                                Eliminar Página
                            </span>


                    </div>

                    @include('dashboard.content.layouts._page')

                    {{-- Modal Eliminar --}}
                    <div class="modal fade" id="modal-delete-page-{{$page->id}}"
                         tabindex="-1" role="dialog"
                         aria-labelledby="#modal-delete-page-{{$page->id}}-label"
                         aria-hidden="true">
                        <div class="modal-dialog" role="document">
                            <div class="modal-content">
                                <div class="modal-header">
                                            <span class="modal-title"
                                                  style="font-size: 1.4rem; font-weight: bold;"
                                                  id="modal-delete-page-{{$page->id}}-label">
                                                Eliminar página {{$page->order}}
                                            </span>

                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>

                                <div class="modal-body">
                                    <p style="font-weight: bold;">
                                        ¿Estás seguro de borrar la página {{$page->order}}?
                                    </p>

                                    <p>
                                        Título de la página: <strong>{{$page->title}}</strong>
                                    </p>

                                    <div class="alert alert-danger" role="alert">
                                        <p class="mb-1 mr-auto ml-auto text-center">
                                            <span class="alert-heading"
                                                  style="font-weight: bold; font-size: 1.4rem; text-align: center;">
                                                ¡Esta acción no se puede deshacer!
                                            </span>
                                        </p>

                                        <p>
                                            Al borrar una página se eliminará todo su contenido asociado.
                                        </p>

                                        <hr>

                                        <p class="mb-0">
                                            Todas las imágenes serán eliminadas del storage.
                                        </p>

                                        <p class="mb-0">
                                            Los adjuntos, galerías y cualquier cosa vinculada se perderá sin
                                            posibilidad de recuperación.
                                        </p>
                                    </div>
                                </div>

                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                                        Cerrar
                                    </button>

                                    <form method="POST"
                                          action="{{route('dashboard.content.page.delete', ['page' => $page->id, 'content' => $model->id])}}">
                                        @csrf

                                        <input type="hidden" value="{{$model->id}}" name="content_id">
                                        <input type="hidden" value="{{$page->id}}" name="page_id">

                                        <button type="submit" class="btn btn-danger">
                                            ¡Eliminar!
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach

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

</div>

<div class="row mt-1">
    <div class="col-12" style="margin: 0; padding: 0;">
        <div id="editor" class="box-editor"></div>
    </div>
</div>



