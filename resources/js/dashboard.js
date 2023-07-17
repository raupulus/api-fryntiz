//require('./bootstrap');
//require('./vue');
//require('./global_vars');
//require('./functions');

//import * as Functions from './functions';

async function sendAjaxCreateTags(tags, platformId) {
    return await fetch(window.urlContentCreateTag, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({
            tagNames: tags,
            platform_id: platformId,
        })
    }).then(response => {
        return response.json();
    }).then((res) => {
        return res;
    });
}

window.document.addEventListener('DOMContentLoaded', () => {
    // Prepara selector de etiquetas
    let modalCreateTag = document.querySelector('#modal-create-tag');

    if (modalCreateTag) {
        let modalCreateTagInput = modalCreateTag.querySelector('#create-tag-input');
        let modalCreateTagButton = modalCreateTag.querySelector('#create-tag-button');

        if (modalCreateTagInput && modalCreateTagButton) {
            modalCreateTagButton.addEventListener('click', async () => {
                let boxBadge =modalCreateTag.querySelector('#box-tags-created')

                let text = modalCreateTagInput.value;
                let array = text.split(',');

                array = array.map((item) => {
                    let text = item.trim();

                    return (text && text.length > 2) ? text : null;
                }).filter((item) => item !== null);

                if (array && array.length) {
                    let platformIdInput = document.getElementById('platform_id');
                    let platformId = platformIdInput.value;

                    // Bloque para mostrar mensajes de estado
                    let statusBox = document.querySelector('.msg-success');

                    // Almacena el mensaje de estado final
                    let statusMessage = '';

                    statusBox.textContent = '';

                    // Realiza la petición AJAX para crear las etiquetas
                    let ajaxResponse = await sendAjaxCreateTags(array, platformId);


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

                            if (boxBadge) {
                                boxBadge.appendChild(badge);
                            }

                            // Añadir nuevo slug al select
                            let select = document.querySelector('#tags');

                            if (select) {
                                let option = document.createElement('option');
                                option.value = id;
                                option.innerText = slug;
                                select.appendChild(option);

                                $('#tags').select2("destroy");

                                $('#tags').select2();
                            }

                        });
                    }
                }
            });
        }
    }
});


