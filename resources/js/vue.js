/**
 * Vue 3 — Montaje de componentes Vue en páginas específicas.
 * Este archivo se carga con @vite('resources/js/vue.js') solo en las
 * vistas que lo necesiten.
 */

import { createApp } from 'vue';
import ChipionaWeatherComponent from './vue/Components/ChipionaWeatherComponent.vue';

/**
 * Montar Widget del clima si existe el contenedor.
 */
const weatherEl = document.getElementById('app-weather-chipiona');

if (weatherEl) {
    let initialData = null;
    const initialDataScript = document.getElementById('weather-widget-initial-data');

    if (initialDataScript) {
        try {
            initialData = JSON.parse(initialDataScript.textContent || 'null');
        } catch {
            initialData = null;
        }
    }

    const weatherApp = createApp(ChipionaWeatherComponent, {
        apiBaseUrl: weatherEl.dataset.apiBaseUrl ?? '',
        apiPath: weatherEl.dataset.apiPath ?? 'weatherstation/widget',
        zone: weatherEl.dataset.zone ?? '',
        locationType: weatherEl.dataset.locationType ?? '',
        station: weatherEl.dataset.station ?? '',
        initialData,
    });
    weatherApp.mount(weatherEl);
}
