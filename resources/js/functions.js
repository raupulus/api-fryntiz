/**
 * Conmuta una clase para el elemento recibido. La quita si la tiene o la pone
 * en caso de no tenerla aún.
 *
 * @param selector El selector al que se le cambia la clase.
 * @param name La clase que se asignará, por defecto 'hidden'.
 */
function toggle(selector, name = 'hidden') {
    let elements = document.querySelectorAll(selector);

    if (elements && elements.length) {
        Array.from(elements).forEach((ele) => {
            let isHidden = ele.classList.contains(name);
            isHidden ? ele.classList.remove(name) : ele.classList.add(name);
        });
    }
}

/**
 * Limpia una cadena de texto para que sea válida como slug/path uri.
 *
 * @param text
 * @returns {string}
 */
function slugify(text) {
    return text.toString().toLowerCase().trim()
        .normalize('NFD') 				 // separate accent from letter
        .replace(/[\u0300-\u036f]/g, '') // remove all separated accents
        .replace(/\s+/g, '-')            // replace spaces with -
        .replace(/&/g, '-and-')          // replace & with 'and'
        .replace(/[^\w\-]+/g, '')        // remove all non-word chars
        .replace(/\-\-+/g, '-')          // replace multiple '-' with single '-'
}

/**
 * Prepara la previsualización de imágenes que se van a subir en
 * campos del formulario.
 *
 * @param inputSelector Campo de entrada para la imagen, input type="file"
 * @param previewSelector Contenedor de la imagen previa, img
 * @param labelSelector Bloque dónde poner el nombre del archivo
 * @param buttonSelector Botón para abrir el selector de archivos
 */
function preparePreviewImage(inputSelector, previewSelector,
                             labelSelector = null,
                             buttonSelector = null) {
    const input = document.querySelector(inputSelector);
    const preview = document.querySelector(previewSelector);
    const label = document.querySelector(labelSelector);
    const button = document.querySelector(buttonSelector);

    if (button) {
        button.addEventListener('click', () => {
            input.click();
        });
    }

    if (preview) {
        preview.addEventListener('click', () => {
            input.click();
        });
    }

    input.onchange = () => {
        const reader = new FileReader();

        reader.onload = () => {
            preview.src = reader.result;
        }

        if(input.files && input.files[0]) {
            reader.readAsDataURL(input.files[0]);

            if(label) {
                label.textContent = input.files[0].name;
            }
        }
    };
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

function initSummernoteEditor(editorSelector, formSelector,
                              textareaSelector) {

}

function initCodeMirrorEditor(editorSelector, formSelector,
                              textareaSelector) {

}

function initGrapejsEditor(editorSelector, formSelector,
                              textareaSelector) {

}
