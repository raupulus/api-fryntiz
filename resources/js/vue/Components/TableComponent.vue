<template>
    <div class="box-vue-table-component" ref="table">
        <div>
            <h3 v-if="title">{{ title }}</h3>
        </div>

        <div v-if="searchable" class="v-table-box-search">
            <input type="search"
                   @keyup="handleOnWriteSearchKeyboardUp"
                   v-model="search">

            <span class="svg-icon"
                  @click="(e) => handleChangeFilter(e, 'search')">
                <svg xmlns="http://www.w3.org/2000/svg"
                     viewBox="0 0 512 512">
                <path d="M500.3 443.7l-119.7-119.7c27.22-40.41 40.65-90.9 33.46-144.7C401.8 87.79 326.8 13.32 235.2 1.723C99.01-15.51-15.51 99.01 1.724 235.2c11.6 91.64 86.08 166.7 177.6 178.9c53.8 7.189 104.3-6.236 144.7-33.46l119.7 119.7c15.62 15.62 40.95 15.62 56.57 0C515.9 484.7 515.9 459.3 500.3 443.7zM79.1 208c0-70.58 57.42-128 128-128s128 57.42 128 128c0 70.58-57.42 128-128 128S79.1 278.6 79.1 208z"/>
            </svg>
            </span>

        </div>

        <div>
            <table class="v-table">
                <caption v-if="caption">{{ caption }}</caption>

                <thead v-if="heads">
                <tr>
                    <th scope="col" v-for="(head, key) of heads"
                        :data-key="key"
                        v-show="(key !== 'id') || showId">
                        {{ head }}
                    </th>

                    <th scope="col" v-if="actions && actions.length">
                        Acciones
                    </th>
                </tr>
                </thead>

                <tbody>
                <tr v-for="row in rows" :data-id="row.id">
                    <td v-for="( cell, key ) of row"
                        :data-attribute="key"
                        :data-id="row.id"
                        :class="'td-' + key + '-' + row.id"
                        @dblclick="(e) => handleOnClickCellEditable(e, 'td-' + key + '-' + row.id)"
                        @focusout="handleOnFocusoutCellEditable"
                        v-show="(key !== 'id') || showId">

                        <div>
                            <div class="headTitleInTd">
                                {{heads[key]}}
                            </div>

                            <div class="td-cell-content"
                                 v-html="getCellContent( cell, key )">

                            </div>

                            <div class="td-cell-editable-hidden"
                                v-if="editable && ['float', 'integer', 'text'].includes(cellsInfo[key].type) ">
                                <input type="text" :value="cell"/>
                            </div>
                        </div>

                    </td>

                    <td v-if="actions && actions.length">
                        <div v-for="info of actions"
                             :class="getClassByActionType(info.type)">

                            <div v-if="info.type === 'delete'"
                                 :data-id="row.id"
                                 :data-url="info.url"
                                 :data-params="info.params"
                                 :data-method="info.method"
                                 @click="handleOnDelete">
                                {{ info.name }}
                            </div>

                            <div v-else-if="info.type === 'update'"
                                 @click="(e) => handleOnUpdate(e, info.url, row.id, row.slug)">
                                {{ info.name }}
                            </div>

                            <div v-else>
                                {{ info.name }}
                            </div>
                        </div>
                    </td>
                </tr>
                </tbody>

                <tfoot>
                <tr>
                    <td :colspan="Object.keys(heads).length ?? Object.keys(rows[0]).length">
                        Mostrando página {{ currentPage ?? 0 }} de
                        {{ totalPages ?? 0 }}
                        ({{ totalElements ?? 0 }} resultados)
                    </td>
                </tr>
                </tfoot>
            </table>
        </div>

        <div class="v-table-paginator">

            <span
                @click="(currentPage > 1) ? changePage(currentPage - 1) : null"
                :class="!hasBackPage ? 'disabled' : 'pointer'">
                <svg :class="'page-back' +  (!hasBackPage ? ' disabled' : '')"
                     fill="currentColor"
                     viewBox="0 0 20 20">
                    <path fill-rule="evenodd"
                          d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z"
                          clip-rule="evenodd">
                    </path>
                </svg>
            </span>

            <span v-for="page in showPages"
                  @click="(page != '...') ? changePage(page) : null"
                  :class="'page' + ((page == currentPage) ? ' current-page' : '') +  ((page == '...') ? ' page-points' : '')">
                {{ page }}
            </span>

            <span
                @click="(currentPage < totalPages) ? changePage(currentPage + 1) : null"
                :class="!hasNextPage ? 'disabled' : 'pointer'">
                <svg :class="'page-next' +  (!hasNextPage ? ' disabled' : '')"
                     fill="currentColor"
                     viewBox="0 0 20 20">
                    <path fill-rule="evenodd"
                          d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z"
                          clip-rule="evenodd">

                    </path>
                </svg>
            </span>
        </div>

    </div>
