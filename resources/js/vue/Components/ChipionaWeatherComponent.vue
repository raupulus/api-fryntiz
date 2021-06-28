<template>
    <div id="box-weather-chipiona">
        <div class="container">

            <div class="box-resume m-0 p-0">

                <div class="box-resume-center">
                    <div class="resume-gradient"></div>

                    <!-- Bloque con información general del tiempo -->
                    <div>
                        <div class="resume-container-date">
                            <h2 class="resume-date-dayname">
                                {{ instant.day_name }}
                            </h2>

                            <span class="resume-date-day">
              {{ instant.date_human_format }}
            </span>

                            <span class="icon icon-location"></span>

                            <span class="resume-location">
              Chipiona, Es
            </span>
                        </div>

                        <div class="resume-weather-container">
                            <!-- Muestra información general -->
                            <div v-show="navigation.info">
                                <span class="icon icon-sun"></span>

                                <h1 class="resume-weather-temp">
                                    {{ info.temperature | roundTo2Decimals }} ºC
                                </h1>

                                <h3 class="resume-weather-desc">
                                    {{ instant.time }}
                                    <br />
                                    {{ instant.day_status }}
                                </h3>
                            </div>

                            <!-- Muestra información del viento -->
                            <div v-show="navigation.wind">
                                <span class="icon icon-wind color-blue"></span>

                                <h1 class="resume-weather-temp">
                                    {{ wind.average | roundTo2Decimals }} km/h
                                </h1>

                                <h3 class="resume-weather-desc">
                                    Min: {{ wind.min | roundTo2Decimals }} km/h
                                    <br />
                                    Max: {{ wind.max | roundTo2Decimals }} km/h
                                </h3>
                            </div>

                            <!-- Muestra información de calidad del aire -->
                            <div v-show="navigation.tvoc">
                                <span class="icon icon-tvoc color-yellow"></span>
                                Calidad del Aire

                                <h1 class="resume-weather-temp">
                                    {{ air_quality.quality | roundTo2Decimals }} %
                                </h1>

                                <h3 class="resume-weather-desc">
                                    TVOC: {{ air_quality.tvoc | roundTo2Decimals }}
                                    <br />
                                    CO2-ECO2: {{ air_quality.co2_eco2 | roundTo2Decimals }}
                                </h3>
                            </div>

                            <!-- Muestra información de rayos UV -->
                            <div v-show="navigation.light">
                                <span class="icon icon-uv color-orange"></span>

                                <h1 class="resume-weather-temp">
                                    {{ light.index | roundTo2Decimals }} UV
                                </h1>

                                <h3 class="resume-weather-desc">
                                    UVA: {{ light.uva | roundTo2Decimals }}
                                    <br />
                                    UVB: {{ light.uvb | roundTo2Decimals }}
                                </h3>
                            </div>


                        </div>
                    </div>
                </div>

            </div>

            <div class="box-selectors m-0 p-0">

                <div class="selectors-container">
                    <ul class="selector-list">
                        <li :class="{active: navigation.info}" @click="menuSelect('info')">
                            <span class="icon icon-info"></span>

                            <span class="selector-element">
              General
            </span>
                        </li>

                        <li :class="{active: navigation.wind}" @click="menuSelect('wind')">
                            <i class="icon icon-wind"></i>

                            <span class="selector-element">
              Viento
            </span>
                        </li>

                        <li :class="{active: navigation.tvoc}" @click="menuSelect('tvoc')">
                            <i class="icon icon-tvoc"></i>

                            <span class="selector-element">
                TVOC
            </span>
                        </li>

                        <li :class="{active: navigation.light}" @click="menuSelect('light')">
                            <i class="icon icon-uv"></i>

                            <span class="selector-element">
                UV
            </span>
                        </li>

                    </ul>
                </div>

            </div>
        </div>
    </div>
</template>

<script>
import {onBeforeMount, onMounted, ref} from "vue";

