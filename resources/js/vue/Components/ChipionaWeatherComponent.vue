<template>
    <div class="box-weather-chipiona">
        <div class="weather-container">
            <div class="box-resume">
                <div class="box-resume-center">
                    <div class="resume-gradient"></div>

                    <div>
                        <div class="resume-container-date">
                            <div class="resume-inline resume-location">
                                <span class="icon icon-location"></span>
                                Chipiona, Es
                            </div>
                            <div class="resume-inline resume-date-dayname">
                                {{ instant.day_name }}
                            </div>
                            <div class="resume-inline resume-date-day">
                                {{ instant.date_human_format }}
                            </div>
                        </div>

                        <div class="resume-weather-container">
                            <!-- Info general -->
                            <div v-show="navigation.info" class="navigation navigation-info-box">
                                <div class="navigation-info-left">
                                    <div>
                                        <span class="icon icon-sun"></span>
                                        <span class="resume-weather-desc">
                                            {{ instant.day_status }} {{ instant.time }}
                                        </span>
                                    </div>
                                </div>
                                <div>
                                    <span class="resume-weather-temp">
                                        {{ roundTo2(info.temperature) }} ºC
                                    </span>
                                </div>
                                <div class="mt-5">
                                    <span class="icon icon-humidity color-yellow"></span>
                                    {{ roundTo2(info.humidity) }} %
                                </div>
                                <div class="mt-5">
                                    <span class="icon icon-pressure color-yellow"></span>
                                    {{ roundTo2(info.pressure) }} mb
                                </div>
                                <div class="mt-5">
                                    <span class="icon icon-lightning color-yellow"></span>
                                    {{ lightning.quantityLastTenMinutes }}
                                </div>
                            </div>

                            <!-- Viento -->
                            <div v-show="navigation.wind" class="navigation">
                                <span class="icon icon-wind color-blue"></span> Viento
                                <h1 class="resume-weather-temp">{{ roundTo2(wind.average) }} km/h</h1>
                                <h3 class="resume-weather-desc">
                                    Min: {{ roundTo2(wind.min) }} km/h<br/>
                                    Max: {{ roundTo2(wind.max) }} km/h
                                </h3>
                            </div>

                            <!-- TVOC -->
                            <div v-show="navigation.tvoc" class="navigation">
                                <span class="icon icon-tvoc color-yellow"></span> Calidad del Aire
                                <h1 class="resume-weather-temp">{{ roundTo2(air_quality.quality) }} %</h1>
                                <h3 class="resume-weather-desc">
                                    TVOC: {{ roundTo2(air_quality.tvoc) }}<br/>
                                    CO2-ECO2: {{ roundTo2(air_quality.co2_eco2) }}
                                </h3>
                            </div>

                            <!-- UV -->
                            <div v-show="navigation.light" class="navigation">
                                <span class="icon icon-uv color-orange"></span> Radiación Solar
                                <h1 class="resume-weather-temp">{{ roundTo2(light.index) }} UV</h1>
                                <h3 class="resume-weather-desc">
                                    UVA: {{ roundTo2(light.uva) }}<br/>
                                    UVB: {{ roundTo2(light.uvb) }}
                                </h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="box-selectors m-0 p-0">
                <div class="selectors-container">
                    <ul class="selector-list">
                        <li :class="{ active: navigation.info }" @click="menuSelect('info')">
                            <span class="icon icon-info"></span>
                            <span class="selector-element">General</span>
                        </li>
                        <li :class="{ active: navigation.wind }" @click="menuSelect('wind')">
                            <span class="icon icon-wind"></span>
                            <span class="selector-element">Viento</span>
                        </li>
                        <li :class="{ active: navigation.tvoc }" @click="menuSelect('tvoc')">
                            <span class="icon icon-tvoc"></span>
                            <span class="selector-element">TVOC</span>
                        </li>
                        <li :class="{ active: navigation.light }" @click="menuSelect('light')">
                            <span class="icon icon-uv"></span>
                            <span class="selector-element">UV</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
/**
 * @component ChipionaWeatherComponent
 * @description Widget en tiempo casi-real que muestra los datos de la
 *              estación meteorológica de Chipiona (información instantánea,
 *              viento, calidad del aire y luz). Se refresca periódicamente
 *              llamando al endpoint `apiBaseUrl + apiPath`.
 *
 * @prop {String} apiBaseUrl - Base URL del backend que sirve los datos.
 * @prop {String} apiPath    - Ruta relativa al endpoint de resumen (default 'api/v2/weatherstation/resume').
 */
