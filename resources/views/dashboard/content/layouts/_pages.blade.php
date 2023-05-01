<div class="row">

    {{-- Listado de Páginas creadas --}}
    <div class="col-12 col-md-3 col-xl-2 mt-3">

        <div class="nav flex-column nav-pills" id="v-pills-tab"
             role="tablist" aria-orientation="vertical">

            @foreach($pages as $page)

                @php($active = $page->order === 1)

                <a class="nav-link {{$active ? 'active' : '' }}" id="ref-page-{{$page->id}}-tab"
                   data-toggle="pill" href="#ref-page-{{$page->id}}" role="tab"
                   aria-controls="ref-page-{{$page->id}}"
                   aria-selected="{{$active ? 'true' : 'false'}}">Página {{$page->order}}</a>
            @endforeach

            {{-- TODO: Crear página por ajax --}}
            <span class="btn btn-dark">
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

            {{-- Sección por cada página --}}
            @foreach($pages as $page)
                @php($active = $page->order === 1)

                <div class="tab-pane fade show {{$active ? 'active' : ''}}"
                     id="ref-page-{{$page->id}}" role="tabpanel"
                     aria-labelledby="ref-page-{{$page->id}}-tab">
                    <div class="row">
                        <div class="col-12">
                            <h3>Página {{$page->order}}</h3>
                        </div>

                        <div class="col-12 text-right">
                            <button class="btn btn-success">
                                Guardar Página
                            </button>
                        </div>
                    </div>

                    @include('dashboard.content.layouts._page')
                </div>
            @endforeach

        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div id="toolbar"></div>
        <div id="editor" class="box-editor">
            <p>Hello World!</p>
        </div>
    </div>
</div>


<div class="row">
    <div class="col-12">
        <h3>Test Editor.js</h3>
        <div id="toolbar1"></div>
        <div id="editor1" class="box-editor">
            <p>Hello World!</p>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <h3>Test Grapes.js</h3>
        <div id="toolbar2"></div>
        <div id="editor2" class="box-editor">
            <p>Hello World!</p>
        </div>
    </div>
</div>

@section('js')

    <script>
        window.document.addEventListener('DOMContentLoaded', function () {

            // TEST
            const editor = new EditorJS({
                /**
                 * Id of Element that should contain Editor instance
                 */
                holder: 'editor1',

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

                    personality: {
                        class: Personality,
                        /*
                        config: {
                            endpoint: 'http://localhost:8008/uploadFile'  // Your backend file uploader endpoint
                        }
                        */
                    },

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
                        /*
                        config: {
                            endpoint: 'http://localhost:8008/uploadFile'
                        }
                        */
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
                        /*
                        config: {
                            endpoints: {
                                byFile: 'http://localhost:8008/uploadFile', // Your backend file uploader endpoint
                                byUrl: 'http://localhost:8008/fetchUrl', // Your endpoint that provides uploading by Url
                            }
                        }
                        */
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

