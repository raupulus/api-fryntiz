import Alpine from 'alpinejs';
import dataTable from './components/data-table.js';

window.Alpine = Alpine;
Alpine.data('dataTable', dataTable);
Alpine.start();
