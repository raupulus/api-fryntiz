//require('./bootstrap');
//require('./vue');
//require('./global_vars');
//require('./functions');

//import * as Functions from './functions';

async function sendAjaxCreateFromWords(url, arrayWords, platformId) {
    return await fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({
            words: arrayWords,
            platform_id: platformId,
        })
    }).then(response => {
        return response.json();
    }).then((res) => {
        return res;
    });
}


/**
 * Prepara un bloque o modal para añadir palabras clave que pueden ser etiquetas, categorías...
 *
 * @param url Url a la que se enviarán las palabras para crearlas.
 * @param select2Selector Selector css para el select2.
 * @param boxSelector Nodo del DOM dónde van a contenerse todos los elementos.
 * @param inputSelector Selector css para el input de texto dónde se introducen todas las palabras separadas por coma.
 * @param saveButtonSelector Selector css para el botón de guardar.
 * @param showCreatedSelector Selector css para el bloque dónde se muestran las palabras creadas.
 * @param statusBoxSelector Selector css para el bloque dónde se muestran los mensajes de estado al guardar.
 */
function prepareModalAddWords(url,
                              select2Selector,
                              boxSelector,
                              inputSelector,
                              saveButtonSelector,
                              showCreatedSelector,
                              statusBoxSelector
)
{
    const box = document.querySelector(boxSelector);

    if (!box) {
        console.error('No se ha encontrado el nodo del DOM para el modal de crear palabras: ' + boxSelector);
        return;
    }

    const input = box.querySelector(inputSelector);
    const saveButton = box.querySelector(saveButtonSelector);
    const showCreated = box.querySelector(showCreatedSelector);
    const statusBox = box.querySelector(statusBoxSelector);

    if (!input || !saveButton || !showCreated || !statusBox) {
        console.error('No se ha encontrado algún nodo del DOM para el modal de crear palabras: ' + boxSelector);
        return;
    }

    saveButton.addEventListener('click', async () => {
        let text = input.value;
        let array = text.split(',');

        array = array.map((item) => {
            let text = item.trim();

            return (text && text.length > 2) ? text : null;
        }).filter((item) => item !== null);

        if (array && array.length) {
            let platformIdInput = document.getElementById('platform_id');
            let platformId = platformIdInput.value;

            // Almacena el mensaje de estado final
            let statusMessage = '';

            statusBox.textContent = '';

            // Realiza la petición AJAX para crear las etiquetas
            let ajaxResponse = await sendAjaxCreateFromWords(url, array, platformId);

            if (!ajaxResponse.errors && (ajaxResponse.status === 'ok') && (!ajaxResponse.tags?.length)) {
                // OK, Pero sin nuevas tags guardadas. Podrían ser repetidas o no válidas

                statusMessage = 'No se han guardado nuevas etiquetas.';

            } else if (!ajaxResponse.errors && (ajaxResponse.tags?.length)) {
                // OK, Con nuevas tags guardadas

                // Cantidad de tags guardadas.
                const quantity = ajaxResponse.tags.length;

                statusMessage = `Se han guardado ${quantity} etiquetas.`;

            } else if (ajaxResponse.errors && ajaxResponse.errors.length) {
                // En este punto ha ocurrido un error en el servidor, probablemente no hay plataforma

                // Mensaje de error/fallo.
                const message = ajaxResponse.message;

                // Array de errores.
                const errors = ajaxResponse.errors;


                statusMessage = message;

                errors.forEach((error) => {
                    //console.log(error);
                });
            }

            // Añado mensaje final al bloque de mensajes
            statusBox.textContent = statusMessage;

            if (ajaxResponse.tags?.length) {
                ajaxResponse.tags.forEach((ele) => {
                    let id = ele.id;
                    let name = ele.name;
                    let slug = ele.slug;

                    // Crear badge en el modal
                    let badge = document.createElement('span');

                    badge.classList.add('badge', 'badge-primary', 'mr-1');
                    badge.innerText = name;

                    if (showCreated) {
                        showCreated.appendChild(badge);
                    }

                    // Añadir nuevo slug al select
                    let select = document.querySelector(select2Selector);

                    if (select) {
                        let option = document.createElement('option');
                        option.value = id;
                        option.innerText = slug;
                        select.appendChild(option);

                        $(select2Selector).select2("destroy");

                        $(select2Selector).select2();
                    }

                });
            }
        }
    });

}

window.document.addEventListener('DOMContentLoaded', () => {

    // Inicializa crear etiquetas -> Estoy rehaciendo la lógica para categorías y etiquetas en las líneas inferiores
    prepareModalAddWords(window.urlContentCreateTag, '#tags', '#modal-create-tag', '#create-tag-input', '#create-tag-button', '#box-tags-created', '.msg-success');
    prepareModalAddWords(window.urlContentCreateCategory, '#categories', '#modal-create-category', '#create-category-input', '#create-categories-button', '#box-categories-created', '.msg-success');

});