</template>


<script>
import {onBeforeMount, onMounted, ref} from 'vue';

export default {
    name:'VTableComponent',
    props:{
        title: { // Título sobre la tabla.
            type:String,
            default:'',
            required:false
        },
        caption: { // Caption de la tabla.
            type:String,
            default:'',
            required:false
        },

        url: { // Url para obtener los datos de la tabla.
            type:String,
            required:true
        },
        showId: { // Indica si muestra el ID en la tabla.
            type:Boolean,
            default:false,
            required:false
        },

        elements: { // Cantidad de elementos por página.
            type:Number,
            default:10,
            required:false
        },
        editable: { // Indica si se habilita el editar.
            type:Boolean,
            default:false,
            required:false
        },
        urlEditHot: {  // Url para editar campos en al pulsarlos.
            //type:String|null, // Falla, pero no encuentro la solución.
            default:'http:://test',
            required:false
        },
        searchable:{  // Indica si tiene input de búsqueda.
            type:Boolean,
            default:false,
            required:false
        },
        shortable:{  // Indica si permite ordenar por campos
            type:Boolean,
            default:false,
            required:false
        },
        actions:{  // JSON con las acciones para añadir botones
            type:Array,
            default:[],
            required:false

        },
        headers:{  // Cabeceras para peticiones ajax
            type:Object,
            default:{},
            required:false

        },
        csrf:{  // Token csrf para la seguridad del formulario
            required:true
        }
    },

    setup(props) {
        const rows = ref([]);  // Columnas con los datos
        const heads = ref([]);  // Títulos para columnas
        const totalPages = ref(0);  // Cantidad total de páginas
        const totalElements = ref(0);  // Cantidad total de elementos
        const currentPage = ref(0);  // Número de página actual
        const hasBackPage = ref(false);  // Indica si tiene página anterior
        const hasNextPage = ref(false);  // Indica si tiene próxima página
        const showPages = ref([]);  // Lista con las páginas a mostrar
        const cellsInfo = ref([]);  // Información de las celdas

        // Almacena timeout para buscar por ajax, así evito consultar al escribir seguido.
        const searchTimer = ref(null);

        const search = ref('');  // Cadena de búsqueda
        const orderDirection = ref('DESC');  // Modo de ordenar
        const orderBy = ref('created_at');  // Campo por el que ordenar

        // Headers para las peticiones ajax, dinamizado con prop headers
        const fetchHeaders = {
            ... {
                'Accept':
                    'application/json',
                'Content-Type':
                    'application/json',
                'X-CSRF-TOKEN':
                props.csrf
            },
            ...props.headers
        };

        /**
         * Realiza una petición ajax.
         *
         * @param url
         * @param method
         * @param params
         * @returns {Promise<any>}
         */
        const getQuery = async(url, method, params) => {
            return fetch(url, {
                    headers:fetchHeaders,
                    method:method,
                    body:JSON.stringify(params)
                }
            ).then((response) => response.json());
        };

        /**
         * Obtiene los registros para una página concreta.
         *
         * @param page Página a descargar.
         * @returns {Promise<*>}
         */
        const fetchPage = async(page) => {
            let params = {
                page:page,
                size:props.elements,
                orderBy:orderBy.value,
                orderDirection:orderDirection.value,
                search:search.value,
            };

            return getQuery(props.url, 'POST', params);
        }

        /**
         * Ejecuta una acción (eliminar, actualizar...)
         *
         * @param action Nombre de la acción
         * @returns {Promise<void>}
         */
        /*
         const executeAction = async (action) => {
         if (props.actions && props.actions.length) {
         let info = props.actions.find(ele => ele.name == action)
         }
         }
         */

        /**
         * Procesa el cambio de página.
         * @param {number} page La página a la que se está cambiando.
         * @param {boolean} reload Indica si fuerza la recarga de la página.
         */
        const changePage = (page, reload = false) => {
            // Descarto si intenta cargar la página actual.
            if(!reload && (page === currentPage.value)) {
                return null;
            }

            fetchPage(page).then( response => {
                const data = response.data;

                if(!data) {
                    console.log('No hay respuesta del servidor');
                    return null;
                }

                currentPage.value = data.currentPage;
                totalElements.value = data.totalElements;

                if(
                    (totalElements.value && (totalElements.value > 0)) &&
                    (totalElements.value <= props.elements)
                ) {
                    totalPages.value = 1;
                } else if(
                    ((totalElements.value / props.elements) > 1) &&
                    ((totalElements.value % props.elements) == 0)) {
                    totalPages.value = Math.floor(totalElements.value / props.elements) - 1;
                } else {
                    totalPages.value = Math.floor(totalElements.value /
                        props.elements) +1;
                }

                hasBackPage.value = (totalPages.value > 1) && (currentPage.value > 1);
                hasNextPage.value = (totalPages.value > 1) && (currentPage.value < totalPages.value);

                switch(true) {
                    // No hay páginas → OK
                    case 0 == totalPages.value:
                        showPages.value = ['...'];

                        break;

                    case 1 == totalPages.value:
                        showPages.value = [1]
                        break;

                    // Hay más de 8 páginas y la actual es la última → OK
                    case (8 < totalPages.value) && (currentPage.value == totalPages.value):

                        showPages.value = [1, '...'];

                        for(let i = 5; i >= 1; i--) {
                            showPages.value.push(totalPages.value - i);
                        }

                        showPages.value.push(totalPages.value);

                        break;

                    // Hay más de 8 páginas y la actual es la primera → OK
                    case (8 < totalPages.value) && (currentPage.value == 1):
                        showPages.value = [];

                        for(let i = 1; i <= 6; i++) {
                            showPages.value.push(i);
                        }

                        showPages.value.push('...');
                        showPages.value.push(totalPages.value);

                        break;

                    // Hay 8 o menos páginas → OK
                    case (8 >= totalPages.value):
                        showPages.value = [];

                        for(let i = 1; i <= totalPages.value; i++) {
                            showPages.value.push(i);
                        }
                        break;
                    default:
                        if(currentPage.value == 2) {
                            showPages.value = [1, currentPage.value, currentPage.value + 1];
                        } else if(currentPage.value == 3) {
                            showPages.value = [1, currentPage.value - 1, currentPage.value, currentPage.value + 1];
                        } else {
                            showPages.value = [1, '...', currentPage.value - 1, currentPage.value, currentPage.value + 1];
                        }

                        if((currentPage.value + 2) < totalPages.value) {
                            showPages.value.push(currentPage.value + 2);
                            showPages.value.push('...');
                            showPages.value.push(totalPages.value);
                        } else if((currentPage.value + 2) == totalPages.value) {
                            showPages.value.push(totalPages.value);
                        }

                        break;
                }

                rows.value = data.rows;
                heads.value = data.heads;
                cellsInfo.value = data.cellsInfo ?? [];
            })
        };

        /**
         * Devuelve el contenido de la celda formateado según el tipo de dato
         * indicado en el objeto cellsInfo.
         * @param cell
         * @param field
         * @returns {string|string|*}
         */
        const getCellContent = (cell, field) => {

            // TODO → obtener de controlador tipos y mirar si hay metadatos en él (button, image, etc) para saber como mostrarlo
            //let info = cellsInfo.value.find(ele => ele.key == field);
            let info = cellsInfo.value ? cellsInfo.value[field] : null;

            if(info) {
                let html = '';

                switch(info.type) {
                    case 'button':
                        html = '<button class="btn btn-primary">' + cell + '</button>';
                        break;
                    case 'image':
                        html = '<img src="' + cell + '" alt=""/>';
                        break;
                    case 'icon':
                        // TODO → preparar iconos
                        html = '<img src="' + cell + '" alt=""/>';
                        break;
                    default:
                        html =  cell;
                }

                return html;
            }


            return field === 'created_at' ? (new Date(cell)).toLocaleString() : cell;
        }

        onBeforeMount(() => {
            handleOnLoadData();
            changePage(1);
        });
        onMounted(() => {
            console.log('Component mounted.');
            handleOnFinishLoadData();
        });


        /**
         * Devuelve la clase para el tipo de acción.
         * @param action
         * @returns {string}
         */
        const getClassByActionType = (action) => {
            switch(action) {
                case 'delete':
                    return 'btn btn-red';
                case 'update':
                    return 'btn btn-blue';
                case 'show':
                    return 'btn btn-green';
                default:
                    return 'btn btn-orange';
            }
        };


        // Al obtener datos del backend, poner spinner de carga.
        const handleOnLoadData = async() => {
            // TODO preparar spinner y señales de carga.
        }

        // Cuando ha terminado de obtener datos, quito spinner de carga.
        const handleOnFinishLoadData = async() => {
            // TODO limpiar señales de carga.
        }

        /**
         * Muestra un mensaje en la tabla indicando lo que ha ocurrido.
         *
         * @param msg El mensaje a mostrar.
         * @param type El tipo del mensaje: success|error|warning
         * @returns {Promise<void>}
         */
        const showPopupMessage = async (msg, type = 'success') => {
            // TODO
            console.log('showPopupMessage() ' + type + ': ' + msg);
        }

        //
        /**
         * Maneja el evento al pulsar botón para eliminar
         *
         * @param e Recibe el evento
         * @returns {Promise<null>}
         */
        const handleOnDelete = async(e) => {
            if(!confirm('¿Estás seguro de eliminar este registro?')) {
                return null;
            }

            let btn = e.target;
            let id = btn.getAttribute('data-id');
            let url = btn.getAttribute('data-url');
            let method = btn.getAttribute('data-method');
            let params = btn.getAttribute('data-params');

            // Pongo la tabla en modo de cargar datos.
            handleOnLoadData();

            // Envío datos por AJAX al servidor
            let result = await getQuery(url, method, {...params, id:id})

            // TODO → Comprobar respuesta antes de mostrar mensaje popup
            if (result && result.deleted) {
                showPopupMessage('Se ha eliminado el registro correctamente', 'success')
            } else {
                showPopupMessage('Ha ocurrido un error al eliminar el registro', 'error')
            }

            // Actualizo la misma página para renovar datos.
            await changePage(currentPage.value, true);

            // Quita la tabla del modo cargar datos.
            handleOnFinishLoadData();
        };

        /**
         * Maneja el evento para cambiar un filtro y recarga la tabla.
         *
         * TODO → terminar ordenar por columna
         *
         * @param e
         */
        const handleChangeFilter = async (e, type) => {
            // Pongo la tabla en modo de cargar datos.
            handleOnLoadData();
            console.log('e: ' + e);
            console.log('type: ' + type);
            console.log('valor: ' + search.value);

            switch(type) {
                case 'orderBy':
                    // TODO
                    break
                case 'orderDirection':
                    // TODO
                    break
                default:
                    console.log('No coincide');
            }

            // Actualizo la página para renovar datos.
            await changePage(1, true);

            // Quita la tabla del modo cargar datos.
            handleOnFinishLoadData();
        };

        /**
         * Evento que se lanza al dejar un instante de escribir en el campo
         * de búsqueda que hay sobre la tabla.
         *
         * @param e
         */
        const handleOnWriteSearchKeyboardUp = async (e) => {

            // Si ya hay una consulta programada, la elimino
            if (searchTimer.value) {
                clearTimeout(searchTimer.value);
                searchTimer.value = null;
            }

            /**
             * Solicita la primera página con los cambios del buscador.
             *
             * @returns {Promise<void>}
             */
            async function startSearch() {
                // Pongo la tabla en modo de cargar datos.
                handleOnLoadData();

                // Actualizo la página para renovar datos.
                await changePage(1, true);

                // Quita la tabla del modo cargar datos.
                handleOnFinishLoadData();
            }

            // Si se ha pulsado Enter, se envía de momento la consulta.
            if (event.which == 13 || event.keyCode == 13) {
                startSearch();
            } else {
                // Añado intervalo a la cola para ejecutarse.
                searchTimer.value = setTimeout(startSearch, 800);
            }
        }

        /**
         * Maneja el evento al pulsar sobre los inputs de la tabla que están
         * como editables.
         *
         * @param e
         * @param nodeUniqueClass Selector de clase única para el <td>
         * @returns {Promise<void>}
         */
        const handleOnClickCellEditable = async (e, nodeUniqueClass) => {
            const target = e.target;
            const td = target.closest('.box-vue-table-component').querySelector('.' + nodeUniqueClass);
            const id = td.getAttribute('data-id');

            const boxCellContent = td.querySelector('.td-cell-content');
            const boxCellEditable = td.querySelector('.td-cell-editable-hidden');

            if (!boxCellContent || !boxCellEditable) {
                return null;
            }

            boxCellContent.classList.remove('td-cell-content');
            boxCellContent.classList.add('td-cell-content-hidden');

            boxCellEditable.classList.remove('td-cell-editable-hidden');
            boxCellEditable.classList.add('td-cell-editable');

            let input = boxCellEditable.querySelector('input');

            if (input) {
                input.focus();
            }
        };

        /**
         * Maneja la perdida de focus de una celda editable.
         *
         * @param e Evento sobre la celda pulsada.
         * @returns {Promise<void>}
         */
        const handleOnFocusoutCellEditable = async (e) => {
            const input = e.target;
            const td = input.closest('td');
            const id = td.getAttribute('data-id');

            let confirm = window.confirm('¿Quieres guardar los cambios?')

            const boxCellContent = td.querySelector('.td-cell-content-hidden');
            const boxCellEditable = td.querySelector('.td-cell-editable');

            boxCellContent.classList.remove('td-cell-content-hidden');
            boxCellContent.classList.add('td-cell-content');

            boxCellEditable.classList.remove('td-cell-editable');
            boxCellEditable.classList.add('td-cell-editable-hidden');

            if (confirm) {
                let newValue = input.value;
                let attribute = td.getAttribute('data-attribute');

                let params = {
                    action:'update',
                    id: id,
                    value:newValue,
                    attribute:attribute,
                    orderBy:orderBy.value,
                    orderDirection:orderDirection.value,
                    search:search.value,
                };

                getQuery(props.urlEditHot, 'POST', params).then(response => {

                    if(response && response.errors && response.errors.length) {

                        response.errors.forEach(error => {
                            showPopupMessage(error, 'error');
                        });

                    } else if (response && response.success && (!response.errors || !response.errors.length)) {

                        boxCellContent.textContent = getCellContent( response.value, attribute );
                        input.value = response.value;

                        showPopupMessage('Se ha modificado correctamente', 'success');

                    }

                });
            } else {
                input.focus();
            }

        }

        /**
         * Manejador para pulsaciones sobre el botón de actualizar.
         *
         * @param e Evento
         * @param url Ruta con los parámetros sin dinamizar
         * @param id Id del elemento
         * @param slug Slug del elemento
         */
        const handleOnUpdate = (e, url, id, slug) => {
            let urlDecoded = decodeURI(url);

            let urlClean = urlDecoded.replace(/\[id\]/ig, id)
                                .replace(/\[slug\]/ig, slug);

            window.location.href = urlClean;
        }

        return {
            rows:rows,
            heads:heads,
            totalPages:totalPages,
            totalElements:totalElements,
            currentPage:currentPage,
            hasBackPage:hasBackPage,
            hasNextPage:hasNextPage,
            showPages:showPages,
            getCellContent:getCellContent,
            cellsInfo:cellsInfo,

            changePage:changePage,
            getClassByActionType:getClassByActionType,

            // Filtro
            search,

            // Eventos
            handleOnDelete,
            handleChangeFilter,
            handleOnWriteSearchKeyboardUp,
            handleOnClickCellEditable,
            handleOnFocusoutCellEditable,
            handleOnUpdate,
        }
    }

}
</script>


