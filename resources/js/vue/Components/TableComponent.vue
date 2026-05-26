<template>
    <div :id="tableId" class="box-vue-table-component" ref="table">

        <div class="box-popup-message-info hidden">
            <div class="popup-message-info">

            </div>
        </div>

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
                        v-show="key && ((key !== 'id') || showId) && cellsInfo && (cellsInfo[key].type !== 'hidden') ">
                        {{ head }}
                    </th>

                    <th scope="col" v-if="actions && actions.length">
                        Acciones
                    </th>
                </tr>
                </thead>

                <tbody>
                <tr v-for="row in rows" :data-id="row.id">
                    <td v-for="( title, key ) of heads"
                        :data-attribute="key"
                        :data-id="row.id"
                        :class="'td-' + key + '-' + row.id"
                        @dblclick="(e) => handleOnClickCellEditable(e, 'td-' + key + '-' + row.id)"
                        @focusout="handleOnFocusoutCellEditable"
                        v-show="key && ((key !== 'id') || showId) && cellsInfo && (cellsInfo[key].type !== 'hidden')"
                        @keyup="handleOnKeyUpCellEditable">

                        <div v-if="row[key]">
                            <div class="headTitleInTd">
                                {{key}}
                            </div>

                            <div class="td-cell-content"
                                 v-html="getCellContent( row[key], key )">

                            </div>

                            <div class="td-cell-editable-hidden"
                                v-if="editable && ['float', 'integer', 'text'].includes(cellsInfo[key].type) ">
                                <input type="text" :value="row[key]"/>
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


<script setup>
/**
 * @component TableComponent
 * @description Tabla genérica con paginación, búsqueda, ordenación y acciones
 *              configurables. Carga los datos vía AJAX desde la URL indicada
 *              en la prop `url`. Útil para listados del panel admin.
 *
 * @prop {String}  title       - Título de la tabla.
 * @prop {String}  caption     - Texto descriptivo bajo el título.
 * @prop {String}  url         - Endpoint que devuelve los datos paginados.
 * @prop {Boolean} showId      - Si muestra la columna `id`.
 * @prop {Number}  elements    - Filas por página (default 10).
 * @prop {Boolean} editable    - Habilita celdas editables in-place.
 * @prop {Boolean} searchable  - Habilita campo de búsqueda.
 * @prop {Boolean} shortable   - Habilita ordenación por columna.
 * @prop {Array}   actions     - Acciones por fila (botones).
 * @prop {Object}  headers     - Mapa columna → etiqueta.
 * @prop {String}  csrf        - Token CSRF (requerido).
 * @prop {Object}  conditions  - Filtros adicionales que se envían en cada petición.
 */
import { ref, onBeforeMount, onMounted } from 'vue';

const props = defineProps({
    title: { type: String, default: '' },
    caption: { type: String, default: '' },
    url: { type: String, required: true },
    showId: { type: Boolean, default: false },
    elements: { type: Number, default: 10 },
    editable: { type: Boolean, default: false },
    urlEditHot: { default: 'http:://test' },
    searchable: { type: Boolean, default: false },
    shortable: { type: Boolean, default: false },
    actions: { type: Array, default: () => [] },
    headers: { type: Object, default: () => ({}) },
    csrf: { required: true },
    conditions: { type: Object, default: () => ({}) },
});

const tableId = ref('table-' + Math.random().toString(36).substr(2, 9));
const rows = ref([]);
const heads = ref([]);
const totalPages = ref(0);
const totalElements = ref(0);
const currentPage = ref(0);
const hasBackPage = ref(false);
const hasNextPage = ref(false);
const showPages = ref([]);
const cellsInfo = ref([]);
const searchTimer = ref(null);
const search = ref('');
const orderDirection = ref('DESC');
const orderBy = ref('created_at');

const fetchHeaders = {
    Accept: 'application/json',
    'Content-Type': 'application/json',
    'X-CSRF-TOKEN': props.csrf,
    ...props.headers,
};

const getQuery = async (url, method, params) => {
    return fetch(url, {
        headers: fetchHeaders,
        method: method,
        body: JSON.stringify({ ...params, conditions: props.conditions }),
    }).then((response) => response.json());
};

const fetchPage = async (page) => {
    return getQuery(props.url, 'POST', {
        page: page,
        size: props.elements,
        orderBy: orderBy.value,
        orderDirection: orderDirection.value,
        search: search.value,
    });
};

