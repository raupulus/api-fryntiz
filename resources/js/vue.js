/**
 * Vue 3 — Montaje de componentes Vue en páginas específicas.
 * Este archivo se carga con @vite('resources/js/vue.js') solo en las
 * vistas que lo necesiten.
 */

import { createApp } from 'vue';
import ChipionaWeatherComponent from './vue/Components/ChipionaWeatherComponent.vue';
import TableComponent from './vue/Components/TableComponent.vue';

/**
 * Montar Widget del clima si existe el contenedor.
 */
const weatherEl = document.getElementById('app-weather-chipiona');

if (weatherEl) {
    const weatherApp = createApp(ChipionaWeatherComponent, {
        apiBaseUrl: weatherEl.dataset.apiBaseUrl ?? '',
        apiPath: weatherEl.dataset.apiPath ?? 'api/v2/weatherstation/station',
        station: weatherEl.dataset.station ?? '',
    });
    weatherApp.mount(weatherEl);
}

/**
 * Montar Tabla de datos si existe el contenedor.
 */
const sensorTableEl = document.getElementById('app-sensor-table');

if (sensorTableEl) {
    const tableApp = createApp(TableComponent, {
        url: sensorTableEl.dataset.apiUrl,
        title: sensorTableEl.dataset.title,
        csrf: sensorTableEl.dataset.csrf,
        elements: 15,
        searchable: true,
    });
    tableApp.mount(sensorTableEl);
}