<style lang="scss" scoped>





.td-cell-content {

}

.td-cell-content-hidden {
    display: none;
}

.td-cell-editable {

}

.td-cell-editable-hidden {
    display: none;
}

.td-cell-editable input {
    text-align: center;
}






.v-table-box-search {
    width: 100%;
    text-align: center;
}
.v-table-box-search input {
    margin: auto;
    width: 50%;
    text-align: center;
    border-radius: 2px;
}

.v-table-box-search svg {
    margin-left: 2px;
    padding: 1px;
    width: 20px;
    cursor: pointer;
}

.v-table {
    border: 1px solid #ccc;
    border-collapse: collapse;
    margin: 0;
    padding: 0;
    width: 100%;
    table-layout: fixed;
}

.v-table caption {
    margin: 0.5rem 0 0.75rem;
    color: rgba(31, 41, 55, 0.9);
    font-size: 2.5em;
}

.v-table tfoot {
    margin: 0.2rem 0;
    font-size: 0.7rem;
}

.v-table thead tr {
    background-color: rgba(31, 41, 55, 0.9);
}

.v-table thead tr th {
    color: rgba(209, 213, 219, 0.9);
    font-weight: 700;
    font-size: 1rem;
    letter-spacing: 0.1rem;
    text-transform: uppercase;
}