import { ref, onBeforeMount, onBeforeUnmount } from 'vue';

const props = defineProps({
    apiBaseUrl: {
        type: String,
        default: '',
    },
    apiPath: {
        type: String,
        default: 'api/v2/weatherstation/resume',
    },
});

const navigation = ref({ info: true, wind: false, tvoc: false, light: false });
const instant = ref({ day_name: '', date_human_format: '', time: '', day_status: '' });
const info = ref({ temperature: 0, humidity: 0, pressure: 0 });
const wind = ref({ average: 0, min: 0, max: 0, direction: 'N' });
const air_quality = ref({ quality: 100, co2_eco2: 0, tvoc: 0 });
const light = ref({ light: 0, index: 0, uva: 0, uvb: 0 });
const lightning = ref({ last: '', quantityLastTenMinutes: 0 });

let intervalId = null;

const roundTo2 = (num) => Math.round((num ?? 0) * 100) / 100;

const menuSelect = (item) => {
    Object.keys(navigation.value).forEach((key) => {
        navigation.value[key] = key === item;
    });
};

const getApiData = async () => {
    try {
        const url = `${props.apiBaseUrl}/${props.apiPath}`;
        const response = await fetch(url, {
            headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
            method: 'GET',
            mode: 'cors',
        });
        const json = await response.json();
        // La API V2 envuelve la carga útil en { success, message, data }.
        const data = json?.data ?? json;

        instant.value = data.instant ?? instant.value;
        info.value = { temperature: data.temperature, humidity: data.humidity, pressure: data.pressure };
        wind.value = { direction: data.wind_direction, average: data.wind_average, min: data.wind_min, max: data.wind_max };
        light.value = { light: data.light, index: data.uv_index, uva: data.uva, uvb: data.uvb };
        air_quality.value = { quality: data.air_quality, tvoc: data.tvoc, co2_eco2: data.eco2 };
        lightning.value = { last: data.last_lightning_at, quantityLastTenMinutes: data.lightningQuantityLastTenMinutes };
    } catch (error) {
        console.error('Error al obtener datos desde la API:', error);
    }
};

onBeforeMount(() => {
    getApiData();
    intervalId = setInterval(getApiData, 65000);
});

onBeforeUnmount(() => {
    if (intervalId) clearInterval(intervalId);
});
</script>

<style scoped>
.m-0 { margin: 0; }
.p-0 { padding: 0; }

#box-weather-chipiona { margin: 0; width: 100%; }

.weather-container {
    margin: auto;
    padding: 1vh;
    max-width: 500px;
    color: #ffffff;
}

/* Iconos */
.icon {
    display: inline-block;
    margin-bottom: -8px;
    width: 30px;
    height: 30px;
    background-repeat: no-repeat;
    background-size: cover;
}

.icon-sun {
    background-image: url("data:image/svg+xml,%3C%3Fxml version='1.0' encoding='utf-8'%3F%3E%3C!-- Generated by IcoMoon.io --%3E%3C!DOCTYPE svg PUBLIC '-//W3C//DTD SVG 1.1//EN' 'http://www.w3.org/Graphics/SVG/1.1/DTD/svg11.dtd'%3E%3Csvg version='1.1' xmlns='http://www.w3.org/2000/svg' xmlns:xlink='http://www.w3.org/1999/xlink' width='512' height='512' viewBox='0 0 512 512'%3E%3Cg%3E%3C/g%3E%3Cpath d='M377.139 259.492c0 66.637-54.020 120.658-120.658 120.658-66.637 0-120.658-54.021-120.658-120.658 0-66.637 54.020-120.658 120.658-120.658 66.637 0 120.658 54.020 120.658 120.658z' fill='%23000000' /%3E%3Cpath d='M228.352 100.669l30.27-77.906 25.979 77.906z' fill='%23000000' /%3E%3Cpath d='M228.352 411.341l30.27 77.895 25.979-77.895z' fill='%23000000' /%3E%3Cpath d='M100.659 287.601l-77.895-30.29 77.895-25.959z' fill='%23000000' /%3E%3Cpath d='M411.361 287.601l77.875-30.29-77.875-25.959z' fill='%23000000' /%3E%3Cpath d='M126.597 165.703l-33.659-76.472 73.442 36.7z' fill='%23000000' /%3E%3Cpath d='M346.276 385.423l76.524 33.639-36.741-73.442z' fill='%23000000' /%3E%3Cpath d='M168.499 388.199l-76.493 33.639 36.72-73.442z' fill='%23000000' /%3E%3Cpath d='M388.199 168.499l33.618-76.513-73.4 36.751z' fill='%23000000' /%3E%3C/svg%3E%0A");
    filter: invert(87%) sepia(31%) saturate(5000%) hue-rotate(346deg) brightness(104%) contrast(97%);
}

