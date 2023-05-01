const editorQuillOptions = {
    modules:{
        toolbar:true,
        //modules: { toolbar: '#toolbar' },
    },
    placeholder:'Redacta tu contenido aquí...',
    theme:'snow'
};

const editorSummernoteOptions = {
    lang: 'es-ES',
}

const editorGutenbergOptions = {

}

const editorGrapesJSOptions = {

}

/**
 * Clase que representa y gestiona los editores de texto para mantener seguro el contenido
 * al cambiar entre ellos y realizar las acciones/eventos correspondientes.
 */
window.EditorHandler = class {
    /**
     * Constructor de la clase.
     *
     * @param boxEditor Selector para la caja de edición del texto, contenedor dónde se pondrá el editor final.
     * @param textareaSelector Selector para el textarea que contiene
     *                         el resultado de texto enriquecido del editor.
     * @param formSelector
     * @param editor
     */
    constructor(boxEditor, textareaSelector, formSelector, editor = 'quill') {
        this.boxEditor = boxEditor;
        this.currentEditor = editor;
        this.textareaSelector = textareaSelector;
        this.formSelector = formSelector;

        this.editors = {
            'quill': {
                init: this.initQuillEditor,
                destroy: this.destroyQuillEditor
            },
            'summernote': {
                init: this.initSummernoteEditor,
                destroy: this.destroySummernoteEditor
            }
        }

        //handleChangeEditor(editor);
    }

    handleChangeEditor(editor = 'quill') {
        console.log('Cambiando editor a: ' + editor);

        if (this.currentEditorInstance !== null) {
            try {
                this.editors[this.currentEditor].destroy(this.currentEditorInstance);
            } catch (e) {
                console.log('No se ha podido destruir el editor: ' + this.currentEditor);
                console.log(e);
            }
        }

        this.currentEditor = editor;
        this.currentEditorInstance = this.editors[editor].init(this.boxEditor, this.formSelector, this.textareaSelector);
    }

    /**
     * Inicializa el editor de texto enriquecido Quill.
     *
     */
    initQuillEditor(boxEditor, formSelector, textareaSelector) {
        //const Delta = Quill.import('delta');
        const quill = new Quill(boxEditor, editorQuillOptions);

        quill.enable(true);

        return quill;

    }

    destroyQuillEditor(currentEditorInstance, boxEditor, formSelector, textareaSelector) {
        const box = currentEditorInstance.container.parentNode;
        const toolbars =  box.querySelectorAll('.ql-toolbar');

        console.log(box, toolbars);
        toolbars.forEach((child) => {
            child.parentNode.removeChild(child);
        });

        currentEditorInstance.enable(false);

        let childs = Array.from(currentEditorInstance.container.childNodes);

        childs.forEach((child) => {
            currentEditorInstance.container.removeChild(child);
        });
    }

    initSummernoteEditor(boxEditor, formSelector, textareaSelector) {

        return $(boxEditor).summernote(editorSummernoteOptions);
    }

    destroySummernoteEditor(currentEditorInstance, boxEditor, formSelector, textareaSelector) {
        $(this.boxEditor).summernote('destroy');
    }

    initCodeMirrorEditor(editorSelector, formSelector,
                                  textareaSelector) {

    }

    initGrapejsEditor(editorSelector, formSelector,
                               textareaSelector) {

    }




}




/**
 * Comprueba si hay cambios en el editor actual y pide confirmar guardar.
 * En el caso de confirmar, realizar envío ajax para el contenido.
 *
 * @returns {boolean} Devuelve si todo está guardado
 */
function saveEditorChanges() {

    // Comprobar si todo está guardado
    // En caso de no, preguntar para guardar o cancelar acción

    return confirm('¿Desea guardar los cambios?');
}

/**
 * Cambia el editor actual por el recibido.
 * Si el editor actual tiene cambios, pide confirmar guardar.
 *
 * @param editor string Nombre del editor al que cambiar.
 */
window.handleChangeEditor = (editor = 'quill') =>
{
    // 1 - Mantener editor actual almacenado en algún sitio
    // 2 - Comprobar si hay cambios en el editor actual y pedir confirmar guardar
    // saveEditorChanges();

    if (!saveEditorChanges()) {
        console.log('No se ha guardado el editor actual');
        return;
    }

    console.log('Cambiando editor a: ' + editor);


    // Problemas
    // como detectar el editor actual

    if (editor === 'quill') {


        initQuillEditor('#editor', '#form', '#textarea');
    }
}


/**
 * Inicializa el editor de texto enriquecido Quill.
 *
 * @param editorSelector Selector para la caja de edición del texto.
 * @param formSelector  Selector para el formulario que contiene el editor.
 * @param textareaSelector Selector para el textarea que contiene
 *                         el resultado de texto enriquecido
 */
function initQuillEditor(editorSelector, formSelector,
                         textareaSelector) {

    const Delta = Quill.import('delta');
    const quill = new Quill(editorSelector, editorQuillOptions);


    // TODO: Cuando implemente creación de páginas, añadir el cuerpo
    /*
     const form = document.querySelector('form');
     form.onsubmit = function() {
     // Populate hidden form on submit
     const about = document.querySelector('input[name=about]');
     about.value = JSON.stringify(quill.getContents());

     console.log("Submitted", $(form).serialize(), $(form).serializeArray());

     // No back end to actually submit to!
     alert('Open the console to see the submit data!')
     return false;
     };
     */

    return quill;
}