export default {
    props: {

        api: {
            default:{
                //domain:'api.fryntiz.dev',
                domain:'localhost:8000',
                path:'api/weatherstation/v1/resume',
                protocol: 'http',
                origin:'vue-component-weather-chipiona',
                configuration:{
                    headers:{
                        'Content-Type':'application/json',
                        'Accept':'application/json',
                    },
                    method:'GET',
                    mode:'cors',
                    cache:'default',
                    //credentials: 'same-origin',
                    redirect:'follow',
                    //referrerPolicy: 'no-referrer',
                }
            }
        },

    },
    setup(props) {
        const intervals = ref({
            id_1: null
        });

        // Uso este objeto para el control de navegación.
        const navigation = ref({
            info: true,
                wind: false,
                tvoc: false,
                light: false,
        });

        const instant = ref({
            timestamp: "2020-10-04 20:26:31",
            year: "2021",
            month: "06",
            month_name: "Octubre",
            day: 4,
            day_week: 0,
            day_name: "Domingo",
            date_human_format: "01 Junio 2021",
            time: "20:26:31",
            day_status: "Noche"
        });

        const info = ref({
            temperature: 29.435345,
        });

        const wind = ref({
            average: 0.0,
                min: 0.0,
                max: 0.0,
                direction: 'N'
        });

        const air_quality =  ref({
            quality: 100,
                co2_eco2: 416.0,
                tvoc: 0.0,
        });

        const light = ref({
            light: 0,
                index: 0,
                uva: 0,
                uvb: 0
        });

        const lightning = ref({
            last: '29/09/2020'
        });

        /**
         * Obtiene los datos actualizados desde la API.
         */
        const getApiData = () => {
            let apiUrl = props.api.protocol + '://' + props.api.domain + '/' + props.api.path;
            const configuration = props.api.configuration;

            fetch(apiUrl, configuration)
                .then(response => response.json())
                .then(data => {
                    // Instante de los datos.
                    instant.value = data.instant;

                    // Información General.
                    info.value.temperature = data.temperature;
                    info.value.pressure = data.pressure;
                    info.value.humidity = data.humidity;

                    // Información del Viento.
                    wind.value.direction = data.wind_direction;
                    wind.value.average = data.wind_average;
                    wind.value.min = data.wind_min;
                    wind.value.max = data.wind_max;

                    // Información de luz y rayos UV/UVA/UVB.
                    light.value.light = data.light;
                    light.value.index = data.uv_index;
                    light.value.uva = data.uva;
                    light.value.uvb = data.uvb;

                    // Calidad del aire.
                    air_quality.value.quality = data.air_quality;
                    air_quality.value.tvoc = data.tvoc;
                    air_quality.value.co2_eco2 = data.eco2;

                    // Rayos
                    lightning.value.last = data.last_lightning_at;
                })
                .catch(error => {
                    console.error("¡Error al obtener datos desde la API!", error);
                });
        };

        /**
         * Establece en el control de navegación el elemento activo actualmente.
         */
        const menuSelect = (item) => {
            Object.keys(navigation.value).forEach(key => {
                navigation.value[key] = (key == item)
            });
        };


        onBeforeMount(() => {
            getApiData();

            intervals.value.id_1 = setInterval(() => {
                getApiData();
            }, 65000);
        });
        onMounted(() => {
            console.log('Component mounted.');
        });

        return {
            getApiData: getApiData,
            menuSelect: menuSelect,
            intervals: intervals,
            navigation: navigation,
            instant: instant,
            info: info,
            wind: wind,
            air_quality: air_quality,
            light: light,
            lightning: lightning,
        };
    },

    filters: {
        roundTo2Decimals(num) {
            return Math.round(num * 100) / 100;
        }
    }
};
</script>

<style lang="scss" scoped>
.m-0 {
    margin: 0;
}
.p-0 {
    padding: 0;
}

#box-weather-chipiona {
    margin: 0;
    width: 100%;
}

.container {
    margin: auto;
    padding: 1vh;
    max-width: 500px;
    color: #ffffff;
}

/* Iconos */
.icon {
    display: inline-block;
    width: 30px;
    height: 30px;
    background-repeat: no-repeat;
    background-size: cover;
}