const changePage = (page, reload = false) => {
    if (!reload && page === currentPage.value) return null;

    fetchPage(page).then((response) => {
        const data = response.data;
        if (!data) return null;

        currentPage.value = data.currentPage;
        totalElements.value = data.totalElements;

        if (totalElements.value && totalElements.value > 0 && totalElements.value <= props.elements) {
            totalPages.value = 1;
        } else if (totalElements.value / props.elements > 1 && totalElements.value % props.elements === 0) {
            totalPages.value = Math.floor(totalElements.value / props.elements);
        } else {
            totalPages.value = Math.floor(totalElements.value / props.elements) + 1;
        }

        hasBackPage.value = totalPages.value > 1 && currentPage.value > 1;
        hasNextPage.value = totalPages.value > 1 && currentPage.value < totalPages.value;

        switch (true) {
            case 0 === totalPages.value:
                showPages.value = ['...'];
                break;
            case 1 === totalPages.value:
                showPages.value = [1];
                break;
            case 8 < totalPages.value && currentPage.value === totalPages.value:
                showPages.value = [1, '...'];
                for (let i = 5; i >= 1; i--) showPages.value.push(totalPages.value - i);
                showPages.value.push(totalPages.value);
                break;
            case 8 < totalPages.value && currentPage.value === 1:
                showPages.value = [];
                for (let i = 1; i <= 6; i++) showPages.value.push(i);
                showPages.value.push('...');
                showPages.value.push(totalPages.value);
                break;
            case 8 >= totalPages.value:
                showPages.value = [];
                for (let i = 1; i <= totalPages.value; i++) showPages.value.push(i);
                break;
            default:
                if (currentPage.value === 2) {
                    showPages.value = [1, currentPage.value, currentPage.value + 1];
                } else if (currentPage.value === 3) {
                    showPages.value = [1, currentPage.value - 1, currentPage.value, currentPage.value + 1];
                } else {
                    showPages.value = [1, '...', currentPage.value - 1, currentPage.value, currentPage.value + 1];
                }
                if (currentPage.value + 2 < totalPages.value) {
                    showPages.value.push(currentPage.value + 2);
                    showPages.value.push('...');
                    showPages.value.push(totalPages.value);
                } else if (currentPage.value + 2 === totalPages.value) {
                    showPages.value.push(totalPages.value);
                }
                break;
        }

        rows.value = data.rows;
        heads.value = data.heads;
        cellsInfo.value = data.cellsInfo;
    });
};

const getCellContent = (cell, field) => {
    let info = cellsInfo.value ? cellsInfo.value[field] : null;

    if (info && info.type) {
        switch (info.type) {
            case 'hidden': return '';
            case 'button': return '<button class="btn btn-primary">' + cell + '</button>';
            case 'color': return '<span style="display: inline-block; width: 3rem; height: 1rem; background-color:' + cell + '"></span>';
            case 'image': return '<img src="' + cell + '" alt=""/>';
            case 'bool': return cell ? 'SI' : 'No';
            case 'integer': return !isNaN(cell) && parseInt(cell) >= 1 ? parseInt(cell).toString() : '0';
            case 'float': return !isNaN(cell) ? parseFloat(cell).toFixed(2) : cell;
            case 'icon': return '<img src="' + cell + '" alt="Icon"/>';
            case 'date':
                if (cell) { let d = new Date(cell).toLocaleDateString().split(','); return d.length ? d[0] : ''; }
                return '';
            case 'datetime': return cell ? new Date(cell).toLocaleString() : '';
            default: return cell;
        }
    }

    return field === 'created_at' ? new Date(cell).toLocaleString() : cell;
};

const getClassByActionType = (action) => {
    switch (action) {
        case 'delete': return 'btn btn-red';
        case 'update': return 'btn btn-blue';
        case 'show': return 'btn btn-green';
        default: return 'btn btn-orange';
    }
};

const handleOnLoadData = async () => {};
const handleOnFinishLoadData = async () => {};

const showPopupMessage = async (msg, type = 'success') => {
    const component = document.getElementById(tableId.value);
    const boxPopup = component.querySelector('.box-popup-message-info');
    const popupMessage = boxPopup.querySelector('.popup-message-info');

    popupMessage.classList.add(type);
    boxPopup.classList.remove('hidden');
    popupMessage.textContent = msg;

    setTimeout(() => {
        boxPopup.classList.add('hidden');
        popupMessage.classList.remove(type);
        popupMessage.textContent = '';
    }, type === 'success' ? 3000 : 8000);
};