.v-table tr {
    background-color: #f8f8f8;
    border: 1px solid #ddd;
    padding: .35em;
}

.v-table th,
.v-table td {
    padding: .625em;
    text-align: center;
}

.v-table-paginator {
    width: 100%;
    margin: 8px auto;
    text-align: center;
}

.v-table-paginator .page {
    display: inline-flex;
    margin-left: -1px;
    padding: 0.5rem 1rem;
    text-align: center;
    align-items: center;
    background-color: #fff;
    color: #0056b3;
    font-size: 0.9rem;
    border: 1px solid rgba(209, 213, 219, 0.8);
    line-height: 1.25rem;
    font-weight: 500;
    transition-duration: 150ms;
    transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
}

.v-table-paginator .page:hover {
    cursor: pointer;
    background-color: #0056b3;
    color: #fff;
}

.v-table-paginator .current-page {
    background-color: #0056b3;
    color: #fff;
}

.v-table-paginator .current-page:hover {
    cursor: not-allowed;
}

.v-table-paginator .page-back, .v-table-paginator .page-next {
    display: inline-flex;
    width: 1.75rem;
    height: 1.75rem;
}

.v-table-paginator .page-back {
    margin-right: 0.3rem;
}

.v-table-paginator .page-next {
    margin-left: 0.3rem;
}