.icon-sun {
    background-image: url('./assets/sun.svg');
    filter: invert(87%) sepia(31%) saturate(5000%) hue-rotate(346deg) brightness(104%) contrast(97%);
}

.icon-location {
    width: 20px;
    height: 20px;
    background-image: url('./assets/location.svg');
    color: #ff0000 !important;
    filter: invert(37%) sepia(51%) saturate(3000%) hue-rotate(346deg) brightness(104%) contrast(97%);
}

.icon-info {
    background-image: url('./assets/info.svg');
}

.icon-wind {
    background-image: url('./assets/wind.svg');
}

.icon-wind.color-blue {
    filter: invert(67%) sepia(10%) saturate(3000%) hue-rotate(156deg) brightness(104%) contrast(67%);
}

.icon-tvoc {
    background-image: url('./assets/tvoc.svg');
}

.icon-tvoc.color-yellow {
    filter: invert(87%) sepia(71%) saturate(6000%) hue-rotate(346deg) brightness(104%) contrast(97%);
}

.icon-uv {
    background-image: url('./assets/uv.svg');
}

.icon-uv.color-orange {
    filter: invert(57%) sepia(41%) saturate(8000%) hue-rotate(346deg) brightness(144%) contrast(97%);
}

/* Box Resume */
.box-resume {
    width: 100%;
    min-height: 300px;

}

.box-resume-center {
    border-radius: 25px;
    background-image: url("./assets/chipiona.webp");
    width: 100%;
    min-height: 300px;
    -webkit-box-shadow: 0 0 20px -10px rgba(0, 0, 0, 0.2);
    box-shadow: 0 0 20px -10px rgba(0, 0, 0, 0.2);
    -webkit-transition: -webkit-transform 400ms ease;
    transition: -webkit-transform 400ms ease;
    -o-transition: transform 400ms ease;
    transition: transform 400ms ease;
    transition: transform 400ms ease, -webkit-transform 400ms ease;
    -webkit-transform: translateZ(0) scale(1.02) perspective(1000px);
    transform: translateZ(0) scale(1.02) perspective(1000px);
    --gradient: linear-gradient(90deg, rgba(81,81,229,0.6) 20%, rgba(68, 218, 255, 0.2) 100%);
}

.box-resume-center:hover {
    -webkit-transform: scale(1.1) perspective(1500px) rotateY(10deg);
    transform: scale(1.1) perspective(1500px) rotateY(10deg);
}

.resume-gradient {
    position: absolute;
    width: 100%;
    height: 100%;
    top: 0;
    left: 0;
    background-image: var(--gradient);
    border-radius: 25px;
    opacity: 0.8;
}

.resume-container-date {
    position: absolute;
    top: 25px;
    left: 25px;
}

.resume-date-dayname {
    margin: 0;
}

.resume-date-day {
    display: block;
}

.resume-location {
    display: inline-block;
}

.resume-location-icon {
    display: inline-block;
    height: 0.8em;
    width: auto;
    margin-right: 5px;
}

.resume-weather-container {
    position: absolute;
    bottom: 25px;
    left: 25px;
}

.resume-weather-temp {
    margin: 0;
    font-weight: 700;
    font-size: 4em;
    text-shadow: 3px 3px 3px #222831;
}

.resume-weather-desc {
    margin: 0;
}

/* Box Selectors - Menú inferior de selección */
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
    -webkit-transition: 200ms ease;
    -o-transition: 200ms ease;
    transition: 200ms ease;
    border-radius: 10px;
}

.selector-list > li:hover {
    -webkit-transform: scale(1.1);
    -ms-transform: scale(1.1);
    transform: scale(1.1);
    background: #fff;
    color: #222831;
    -webkit-box-shadow: 0 0 40px -5px rgba(0, 0, 0, 0.2);
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
.color-red {
    color: #ff0000;
}
.color-blue {
    color: #2bf7ff;
}
.color-yellow {
    color: #fcff6b;
}
.color-orange {
    color: #ffc568;
}
</style>