const handleOnDelete = async (e) => {
    if (!confirm('¿Estás seguro de eliminar este registro?')) return null;

    let btn = e.target;
    let id = btn.getAttribute('data-id');
    let url = btn.getAttribute('data-url');
    let method = btn.getAttribute('data-method');
    let params = btn.getAttribute('data-params');

    await handleOnLoadData();
    let result = await getQuery(url, method, { ...params, id: id });

    if (result && result.deleted) {
        await showPopupMessage('Se ha eliminado el registro correctamente', 'success');
    } else {
        await showPopupMessage('Ha ocurrido un error al eliminar el registro', 'error');
    }

    await changePage(currentPage.value, true);
    await handleOnFinishLoadData();
};

const handleChangeFilter = async (e, type) => {
    handleOnLoadData();
    await changePage(1, true);
    handleOnFinishLoadData();
};

const handleOnWriteSearchKeyboardUp = async (e) => {
    if (searchTimer.value) {
        clearTimeout(searchTimer.value);
        searchTimer.value = null;
    }

    async function startSearch() {
        handleOnLoadData();
        await changePage(1, true);
        handleOnFinishLoadData();
    }

    if (e.which == 13 || e.keyCode == 13) {
        startSearch();
    } else {
        searchTimer.value = setTimeout(startSearch, 800);
    }
};

const handleOnClickCellEditable = async (e, nodeUniqueClass) => {
    const target = e.target;
    const td = target.closest('.box-vue-table-component').querySelector('.' + nodeUniqueClass);

    const boxCellContent = td.querySelector('.td-cell-content');
    const boxCellEditable = td.querySelector('.td-cell-editable-hidden');

    if (!boxCellContent || !boxCellEditable) return null;

    boxCellContent.classList.remove('td-cell-content');
    boxCellContent.classList.add('td-cell-content-hidden');
    boxCellEditable.classList.remove('td-cell-editable-hidden');
    boxCellEditable.classList.add('td-cell-editable');

    let input = boxCellEditable.querySelector('input');
    if (input) input.focus();
};

const handleOnFocusoutCellEditable = async (e) => {
    const input = e.target;
    const td = input.closest('td');
    const id = td.getAttribute('data-id');

    let confirmSave = window.confirm('¿Quieres guardar los cambios?');

    const boxCellContent = td.querySelector('.td-cell-content-hidden');
    const boxCellEditable = td.querySelector('.td-cell-editable');

    boxCellContent.classList.remove('td-cell-content-hidden');
    boxCellContent.classList.add('td-cell-content');
    boxCellEditable.classList.remove('td-cell-editable');
    boxCellEditable.classList.add('td-cell-editable-hidden');

    if (confirmSave) {
        let newValue = input.value;
        let attribute = td.getAttribute('data-attribute');

        getQuery(props.urlEditHot, 'POST', {
            action: 'update', id, value: newValue, attribute,
            orderBy: orderBy.value, orderDirection: orderDirection.value, search: search.value,
        }).then((response) => {
            if (response && response.errors && response.errors.length) {
                response.errors.forEach((error) => showPopupMessage(error, 'error'));
            } else if (response && response.success) {
                boxCellContent.textContent = getCellContent(response.value, attribute);
                input.value = response.value;
                showPopupMessage('Se ha modificado correctamente', 'success');
            }
        });
    } else {
        input.focus();
    }
};

const handleOnKeyUpCellEditable = async (e) => {
    const input = e.target;
    const component = input.closest('.box-vue-table-component');
    const inputSearch = component.querySelector('input[type="search"]');

    if (e.which == 13 || e.keyCode == 13 || e.which == 27 || e.keyCode == 27) {
        inputSearch.focus();
    }
};

const handleOnUpdate = (e, url, id, slug) => {
    let urlClean = decodeURI(url).replace(/\[id\]/ig, id).replace(/\[slug\]/ig, slug);
    window.location.href = urlClean;
};

onBeforeMount(() => {
    handleOnLoadData();
    changePage(1);
});

onMounted(() => {
    handleOnFinishLoadData();
});
</script>


<style scoped>

/* Modal messages popup */

.box-popup-message-info {
    position: absolute;
    min-width: 120px;
    max-width: 800px;
    min-height: 50px;
    top: 120px;
    left: 15px;
    margin: auto;
    border-radius: 10px;
    opacity: 0.8;
}

.box-popup-message-info.hidden {
    display: none;
}

.popup-message-info {
    width: 100%;
    height: 100%;
    padding: 15px;
    text-align: center;
    background-color: yellow;
    color: #fff;
    font-size: 1.2rem;
    font-weight: bold;
    border-radius: 10px;
}
.popup-message-info.success {
    background-color: #2ecc71;
}
.popup-message-info.error {
    background-color: #DC3545;
}
.popup-message-info.warning {
    background-color: #e6d461;
}
.popup-message-info.primary {
    background-color: #0056b3;
}


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
