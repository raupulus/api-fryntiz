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
                </tr>
                </thead>

                <tbody>
                <tr v-for="row in rows">
                    <td v-for="( cell, key ) of row">
                        {{ key  == 'created_at' ? (new Date(cell)).toLocaleString() : cell }}
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

        elements: {
            type: Number,
            default: 10,
            required: false
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

        const getQuery = async (page) => {

            return await fetch(props.url, {
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
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
                    (totalElements.value / props.elements > 1) &&
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
            })
        };

        onBeforeMount(() => {
            changePage(1);
        });
        onMounted(() => {
            console.log('Component mounted.');
        });

        return {
            rows: rows,
            heads: heads,
            totalPages: totalPages,
            totalElements: totalElements,
            currentPage: currentPage,
            hasBackPage: hasBackPage,
            hasNextPage: hasNextPage,
            showPages: showPages,

            changePage: changePage,
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

</style>