.v-table-paginator .pointer:hover {
    cursor: pointer;
}

.v-table-paginator .disabled:hover {
    cursor: not-allowed;
}

.v-table-paginator .page.page-points {
    color: rgba(55, 65, 81, 0.8);
}

.v-table-paginator .page.page-points:hover {
    cursor: not-allowed;
    background-color: #fff;
    color: #0056b3;
}

/* Título de la tabla dentro del propio <td>, oculto en grandes pantallas */
.v-table .headTitleInTd {
    font-size: 1.2rem;
    font-weight: bold;
    display: none;
}

@media screen and (max-width: 600px) {
    .v-table {
        border: 0;
    }

    .v-table caption {
        font-size: 1.3em;
    }

    .v-table thead {
        border: none;
        clip: rect(0 0 0 0);
        height: 1px;
        margin: -1px;
        overflow: hidden;
        padding: 0;
        position: absolute;
        width: 1px;
    }

    .v-table tr {
        border-bottom: 3px solid #ddd;
        display: block;
        margin-bottom: .625em;
    }

    .v-table td {
        border-bottom: 1px solid #ddd;
        display: block;
        font-size: .8em;
        text-align: right;
    }

    .v-table td::before {
        content: attr(data-label);
        float: left;
        font-weight: bold;
        text-transform: uppercase;
    }

    .v-table td:last-child {
        border-bottom: 0;
    }

    .v-table .headTitleInTd {
        display: block;
        text-align: left;
    }
}