.icon-location {
    width: 20px;
    height: 20px;
    background-image: url("data:image/svg+xml,%3C%3Fxml version='1.0' encoding='iso-8859-1'%3F%3E%3Csvg version='1.1' xmlns='http://www.w3.org/2000/svg' width='425.963px' height='425.963px' viewBox='0 0 425.963 425.963'%3E%3Cg%3E%3Cpath d='M213.285,0h-0.608C139.114,0,79.268,59.826,79.268,133.361c0,48.202,21.952,111.817,65.246,189.081 c32.098,57.281,64.646,101.152,64.972,101.588c0.906,1.217,2.334,1.934,3.847,1.934c0.043,0,0.087,0,0.13-0.002 c1.561-0.043,3.002-0.842,3.868-2.143c0.321-0.486,32.637-49.287,64.517-108.976c43.03-80.563,64.848-141.624,64.848-181.482 C346.693,59.825,286.846,0,213.285,0z M274.865,136.62c0,34.124-27.761,61.884-61.885,61.884 c-34.123,0-61.884-27.761-61.884-61.884s27.761-61.884,61.884-61.884C247.104,74.736,274.865,102.497,274.865,136.62z'/%3E%3C/g%3E%3C/svg%3E%0A");
    filter: invert(37%) sepia(51%) saturate(3000%) hue-rotate(346deg) brightness(104%) contrast(97%);
}

.icon-info {
    background-image: url("data:image/svg+xml,%3C%3Fxml version='1.0' encoding='iso-8859-1'%3F%3E%3Csvg version='1.1' xmlns='http://www.w3.org/2000/svg' width='496.304px' height='496.303px' viewBox='0 0 496.304 496.303'%3E%3Cg%3E%3Cpath d='M248.146,0C111.314,0,0,111.321,0,248.152c0,136.829,111.314,248.151,248.146,248.151 c136.835,0,248.158-111.322,248.158-248.151C496.304,111.321,384.98,0,248.146,0z M248.146,472.093 c-123.473,0-223.935-100.459-223.935-223.941c0-123.479,100.462-223.941,223.935-223.941 c123.488,0,223.947,100.462,223.947,223.941C472.093,371.634,371.634,472.093,248.146,472.093z M319.536,383.42v32.852 c0,1.383-1.123,2.494-2.482,2.494H196.45c-1.374,0-2.482-1.117-2.482-2.494V383.42c0-1.372,1.114-2.482,2.482-2.482h34.744V205.831 h-35.101c-1.375,0-2.468-1.111-2.468-2.474v-33.6c0-1.38,1.1-2.479,2.468-2.479h82.293c1.371,0,2.482,1.105,2.482,2.479v211.181 h36.186C318.413,380.938,319.536,382.048,319.536,383.42z M209.93,105.927c0-20.895,16.929-37.829,37.829-37.829 c20.886,0,37.826,16.935,37.826,37.829s-16.94,37.829-37.826,37.829C226.853,143.756,209.93,126.822,209.93,105.927z'/%3E%3C/g%3E%3C/svg%3E%0A");
}

