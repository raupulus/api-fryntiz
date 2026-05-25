/**
 * Componente Alpine.js para data tables.
 * Proporciona funcionalidad básica de ordenación y filtrado.
 */
export default () => ({
    search: '',
    sortColumn: '',
    sortDirection: 'asc',

    init() {
        // Inicialización del componente
    },

    setSort(column) {
        if (this.sortColumn === column) {
            this.sortDirection = this.sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            this.sortColumn = column;
            this.sortDirection = 'asc';
        }
    },
});