/* Botones */

.btn {
    position: relative;

    margin: 5px 3px;
    padding: 3px 5px;

    font-family: Arial;
    font-size: 12px;
    font-weight: bold;

    overflow: hidden;

    border-width: 0;
    outline: none;
    border-radius: 2px;
    box-shadow: 0 1px 4px rgba(0, 0, 0, .6);

    color: #ecf0f1;

    transition: background-color .3s;
}

.btn > * {
    position: relative;
}

.btn span {
    display: block;
    padding: 12px 24px;
}

.btn:before {
    content: "";

    position: absolute;
    top: 50%;
    left: 50%;

    display: block;
    width: 0;
    padding-top: 0;

    border-radius: 100%;

    background-color: rgba(236, 240, 241, .3);

    -webkit-transform: translate(-50%, -50%);
    -moz-transform: translate(-50%, -50%);
    -ms-transform: translate(-50%, -50%);
    -o-transform: translate(-50%, -50%);
    transform: translate(-50%, -50%);
}

.btn:active:before {
    padding-top: 120%;

    transition: width .2s ease-out, padding-top .2s ease-out;
}


.btn-orange {
    color: #ecf0f1;
    background-color: #e67e22;
}

.btn-orange:hover, .btn-orange:focus {
    background-color: #d35400;
}

.btn-red {
    color: #ecf0f1;
    background-color: #DC3545;
}

.btn-red:hover, .btn-red:focus {
    background-color: #c0392b;
}

.btn-blue {
    color: #ecf0f1;
    background-color: #0056B3;
}

.btn-blue:hover, .btn-blue:focus {
    background-color: #00056b;
}

.btn-green {
    color: #ecf0f1;
    background-color: #2ecc71;
}

.btn-green:hover, .btn-green:focus {
    background-color: #27ae60;
}

.btn-yellow {
    color: #ecf0f1;
    background-color: #e6d461;
}

.btn-yellow:hover, .btn-yellow:focus {
    background-color: #b59126;
}
</style>
