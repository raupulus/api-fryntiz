/**
 * Cambia a la página recibida guardando los datos primero.
 *
 * @param pageId Nueva página a la que ir.
 * @param save Guardar antes de cambiar de página.
 *
 * @returns {Promise<void>}
 */
async function changePage(pageId, save = true) {

    if (save) {
        await savePage(currentPage);
    }

    await editor.clear();

    let url = window.urlPageGetContent;

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
        //console.log(data);
        if (data && data.blocks && data.blocks.length) {
            editor.render(data);
        }
    }).catch((error) => {
        console.error(error);
    });

}



async function checkSlug(contentPageId, slug, input = null) {
    if (!contentPageId || !slug || !slug.length || slug.length < 5 || slug.length > 255) {
        if (input) {
            input.classList.add('is-invalid');
        }

        return false;
    }

    const url = window.urlPageCheckSlug?.replace(':page', contentPageId);

    return fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({
            slug: slug,
            contentPageId: contentPageId,
        })
    }).then(res => res.json()).then((data) => {
        const isValid = data && data.is_valid;

        if (input) {
            if (isValid) {
                input.classList.remove('is-invalid');
            } else {
                input.classList.add('is-invalid');
            }
        }

        return isValid;
    }).catch((error) => {
        console.error(error);
    });
}

/**
 * Procesa el guardado de datos para la página actual.
 *
 * @returns {Promise<void>}
 */
async function savePage(pageId = null) {
    if (!editor) {
        //console.error('No se ha encontrado el editor');
        return;
    }

    let contentJson = await editor.save();
    let updatePageUrl = window.urlPageUpdate;

    const pageMenuSelected = document.querySelector('#box-page-selector .nav-link.active');

    if (!pageId) {
        pageId = pageMenuSelected.getAttribute('data-id');
    }

    updatePageUrl = updatePageUrl.replace(':pageId', pageId);


    const boxPageForm = document.querySelector('.box-page-form[data-id="' + pageId + '"]');
    const title = boxPageForm.querySelector('input[name="title"]')?.value;
    const slug = boxPageForm.querySelector('input[name="slug"]')?.value;


    // Compruebo slug
    const isValidSlug = await checkSlug(pageId, slug);

    if (!isValidSlug) {
        return false;
    }

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
        //console.log(data);
    }).catch((error) => {
        console.error(error);
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
    form.setAttribute('action', window.urlPageAdd);

    let csrf = document.createElement('input');
    csrf.name = '_token';
    csrf.value = document.querySelector('meta[name="csrf-token"]').getAttribute('content')

    form.append(csrf);

    document.querySelector('div').append(form);

    let test = await savePage(currentPage);

    //console.log(test);

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

    /** Al modificar el slug, se comprueba si es válido y único **/
    let slugInputs = document.querySelectorAll('.page-slug');

    // Almacena las comprobaciones de slug para evitar que se hagan muchas peticiones
    window.checkingPageSlugInterval = {};

    slugInputs.forEach((input) => input.addEventListener('change', function (e) {
        checkSlug(input.getAttribute('data-page_id'), input.value, input)
    }));


    // Modificar url al guardar
    // https://editorjs.io/changing-a-view/


    // EditorJS
    if (document.getElementById('editor')) {
        window.editor = new EditorJS({
            /**
             * Id of Element that should contain Editor instance
             */
            holder: 'editor',
            placeholder: 'Comienza a escribir aquí...',
            readOnly: false, // editor.readOnly.toggle();


            logLevel: 'ERROR', // 'ERROR' | 'WARN' | 'INFO' | 'DEBUG'

            /*
            i18n: { // https://editorjs.io/i18n/
                messages: {
                    ui: {
                        // Translations of internal UI components of the editor.js core
                    },
                    toolNames: {
                        // Section for translation Tool Names: both block and inline tools
                    },
                    tools: {
                        // Section for passing translations to the external tools classes
                        // The first-level keys of this object should be equal of keys ot the 'tools' property of EditorConfig
                    },
                    blockTunes: {
                        // Section allows to translate Block Tunes
                    },
                }
            }
            */

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

                code: editorjsCodeflask, // Otras opciones: Code, CodeTool, CodeMirror, editorjsCodeflask

                Marker: {
                    class: Marker,
                    shortcut: 'CMD+SHIFT+M',
                },

                /*
                personality: {
                    class: Personality,
                    config: {
                        endpoint: urlUploadFile,
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
                        endpoint: window.urlUploadFile,
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
                            byFile: window.urlUploadFile, // Your backend file uploader endpoint
                            //byUrl: 'http://localhost:8008/fetchUrl', // Your endpoint that provides uploading by Url
                        },
                        additionalRequestData: {
                            content_id: window.contentId,
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
                    //console.log('Block removed: ', block);

                    if (['image', 'attaches'].includes(blockType) && blockData.file && blockData.file.file_id) {
                        let removeFileUrl = window.urlRemoveFile;

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
                            //console.log(data);
                            savePage();

                        }).catch((error) => {
                            console.log(error);
                        });

                    }

                } else if (event.type === 'block-added') {
                    //console.log('Block added: ', event.detail.block);
                } else if (event.type === 'block-changed') {
                    //console.log('Block updated: ', event.detail.block, event.detail, event);

                    //console.log('BlockData: ', blockData);

                    // Guardo toda la página al subir imágenes y adjuntos
                    if (['image', 'attaches'].includes(blockType)) {
                        await savePage();

                        if ((blockData.caption ||blockData.title) && blockData.file && blockData.file.content_file_id && blockData.file.file_id) {

                            // Cuando se actualice nombre de imagen, vendrá caption. Cuando sea un adjunto, vendrá title
                            const caption = blockData.caption?.replace(/<p>|<\/p>|<br>|<br\/>|<br \/>/gi, '').trim();
                            const title = blockData.title?.replace(/<p>|<\/p>|<br>|<br\/>|<br \/>/gi, '').trim();
                            const fileId = blockData.file.file_id;
                            const contentFileId = blockData.file.content_file_id;

                            let urlRaw = window.urlUpdateMetadataFile;
                            let url = urlRaw.replace(':fileId', fileId).replace(':contentFileId', contentFileId);

                            await fetch(url, {
                                method: 'PATCH',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                                },
                                body: JSON.stringify({
                                    title: caption ?? title,
                                    alt: caption ?? title,
                                })
                            })
                                .then(res => res.json())
                                .then(response => {
                                    //console.log('Success:', response);
                                })
                                .catch(error => console.error('Error:', error));


                        }
                    }

                } else {
                    //console.log('Event type: ', event.type);
                }

            },
            onReady: () => {
                // Inicio el editor con la página actual si existiera
                if (window.currentPage) {
                    changePage(window.currentPage, false);
                }
            },

            data: {},
        });


    }


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