.icon-wind {
    background-image: url("data:image/svg+xml,%3Csvg version='1.1' xmlns='http://www.w3.org/2000/svg' viewBox='0 0 331.309 331.309'%3E%3Cg%3E%3Cg%3E%3Cg%3E%3Cpath d='M49.425,143.993h138.344c19.952,0,36.184-16.232,36.184-36.184c0-19.952-16.231-36.185-36.184-36.185 c-19.953,0-36.185,16.232-36.185,36.185c0,0.737,0.099,1.45,0.266,2.136c0.96,3.938,4.501,6.864,8.734,6.864c4.971,0,9-4.029,9-9 c0-10.027,8.157-18.185,18.185-18.185c10.026,0,18.184,8.158,18.184,18.185c0,10.027-8.157,18.184-18.184,18.184H86.227H49.425 c-4.971,0-9,4.029-9,9S44.454,143.993,49.425,143.993z'/%3E%3Cpath d='M270.532,187.315H132.188c-4.971,0-9,4.029-9,9s4.029,9,9,9h36.803h101.541c10.027,0,18.185,8.157,18.185,18.184 c0,10.027-8.157,18.185-18.185,18.185c-10.028,0-18.185-8.158-18.185-18.185c0-4.971-4.029-9-9-9 c-4.233,0-7.774,2.926-8.734,6.864c-0.167,0.686-0.266,1.399-0.266,2.136c0,19.952,16.232,36.185,36.185,36.185 s36.185-16.232,36.185-36.185S290.484,187.315,270.532,187.315z'/%3E%3Cpath d='M282.81,79.094c-26.743,0-48.5,21.756-48.5,48.499c0,4.971,4.029,9,9,9s9-4.029,9-9c0-16.817,13.683-30.499,30.5-30.499 c16.817,0,30.499,13.682,30.499,30.499c0,16.817-13.682,30.5-30.499,30.5H9c-4.971,0-9,4.029-9,9c0,4.971,4.029,9,9,9h273.81 c26.742,0,48.499-21.757,48.499-48.5C331.309,100.849,309.552,79.094,282.81,79.094z'/%3E%3Cpath d='M104.743,187.315H87.785c-4.971,0-9,4.029-9,9s4.029,9,9,9h16.958c4.971,0,9-4.029,9-9S109.714,187.315,104.743,187.315z'/%3E%3Cpath d='M21.987,143.993h3.334c4.971,0,9-4.029,9-9s-4.029-9-9-9h-3.334c-4.971,0-9,4.029-9,9S17.017,143.993,21.987,143.993z'/%3E%3C/g%3E%3C/g%3E%3C/g%3E%3C/svg%3E%0A");
}

.icon-wind.color-blue {
    filter: invert(67%) sepia(10%) saturate(3000%) hue-rotate(156deg) brightness(104%) contrast(67%);
}

.icon-tvoc {
    background-image: url("data:image/svg+xml,%3Csvg version='1.1' xmlns='http://www.w3.org/2000/svg' width='439.685px' height='439.685px' viewBox='0 0 439.685 439.685'%3E%3Cg%3E%3Cg%3E%3Cpath d='M384.683,165.281c-11.13-70.038-71.789-123.575-144.958-123.575c-66.203,0-122.161,43.827-140.464,104.048 C42.424,158.683,0,209.523,0,270.275c0,70.528,57.175,127.703,127.704,127.703H311.98c70.528,0,127.704-57.175,127.704-127.703 C439.686,226.762,417.917,188.339,384.683,165.281z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E%0A");
}

.icon-tvoc.color-yellow {
    filter: invert(87%) sepia(71%) saturate(6000%) hue-rotate(346deg) brightness(104%) contrast(97%);
}

.icon-uv {
    background-image: url("data:image/svg+xml,%3Csvg version='1.1' xmlns='http://www.w3.org/2000/svg' viewBox='0 0 512 512'%3E%3Cg%3E%3Cg%3E%3Cpath d='M437.019,74.981C388.667,26.628,324.379,0,256,0S123.333,26.628,74.981,74.981C26.628,123.332,0,187.621,0,256 s26.628,132.668,74.981,181.019C123.333,485.372,187.621,512,256,512s132.667-26.628,181.019-74.981 C485.372,388.668,512,324.379,512,256S485.372,123.332,437.019,74.981z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E%0A");
}

.icon-uv.color-orange {
    filter: invert(57%) sepia(41%) saturate(8000%) hue-rotate(346deg) brightness(144%) contrast(97%);
}

.icon-humidity {
    background-image: url("data:image/svg+xml;charset=utf8,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 64 64'%3E%3Cpath d='M51.9 40.1a20.6 20.6 0 0 0-1-4.9C46.9 20.8 32 2 32 2S17.1 20.8 13 35.2a20.6 20.6 0 0 0-1 4.9c0 .5-.1 1-.1 1.5A20.2 20.2 0 0 0 32 62a20.2 20.2 0 0 0 20-20.4c0-.5 0-1-.1-1.5z' fill='none' stroke='%23202020' stroke-miterlimit='10' stroke-width='2'%3E%3C/path%3E%3C/svg%3E");
}

