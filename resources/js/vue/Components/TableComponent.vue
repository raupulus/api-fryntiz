 <template>
    <div class="box-vue-table-component">
        <div>
            <table class="v-table">
                <caption v-if="title">{{ title }}</caption>

                <thead v-if="heads">
                <tr>
                    <th scope="col" v-for="(head, key) of heads">
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
                        v-html="getCellContent( cell, key )">
                    </td>

                    <td v-if="actions && actions.length">
                        <span v-for="info of actions">
                            <span :class="getClassByActionType(info.type)">
                                {{info.name}}
                            </span>
                        </span>
                    </td>
                </tr>
                </tbody>

                <tfoot>
                <tr>
                    <td :colspan="Object.keys(heads).length ?? Object.keys(rows[0]).length">
                        Mostrando página {{ currentPage ?? 0 }} de {{ totalPages ?? 0 }}
                        ({{ totalElements ?? 0 }} resultados)
                    </td>
                </tr>
                </tfoot>
            </table>
        </div>

        <div class="v-table-paginator">

            <span @click="(currentPage > 1) ? changePage(currentPage - 1) : null"
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
                {{page}}
            </span>

            <span @click="(currentPage < totalPages) ? changePage(currentPage + 1) : null"
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
    name: 'VTableComponent',
    props: {
        title: {
            type: String,
            default: '',
            required: false
        },

        url: {
            type: String,
            required: true
        },
        showId: { // Indica si muestra el ID en la tabla
            type: Boolean,
            default: false,
            required: false
        },

        elements: {
            type: Number,
            default: 10,
            required: false
        },
        editable: {
            type: Boolean,
            default: false,
            required: false
        },
        actions: {
            type: Array,
            default: [],
            required: false

        }
    },

    setup(props) {
        // TODO → Cuando viene un id del servidor, usar



        console.log(props.actions);

        const rows = ref([]);  // Columnas con los datos
        const heads = ref([]);  // Títulos para columnas
        const totalPages = ref(0);  // Cantidad total de páginas
        const totalElements = ref(0);  // Cantidad total de elementos
        const currentPage = ref(0);  // Número de página actual
        const hasBackPage = ref(false);  // Indica si tiene página anterior
        const hasNextPage = ref(false);  // Indica si tiene próxima página
        const showPages = ref([]);  // Lista con las páginas a mostrar
        const cellsInfo = ref([]);  // Información de las celdas

        /**
         * Obtiene la consulta para la página recibida.
         *
         * @param page
         * @returns {Promise<any>}
         */
        const getQuery = async (page) => {

            let csrfToken = document.head.querySelector('meta[name="csrf-token"]')
                ? document.head.querySelector('meta[name="csrf-token"]').content : '';


            return await fetch(props.url, {
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                method: "POST",
                body: JSON.stringify({
                    page: page,
                    size: props.elements,
                    orderBy: 'created_at',
                    orderDirection: 'DESC'
                })
            }
            ).then((response) => response.json());
        };

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
         */
        const changePage = (page) => {
            // Descarto si intenta cargar la página actual.
            if (page == currentPage.value) {
                return null;
            }

            getQuery(page).then((response) => {
                const data = response.data;

                if (! data) {
                    console.log('No hay respuesta del servidor');
                    return null;
                }

                currentPage.value = data.currentPage;
                totalElements.value = data.totalElements;

                if (
                    (totalElements.value && (totalElements.value > 0)) &&
                    (totalElements.value <= props.elements)
                ) {
                    totalPages.value = 1;
                } else if (
                    ((totalElements.value / props.elements) > 1) &&
                    ((totalElements.value % props.elements) == 0)) {
                    totalPages.value = Math.floor(totalElements.value / props.elements) - 1;
                } else {
                    totalPages.value = Math.floor(totalElements.value / props.elements);
                }

                hasBackPage.value = (totalPages.value > 1) && (currentPage.value > 1);
                hasNextPage.value = (totalPages.value > 1) && (currentPage.value < totalPages.value);

                switch (true) {
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

                        for (let i = 5; i >= 1; i--) {
                            showPages.value.push(totalPages.value - i);
                        }

                        showPages.value.push(totalPages.value);

                        break;

                    // Hay más de 8 páginas y la actual es la primera → OK
                    case (8 < totalPages.value) && (currentPage.value == 1):
                        showPages.value = [];

                        for (let i = 1; i <= 6; i++) {
                            showPages.value.push(i);
                        }

                        showPages.value.push('...');
                        showPages.value.push(totalPages.value);

                        break;

                    // Hay 8 o menos páginas → OK
                    case (8 >= totalPages.value):
                        showPages.value = [];

                        for (let i = 1; i <= totalPages.value; i++) {
                            showPages.value.push(i);
                        }
                        break;
                    default:
                        if (currentPage.value == 2) {
                            showPages.value = [1, currentPage.value, currentPage.value + 1];
                        } else if (currentPage.value == 3) {
                            showPages.value = [1, currentPage.value - 1, currentPage.value, currentPage.value + 1];
                        } else {
                            showPages.value = [1, '...', currentPage.value - 1, currentPage.value, currentPage.value + 1];
                        }

                        if ((currentPage.value + 2) < totalPages.value) {
                            showPages.value.push(currentPage.value + 2);
                            showPages.value.push('...');
                            showPages.value.push(totalPages.value);
                        } else if ((currentPage.value + 2) == totalPages.value) {
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

                /*
                console.log('s:', cellsInfo.value ?
                    cellsInfo.value[field] : '');

            Object.keys(cellsInfo.value).forEach(key => {
                const item = cellsInfo.value[key]
                console.log('i:', item.value);

                if (item == field) {
                    info = item

                }
            })
*/

/*

            Object.keys(cellsInfo.value).forEach(key => {
                console.log('k:', cellsInfo.value[key].key);

                if (key == field) {
                    info == key;
                }
            });
 */

            //console.log(cellsInfo.value);
            //console.log(info, info ? info.type : null);

            //console.log(cellsInfo.value.keys);


            //console.log(field);
            //console.log(info ? info.type == 'icon' : null);

                if (info) {
                    switch (info.type) {
                        case 'button':
                            return '<button class="btn btn-primary">' + cell + '</button>';
                        case 'image':
                            return '<img src="' + cell +  '" alt=""/>';
                        case 'icon':
                            // TODO → preparar iconos
                            return '<img src="' + cell +  '" alt=""/>';
                        default:
                            return cell;
                    }
                }


            return field  == 'created_at' ? (new Date(cell)).toLocaleString() : cell;
        }

        onBeforeMount(() => {
            changePage(1);
        });
        onMounted(() => {
            console.log('Component mounted.');
        });

        const getClassByActionType = (action) => {
            switch(action) {
                case 'delete':
                    return 'btn-font btn-red';
                case 'update':
                    return 'btn-font btn-blue';
                case 'show':
                    return 'btn-font btn-yellow';
                default:
                    return 'btn-font btn-blue';
            }
        };

        return {
            rows: rows,
            heads: heads,
            totalPages: totalPages,
            totalElements: totalElements,
            currentPage: currentPage,
            hasBackPage: hasBackPage,
            hasNextPage: hasNextPage,
            showPages: showPages,
            getCellContent: getCellContent,
            cellsInfo: cellsInfo,

            changePage: changePage,
            getClassByActionType: getClassByActionType,
        }
    }

}
</script>


<style lang="scss" scoped>
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
        /*
        * aria-label has no advantage, it won't be read inside a table
        content: attr(aria-label);
        */
        content: attr(data-label);
        float: left;
        font-weight: bold;
        text-transform: uppercase;
    }

    .v-table td:last-child {
        border-bottom: 0;
    }
}



/* Botones */


.btn-blue {
    margin: 2px;
    box-shadow: 0px 10px 14px -7px #403c40;
    background:linear-gradient(to bottom, #00056b 5%, #00044a 100%);
    background-color:#00056b;
    border-radius:8px;
    display:inline-block;
    cursor:pointer;
    color:#ffffff;

    padding:5px 12px;
    text-decoration:none;
    text-shadow:0px 1px 0px #3d768a;
}
.btn-blue:hover {
    background:linear-gradient(to bottom, #00044a 5%, #00056b 100%);
    background-color:#00044a;
}
.btn-blue:active {
    position:relative;
    top:1px;
}

.btn-red {
    margin: 2px;
    box-shadow: 0px 10px 14px -7px #403c40;
    background:linear-gradient(to bottom, #ed2828 5%, #820000 100%);
    background-color:#ed2828;
    border-radius:8px;
    display:inline-block;
    cursor:pointer;
    color:#ffffff;

    padding:5px 12px;
    text-decoration:none;
    text-shadow:0px 1px 0px #3d768a;
}
.btn-red:hover {
    background:linear-gradient(to bottom, #820000 5%, #ed2828 100%);
    background-color:#820000;
}
.btn-red:active {
    position:relative;
    top:1px;
}

.btn-yellow {
    margin: 2px;
    box-shadow: 0px 10px 14px -7px #403c40;
    background:linear-gradient(to bottom, #e6d461 5%, #b59126 100%);
    background-color:#e6d461;
    border-radius:8px;
    display:inline-block;
    cursor:pointer;
    color:#ffffff;

    padding:5px 12px;
    text-decoration:none;
    text-shadow:0px 1px 0px #3d768a;
}
.btn-yellow:hover {
    background:linear-gradient(to bottom, #b59126 5%, #e6d461 100%);
    background-color:#b59126;
}
.btn-yellow:active {
    position:relative;
    top:1px;
}

.btn-font {
    font-family:Arial;
    font-size:12px;
    font-weight:bold;
}

</style>
