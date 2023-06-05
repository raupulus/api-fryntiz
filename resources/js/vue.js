/**
 * The following block of code may be used to automatically register your
 * Vue components. It will recursively scan this directory for the Vue
 * components and automatically register them with their "basename".
 *
 * Eg. ./components/ExampleComponent.vue -> <example-component></example-component>
 */

// const files = require.context('./', true, /\.vue$/i)
// files.keys().map(key => Vue.component(key.split('/').pop().split('.')[0], files(key).default))

import { createApp } from 'vue'

import VueAxios from 'vue-axios';

// PrimeVue
import PrimeVue from 'primevue/config';
import Chart from 'primevue/chart';

/*
import { createRouter, createWebHashHistory, RouteRecordRaw } from 'vue-router'

const routes = [

]

const router = createRouter({
    history: createWebHashHistory(),
    routes
})

*/

/*
 Vue.use(VueAxios, {
 enableSettings:() => {
 axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
 }
 });
 */


const app = createApp({
    use: [
        PrimeVue,
        VueAxios,
    ],
    components: {
        VueAxios,
        PrimeVue: {ripple: true},
        Chart,
        //router
    }
});

import TableComponent from './vue/Components/TableComponent.vue';
import ImageCropper from "./vue/Components/ImageCropper.vue";

app.component('v-table-component', TableComponent)
app.component('v-image-cropper', ImageCropper)

app.component('v-chipiona-weather-component', require('./vue/Components/ChipionaWeatherComponent.vue').default);

app.mount('#app');