.icon-humidity.color-yellow {
    filter: invert(87%) sepia(71%) saturate(6000%) hue-rotate(346deg) brightness(104%) contrast(97%);
}

.icon-pressure {
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 512 512'%3E%3Ccircle cx='256' cy='256' r='240' fill='none' stroke='%23000' stroke-width='20'/%3E%3Cline x1='256' y1='256' x2='340' y2='180' stroke='%23000' stroke-width='16' stroke-linecap='round'/%3E%3C/svg%3E");
}

.icon-pressure.color-yellow {
    filter: invert(87%) sepia(71%) saturate(6000%) hue-rotate(346deg) brightness(104%) contrast(97%);
}

.icon-lightning {
    background-image: url("data:image/svg+xml;charset=utf8,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 64 64'%3E%3Cpath d='M37.7 27L23 43h7.5l-3.3 14L43 40h-7.4l2.1-13z' fill='none' stroke='%23202020' stroke-miterlimit='10' stroke-width='2'%3E%3C/path%3E%3C/svg%3E");
}

.icon-lightning.color-yellow {
    filter: invert(87%) sepia(71%) saturate(6000%) hue-rotate(346deg) brightness(104%) contrast(97%);
}

/* Box Resume */
.box-resume {
    margin: 0;
    padding: 0;
    width: 100%;
    min-height: 300px;
}

.box-resume-center {
    border-radius: 25px;
    background-image: url("./assets/chipiona.webp");
    width: 100%;
    min-height: 300px;
    box-shadow: 0 0 20px -10px rgba(0, 0, 0, 0.2);
    transition: transform 400ms ease;
    transform: translateZ(0) scale(1.02) perspective(1000px);
    --gradient: linear-gradient(90deg, rgba(81,81,229,0.6) 20%, rgba(68, 218, 255, 0.2) 100%);
}

.box-resume-center:hover {
    transform: scale(1.1) perspective(1500px) rotateY(10deg);
}

.resume-container-date {
    text-align: center;
    padding-top: 10px;
}

.resume-inline { display: inline-flex; }

.resume-gradient {
    position: absolute;
    width: 100%;
    height: 100%;
    top: 0;
    left: 0;
    background-image: var(--gradient);
    border-radius: 25px;
    opacity: 0.8;
    z-index: -1;
}

.resume-date-day { margin-left: 5px; }
.resume-date-dayname { margin-left: 8px; }

.resume-weather-container {
    margin-top: 8px;
    margin-left: 15px;
}

.resume-weather-temp {
    margin: 0;
    font-weight: 700;
    font-size: 3.5rem;
    text-shadow: 3px 3px 3px #222831;
}

.resume-weather-desc { margin: 0; }
.navigation-info-box { padding: 0; }

.navigation-info-left {
    display: inline-block;
    text-align: left;
    margin-left: 8px;
}

/* Box Selectors */
.box-selectors {
    margin-top: -15px;
    margin-left: auto;
    margin-right: auto;
    padding-top: 16px;
    padding-bottom: 1px;
    width: 95%;
    min-height: 90px;
    background-color: #222831;
    text-align: center;
    border-radius: 0 0 20px 20px;
}

.selector-list {
    padding: 2px 0;
    border-radius: 10px;
}

.selector-list > li {
    display: inline-block;
    padding: 15px;
    cursor: pointer;
    transition: 200ms ease;
    border-radius: 10px;
}

.selector-list > li:hover {
    transform: scale(1.1);
    background: #fff;
    color: #222831;
    box-shadow: 0 0 40px -5px rgba(0, 0, 0, 0.2);
}

.selector-list > li > .icon {
    filter: invert(27%) sepia(51%) saturate(2878%) hue-rotate(346deg) brightness(104%) contrast(97%);
}

.selector-list > li:hover > .icon, .selector-list > li.active > .icon {
    filter: none;
}

.selector-list > li.active {
    background: #fff;
    color: #222831;
    border-radius: 10px;
}

.selector-list > li .selector-element {
    display: block;
    margin: 10px 0 0 0;
    text-align: center;
}

/* Colores */
.color-blue { color: #2bf7ff; }
.color-yellow { color: #fcff6b; }
.color-orange { color: #ffc568; }
.mt-5 { margin-top: 5px; }
</style>
