<div class="row">

    {{-- Listado de Páginas creadas --}}
    <div id="box-page-selector" class="col-12 col-md-3 col-xl-2 mt-3">

        <div class="nav flex-column nav-pills" id="v-pills-tab"
             role="tablist" aria-orientation="vertical">

            @foreach($pages as $page)

                @php($active = $page->order === 1)

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
                {{$page->first()->content}}
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
                @php($active = $page->order === 1)

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

@section('css')
    <style>
        .box-editor {
            margin: auto;
            max-width: 1024px;
            background-color: #ffffff;
            padding: 1rem;
            border-radius: 5px;
        }

        .box-editor .ce-block__content {
            max-width: 900px;
        }
    </style>
@endsection

@section('js')

    <script>
        window.currentPage = "{{$pages->first()?->id}}"

        /**
         * Cambia a la página recibida guardando los datos primero.
         *
         * @param pageId Nueva página a la que ir.
         * @returns {Promise<void>}
         */
        async function changePage(pageId) {
            await savePage(currentPage);

            await editor.clear();

            let url = "{{route('dashboard.content.ajax.page.get.content', ':pageId')}}";

            url = url.replace(':pageId', pageId);

            // Establezco la página actual seleccionada
            currentPage = pageId;

            fetch(url, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
            }).then((response) => {
                return response.json();
            }).then((data) => {
                console.log(data);
                if(data && data.blocks && data.blocks.length) {
                    editor.render(data);
                }
            }).catch((error) => {
                console.log(error);
            });

        }

        /**
         * Procesa el guardado de datos para la página actual.
         *
         * @returns {Promise<void>}
         */
        async function savePage(pageId = null) {
            let contentJson = await editor.save();
            let updatePageUrl = '{{route('dashboard.content.ajax.page.update', ['contentType' => 'json', 'contentPage' => ':pageId'])}}';

            const pageMenuSelected = document.querySelector('#box-page-selector .nav-link.active');

            if (!pageId) {
                pageId = pageMenuSelected.getAttribute('data-id');
            }

            updatePageUrl = updatePageUrl.replace(':pageId', pageId);



            const boxPageForm = document.querySelector('.box-page-form[data-id="' + pageId + '"]');
            const title = boxPageForm.querySelector('input[name="title"]')?.value;
            const slug = boxPageForm.querySelector('input[name="slug"]')?.value;

            return fetch(updatePageUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    content: contentJson,
                    pageId: pageId,
                    title: title,
                    slug: slug,
                })
            }).then((response) => {
                return response.json();
            }).then((data) => {
                console.log(data);
            }).catch((error) => {
                console.log(error);
            });

        }

        async function uploadFile() {

        }


        /**
         * Crea una nueva página y recarga el gestor de contenido.
         *
         * @returns {Promise<void>}
         */
        async function createPage() {
            let form = document.createElement('form');
            form.setAttribute('id', 'createPageTemp');
            form.setAttribute('method', 'POST');
            form.setAttribute('action', "{{route('dashboard.content.add.page', $model->id)}}")

            let csrf = document.createElement('input');
            csrf.name = '_token';
            csrf.value = "{{csrf_token()}}";

            form.append(csrf);

            document.querySelector('div').append(form);

            let test = await savePage(currentPage);

            console.log(test);

            form.submit();
        }

        window.document.addEventListener('DOMContentLoaded', function () {

            // Botones para Guardar página
            let btnsSavePage = document.querySelectorAll('.btn-save-page');

            btnsSavePage.forEach((btn) => {
                btn.addEventListener('click', () => savePage(btn.getAttribute('data-id')));
            });

            let btnsChangePage = document.querySelectorAll('.btn-change-page');

            btnsChangePage.forEach((btn) => {
                btn.addEventListener('click', () => changePage(btn.getAttribute('data-id')));
            });


            let btnsAddPage = document.querySelectorAll('.btn-add-page');

            btnsAddPage.forEach((btn) => {
                btn.addEventListener('click', createPage);
            });


            // Modificar url al guardar
            // https://editorjs.io/changing-a-view/


            // TEST
            window.editor = new EditorJS({
                /**
                 * Id of Element that should contain Editor instance
                 */
                holder: 'editor',

                //stretched: true,

                /**
                 * Apply to all the blocks
                 */
                tunes: ['textVariant'],

                /**
                 * Available Tools list.
                 * Pass Tool's class or Settings object for each Tool you want to use
                 */
                tools: {
                    header: Header,
                    raw: RawTool,
                    textVariant: TextVariantTune,

                    code: CodeTool,

                    Marker: {
                        class: Marker,
                        shortcut: 'CMD+SHIFT+M',
                    },

                    /*
                    personality: {
                        class: Personality,
                        config: {
                            endpoint: "{{route('dashboard.content.ajax.upload.file')}}"
                        }
                    },
                    */

                    warning: {
                        class: Warning,
                        inlineToolbar: true,
                        shortcut: 'CMD+SHIFT+W',
                        config: {
                            titlePlaceholder: 'Title',
                            messagePlaceholder: 'Message',
                        },
                    },

                    paragraph: {
                        class: Paragraph,
                        inlineToolbar: true,
                    },

                    inlineCode: {
                        class: InlineCode,
                        shortcut: 'CMD+SHIFT+M',
                    },

                    attaches: {
                        class: AttachesTool,
                        field: 'file',
                        config: {
                            endpoint: "{{route('dashboard.content.ajax.upload.file', $model->id)}}",
                            additionalRequestHeaders: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                            }
                        },
                    },

                    quote: {
                        class: Quote,
                        inlineToolbar: true,
                        shortcut: 'CMD+SHIFT+O',
                        config: {
                            quotePlaceholder: 'Enter a quote',
                            captionPlaceholder: 'Quote\'s author',
                        },
                    },
                    list: {
                        class: List,
                        inlineToolbar: true,
                        config: {
                            defaultStyle: 'unordered'
                        }
                    },
                    embed: {
                        class: Embed,
                        inlineToolbar: true,
                        config: {
                            services: {
                                youtube: true,
                                coub: true,
                                codepen: {
                                    regex: /https?:\/\/codepen.io\/([^\/\?\&]*)\/pen\/([^\/\?\&]*)/,
                                    embedUrl: 'https://codepen.io/<%= remote_id %>?height=300&theme-id=0&default-tab=css,result&embed-version=2',
                                    html: "<iframe height='300' scrolling='no' frameborder='no' allowtransparency='true' allowfullscreen='true' style='width: 100%;'></iframe>",
                                    height: 300,
                                    width: 600,
                                    id: (groups) => groups.join('/embed/')
                                }
                            }
                        }
                    },
                    //image: SimpleImage,
                    checklist: {
                        class: Checklist,
                        inlineToolbar: true,
                    },
                    image: {
                        class: ImageTool,
                        config: {
                            types: 'image/*',
                            field: 'file',
                            endpoints: {
                                byFile: "{{route('dashboard.content.ajax.upload.file')}}", // Your backend file uploader endpoint
                                //byUrl: 'http://localhost:8008/fetchUrl', // Your endpoint that provides uploading by Url
                            },
                            additionalRequestData: {
                                content_id: '{{$model->id}}',
                            },
                            additionalRequestHeaders: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                            }
                        }
                    },


                    /*
                    linkTool: {
                        class: LinkTool,
                        config: {
                            endpoint: 'http://localhost:8008/fetchUrl', // Your backend endpoint for url data fetching,
                        }
                    }
                    */


                },

                /**
                 * onChange callback
                 */
                onChange: async (api, event) => {
                    //const index = event.detail.index;
                    const block = await event.detail.target.save();
                    const blockData = block.data;
                    const blockType = block.tool;

                    // Al añadir bloque de imagen, se disparan estos eventos:
                    // Event type:  block-added
                    // Event type:  block-removed
                    // Event type:  block-added

                    if (event.type === 'block-removed') {
                        console.log('Block removed: ', block);

                        if (['image', 'attaches'].includes(blockType) && blockData.file && blockData.file.file_id) {
                            let removeFileUrl = "{{route('dashboard.content.ajax.upload.remove.file')}}";

                            fetch(removeFileUrl, {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                                },
                                body: JSON.stringify({
                                    file_id: blockData.file.file_id
                                })
                            }).then((response) => {
                                return response.json();
                            }).then((data) => {
                                console.log(data);
                            }).catch((error) => {
                                console.log(error);
                            });

                        }

                    } else if (event.type === 'block-added') {
                        console.log('Block added: ', event.detail.block);
                    } else if (event.type === 'block-changed') {
                        console.log('Block updated: ', event.detail.block);

                        // Guardo toda la página al subir imágenes y adjuntos
                        if (['image', 'attaches'].includes(blockType)) {
                            savePage();
                        }

                    } else {
                        console.log('Event type: ', event.type);
                    }

                },

                data: {
                    "time": 1683536316992,
                    "blocks": [
                        {
                            "id": "kYV99Mgkjj",
                            "type": "paragraph",
                            "data": {
                                "text": "dfsafasdf asdf asdf asdf<br>"
                            },
                            "tunes": {
                                "textVariant": ""
                            }
                        },
                        {
                            "id": "lGe6graBkd",
                            "type": "paragraph",
                            "data": {
                                "text": "sfdghjkl ahsdJKFH Ajklsd hjklah djklfahs djklfhaklsjdhfjkl ajksldf asdf"
                            },
                            "tunes": {
                                "textVariant": ""
                            }
                        },
                        {
                            "id": "wlKXaBKYBX",
                            "type": "paragraph",
                            "data": {
                                "text": "&nbsp;asd fas"
                            },
                            "tunes": {
                                "textVariant": ""
                            }
                        },
                        {
                            "id": "fenPhV-w0a",
                            "type": "paragraph",
                            "data": {
                                "text": "df asdf a"
                            },
                            "tunes": {
                                "textVariant": ""
                            }
                        },
                        {
                            "id": "Ak-BnyOeM5",
                            "type": "list",
                            "data": {
                                "style": "unordered",
                                "items": [
                                    "asdfasdfasdfasdfas",
                                    "df",
                                    "as",
                                    "df",
                                    "as",
                                    "df",
                                    "as",
                                    "df"
                                ]
                            },
                            "tunes": {
                                "textVariant": ""
                            }
                        },
                        {
                            "id": "kxXibW2dK-",
                            "type": "paragraph",
                            "data": {
                                "text": "asdfasdfkjashdfjklahskdf ajklsdfhjkl<br>"
                            },
                            "tunes": {
                                "textVariant": ""
                            }
                        }
                    ],
                    "version": "2.26.5"
                }
            });


            // PROD

            /*
            const editorHandler = new EditorHandler('#editor', '#textarea', '#form', 'quill');
            editorHandler.handleChangeEditor('quill');


            const editorButtons = document.querySelectorAll('.btn-change-editor');

            editorButtons.forEach(function (button) {

                button.addEventListener('click', function (e) {
                    e.preventDefault();

                    editorButtons.forEach(function (btn) {
                        btn.classList.remove('disabled', 'btn-success');
                        btn.classList.add('btn-info');
                    });

                    button.classList.add('disabled', 'btn-success');
                    button.classList.remove('btn-info');

                    const editor = button.getAttribute('data-editor');

                    //handleChangeEditor(editor);
                    editorHandler.handleChangeEditor(editor);
                });
            });

            */

        });
    </script>
@endsection

