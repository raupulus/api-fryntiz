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
import {onBeforeMount, ref} from "vue";

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
    background-image: url("data:image/svg+xml,%3C%3Fxml version='1.0' encoding='utf-8'%3F%3E%3C!-- Generated by IcoMoon.io --%3E%3C!DOCTYPE svg PUBLIC '-//W3C//DTD SVG 1.1//EN' 'http://www.w3.org/Graphics/SVG/1.1/DTD/svg11.dtd'%3E%3Csvg version='1.1' xmlns='http://www.w3.org/2000/svg' xmlns:xlink='http://www.w3.org/1999/xlink' width='512' height='512' viewBox='0 0 512 512'%3E%3Cg%3E%3C/g%3E%3Cpath d='M377.139 259.492c0 66.637-54.020 120.658-120.658 120.658-66.637 0-120.658-54.021-120.658-120.658 0-66.637 54.020-120.658 120.658-120.658 66.637 0 120.658 54.020 120.658 120.658z' fill='%23000000' /%3E%3Cpath d='M228.352 100.669l30.27-77.906 25.979 77.906z' fill='%23000000' /%3E%3Cpath d='M228.352 411.341l30.27 77.895 25.979-77.895z' fill='%23000000' /%3E%3Cpath d='M100.659 287.601l-77.895-30.29 77.895-25.959z' fill='%23000000' /%3E%3Cpath d='M411.361 287.601l77.875-30.29-77.875-25.959z' fill='%23000000' /%3E%3Cpath d='M126.597 165.703l-33.659-76.472 73.442 36.7z' fill='%23000000' /%3E%3Cpath d='M346.276 385.423l76.524 33.639-36.741-73.442z' fill='%23000000' /%3E%3Cpath d='M168.499 388.199l-76.493 33.639 36.72-73.442z' fill='%23000000' /%3E%3Cpath d='M388.199 168.499l33.618-76.513-73.4 36.751z' fill='%23000000' /%3E%3C/svg%3E%0A");
    filter: invert(87%) sepia(31%) saturate(5000%) hue-rotate(346deg) brightness(104%) contrast(97%);
}

.icon-location {
    width: 20px;
    height: 20px;
    background-image: url("data:image/svg+xml,%3C%3Fxml version='1.0' encoding='iso-8859-1'%3F%3E%3C!-- Generator: Adobe Illustrator 16.0.0, SVG Export Plug-In . SVG Version: 6.00 Build 0) --%3E%3C!DOCTYPE svg PUBLIC '-//W3C//DTD SVG 1.1//EN' 'http://www.w3.org/Graphics/SVG/1.1/DTD/svg11.dtd'%3E%3Csvg version='1.1' id='Capa_1' xmlns='http://www.w3.org/2000/svg' xmlns:xlink='http://www.w3.org/1999/xlink' x='0px' y='0px' width='425.963px' height='425.963px' viewBox='0 0 425.963 425.963' style='enable-background:new 0 0 425.963 425.963;' xml:space='preserve'%3E%3Cg%3E%3Cpath d='M213.285,0h-0.608C139.114,0,79.268,59.826,79.268,133.361c0,48.202,21.952,111.817,65.246,189.081 c32.098,57.281,64.646,101.152,64.972,101.588c0.906,1.217,2.334,1.934,3.847,1.934c0.043,0,0.087,0,0.13-0.002 c1.561-0.043,3.002-0.842,3.868-2.143c0.321-0.486,32.637-49.287,64.517-108.976c43.03-80.563,64.848-141.624,64.848-181.482 C346.693,59.825,286.846,0,213.285,0z M274.865,136.62c0,34.124-27.761,61.884-61.885,61.884 c-34.123,0-61.884-27.761-61.884-61.884s27.761-61.884,61.884-61.884C247.104,74.736,274.865,102.497,274.865,136.62z'/%3E%3C/g%3E%3Cg%3E%3C/g%3E%3Cg%3E%3C/g%3E%3Cg%3E%3C/g%3E%3Cg%3E%3C/g%3E%3Cg%3E%3C/g%3E%3Cg%3E%3C/g%3E%3Cg%3E%3C/g%3E%3Cg%3E%3C/g%3E%3Cg%3E%3C/g%3E%3Cg%3E%3C/g%3E%3Cg%3E%3C/g%3E%3Cg%3E%3C/g%3E%3Cg%3E%3C/g%3E%3Cg%3E%3C/g%3E%3Cg%3E%3C/g%3E%3C/svg%3E%0A");
    color: #ff0000 !important;
    filter: invert(37%) sepia(51%) saturate(3000%) hue-rotate(346deg) brightness(104%) contrast(97%);
}

.icon-info {
    background-image: url("data:image/svg+xml,%3C%3Fxml version='1.0' encoding='iso-8859-1'%3F%3E%3C!-- Generator: Adobe Illustrator 16.0.0, SVG Export Plug-In . SVG Version: 6.00 Build 0) --%3E%3C!DOCTYPE svg PUBLIC '-//W3C//DTD SVG 1.1//EN' 'http://www.w3.org/Graphics/SVG/1.1/DTD/svg11.dtd'%3E%3Csvg version='1.1' id='Capa_1' xmlns='http://www.w3.org/2000/svg' xmlns:xlink='http://www.w3.org/1999/xlink' x='0px' y='0px' width='496.304px' height='496.303px' viewBox='0 0 496.304 496.303' style='enable-background:new 0 0 496.304 496.303;' xml:space='preserve'%3E%3Cg%3E%3Cpath d='M248.146,0C111.314,0,0,111.321,0,248.152c0,136.829,111.314,248.151,248.146,248.151 c136.835,0,248.158-111.322,248.158-248.151C496.304,111.321,384.98,0,248.146,0z M248.146,472.093 c-123.473,0-223.935-100.459-223.935-223.941c0-123.479,100.462-223.941,223.935-223.941 c123.488,0,223.947,100.462,223.947,223.941C472.093,371.634,371.634,472.093,248.146,472.093z M319.536,383.42v32.852 c0,1.383-1.123,2.494-2.482,2.494H196.45c-1.374,0-2.482-1.117-2.482-2.494V383.42c0-1.372,1.114-2.482,2.482-2.482h34.744V205.831 h-35.101c-1.375,0-2.468-1.111-2.468-2.474v-33.6c0-1.38,1.1-2.479,2.468-2.479h82.293c1.371,0,2.482,1.105,2.482,2.479v211.181 h36.186C318.413,380.938,319.536,382.048,319.536,383.42z M209.93,105.927c0-20.895,16.929-37.829,37.829-37.829 c20.886,0,37.826,16.935,37.826,37.829s-16.94,37.829-37.826,37.829C226.853,143.756,209.93,126.822,209.93,105.927z'/%3E%3C/g%3E%3Cg%3E%3C/g%3E%3Cg%3E%3C/g%3E%3Cg%3E%3C/g%3E%3Cg%3E%3C/g%3E%3Cg%3E%3C/g%3E%3Cg%3E%3C/g%3E%3Cg%3E%3C/g%3E%3Cg%3E%3C/g%3E%3Cg%3E%3C/g%3E%3Cg%3E%3C/g%3E%3Cg%3E%3C/g%3E%3Cg%3E%3C/g%3E%3Cg%3E%3C/g%3E%3Cg%3E%3C/g%3E%3Cg%3E%3C/g%3E%3C/svg%3E%0A");
}

.icon-wind {
    background-image: url("data:image/svg+xml,%3C%3Fxml version='1.0' encoding='iso-8859-1'%3F%3E%3C!-- Generator: Adobe Illustrator 19.0.0, SVG Export Plug-In . SVG Version: 6.00 Build 0) --%3E%3Csvg version='1.1' id='Layer_1' xmlns='http://www.w3.org/2000/svg' xmlns:xlink='http://www.w3.org/1999/xlink' x='0px' y='0px' viewBox='0 0 331.309 331.309' style='enable-background:new 0 0 331.309 331.309;' xml:space='preserve'%3E%3Cg%3E%3Cg%3E%3Cg%3E%3Cpath d='M49.425,143.993h138.344c19.952,0,36.184-16.232,36.184-36.184c0-19.952-16.231-36.185-36.184-36.185 c-19.953,0-36.185,16.232-36.185,36.185c0,0.737,0.099,1.45,0.266,2.136c0.96,3.938,4.501,6.864,8.734,6.864c4.971,0,9-4.029,9-9 c0-10.027,8.157-18.185,18.185-18.185c10.026,0,18.184,8.158,18.184,18.185c0,10.027-8.157,18.184-18.184,18.184H86.227H49.425 c-4.971,0-9,4.029-9,9S44.454,143.993,49.425,143.993z'/%3E%3Cpath d='M270.532,187.315H132.188c-4.971,0-9,4.029-9,9s4.029,9,9,9h36.803h101.541c10.027,0,18.185,8.157,18.185,18.184 c0,10.027-8.157,18.185-18.185,18.185c-10.028,0-18.185-8.158-18.185-18.185c0-4.971-4.029-9-9-9 c-4.233,0-7.774,2.926-8.734,6.864c-0.167,0.686-0.266,1.399-0.266,2.136c0,19.952,16.232,36.185,36.185,36.185 s36.185-16.232,36.185-36.185S290.484,187.315,270.532,187.315z'/%3E%3Cpath d='M282.81,79.094c-26.743,0-48.5,21.756-48.5,48.499c0,4.971,4.029,9,9,9s9-4.029,9-9c0-16.817,13.683-30.499,30.5-30.499 c16.817,0,30.499,13.682,30.499,30.499c0,16.817-13.682,30.5-30.499,30.5H9c-4.971,0-9,4.029-9,9c0,4.971,4.029,9,9,9h273.81 c26.742,0,48.499-21.757,48.499-48.5C331.309,100.849,309.552,79.094,282.81,79.094z'/%3E%3Cpath d='M104.743,187.315H87.785c-4.971,0-9,4.029-9,9s4.029,9,9,9h16.958c4.971,0,9-4.029,9-9S109.714,187.315,104.743,187.315z '/%3E%3Cpath d='M21.987,143.993h3.334c4.971,0,9-4.029,9-9s-4.029-9-9-9h-3.334c-4.971,0-9,4.029-9,9S17.017,143.993,21.987,143.993z'/%3E%3C/g%3E%3C/g%3E%3C/g%3E%3Cg%3E%3C/g%3E%3Cg%3E%3C/g%3E%3Cg%3E%3C/g%3E%3Cg%3E%3C/g%3E%3Cg%3E%3C/g%3E%3Cg%3E%3C/g%3E%3Cg%3E%3C/g%3E%3Cg%3E%3C/g%3E%3Cg%3E%3C/g%3E%3Cg%3E%3C/g%3E%3Cg%3E%3C/g%3E%3Cg%3E%3C/g%3E%3Cg%3E%3C/g%3E%3Cg%3E%3C/g%3E%3Cg%3E%3C/g%3E%3C/svg%3E%0A");
}

.icon-wind.color-blue {
    filter: invert(67%) sepia(10%) saturate(3000%) hue-rotate(156deg) brightness(104%) contrast(67%);
}

.icon-tvoc {
    background-image: url("data:image/svg+xml,%3C%3Fxml version='1.0' encoding='iso-8859-1'%3F%3E%3C!-- Generator: Adobe Illustrator 16.0.0, SVG Export Plug-In . SVG Version: 6.00 Build 0) --%3E%3C!DOCTYPE svg PUBLIC '-//W3C//DTD SVG 1.1//EN' 'http://www.w3.org/Graphics/SVG/1.1/DTD/svg11.dtd'%3E%3Csvg version='1.1' id='Capa_1' xmlns='http://www.w3.org/2000/svg' xmlns:xlink='http://www.w3.org/1999/xlink' x='0px' y='0px' width='439.685px' height='439.685px' viewBox='0 0 439.685 439.685' style='enable-background:new 0 0 439.685 439.685;' xml:space='preserve'%3E%3Cg%3E%3Cg%3E%3Cpath d='M384.683,165.281c-11.13-70.038-71.789-123.575-144.958-123.575c-66.203,0-122.161,43.827-140.464,104.048 C42.424,158.683,0,209.523,0,270.275c0,70.528,57.175,127.703,127.704,127.703H311.98c70.528,0,127.704-57.175,127.704-127.703 C439.686,226.762,417.917,188.339,384.683,165.281z M176.68,209.257l-4.213,16.443c-0.275,1.07-1.003,1.973-1.993,2.464 c-0.992,0.493-2.147,0.53-3.167,0.104c-4.906-2.059-10.264-3.102-15.924-3.102c-15.912,0-25.412,9.907-25.412,26.5 c0,16.581,9.312,26.092,25.549,26.092c6.219,0,12.573-1.408,15.845-2.718c1.054-0.42,2.24-0.352,3.238,0.193 c0.996,0.543,1.698,1.504,1.914,2.617l3.126,16.174c0.323,1.678-0.509,3.364-2.036,4.129c-4.008,2.002-13.017,4.342-25.213,4.342 c-31.456,0-51.781-19.473-51.781-49.606c0-31.378,21.629-52.461,53.819-52.461c11.517,0,20.11,2.327,24.313,4.504 C176.318,205.748,177.119,207.541,176.68,209.257z M229.422,302.63c-27.607,0-46.888-20.732-46.888-50.422 c0-30.488,19.896-51.78,48.384-51.78c28.289,0,47.296,20.154,47.296,50.151C278.214,282.201,259.062,302.63,229.422,302.63z M343.073,322.74c0,2.102-1.704,3.806-3.808,3.806h-46.41c-2.103,0-3.807-1.704-3.807-3.806v-9.265 c0-1.075,0.455-2.102,1.253-2.821l8.456-7.65c13.558-12.127,20.031-18.964,20.192-25.062c-0.003-3.773-2.279-5.658-6.968-5.658 c-3.713,0-7.643,1.565-11.681,4.654c-0.948,0.728-2.184,0.959-3.333,0.646c-1.149-0.32-2.083-1.161-2.521-2.273l-4.328-10.975 c-0.628-1.591-0.116-3.403,1.247-4.435c6.326-4.773,14.979-7.514,23.736-7.514c16.257,0,26.761,9.555,26.761,24.344 c0,11.914-8.04,21.295-17.126,29.615h14.527c2.104,0,3.807,1.704,3.807,3.807v12.588H343.073z'/%3E%3Cpath d='M230.373,224.488c-13.54,0-18.345,14.713-18.345,27.313c0,16.265,7.255,26.772,18.481,26.772 c13.44,0,18.21-14.715,18.21-27.314C248.72,237.933,243.047,224.488,230.373,224.488z'/%3E%3C/g%3E%3C/g%3E%3Cg%3E%3C/g%3E%3Cg%3E%3C/g%3E%3Cg%3E%3C/g%3E%3Cg%3E%3C/g%3E%3Cg%3E%3C/g%3E%3Cg%3E%3C/g%3E%3Cg%3E%3C/g%3E%3Cg%3E%3C/g%3E%3Cg%3E%3C/g%3E%3Cg%3E%3C/g%3E%3Cg%3E%3C/g%3E%3Cg%3E%3C/g%3E%3Cg%3E%3C/g%3E%3Cg%3E%3C/g%3E%3Cg%3E%3C/g%3E%3C/svg%3E%0A");
}

.icon-tvoc.color-yellow {
    filter: invert(87%) sepia(71%) saturate(6000%) hue-rotate(346deg) brightness(104%) contrast(97%);
}

.icon-uv {
    background-image: url("data:image/svg+xml,%3C%3Fxml version='1.0' encoding='iso-8859-1'%3F%3E%3C!-- Generator: Adobe Illustrator 19.0.0, SVG Export Plug-In . SVG Version: 6.00 Build 0) --%3E%3Csvg version='1.1' id='Layer_1' xmlns='http://www.w3.org/2000/svg' xmlns:xlink='http://www.w3.org/1999/xlink' x='0px' y='0px' viewBox='0 0 512 512' style='enable-background:new 0 0 512 512;' xml:space='preserve'%3E%3Cg%3E%3Cg%3E%3Cpath d='M437.019,74.981C388.667,26.628,324.379,0,256,0S123.333,26.628,74.981,74.981C26.628,123.332,0,187.621,0,256 s26.628,132.668,74.981,181.019C123.333,485.372,187.621,512,256,512s132.667-26.628,181.019-74.981 C485.372,388.668,512,324.379,512,256S485.372,123.332,437.019,74.981z M422.242,422.241 C377.837,466.647,318.798,491.102,256,491.102c-62.798,0-121.837-24.455-166.242-68.86c-91.666-91.667-91.666-240.818,0-332.484 C134.163,45.353,193.202,20.898,256,20.898c62.798,0,121.837,24.455,166.242,68.86 C513.909,181.425,513.909,330.575,422.242,422.241z'/%3E%3C/g%3E%3C/g%3E%3Cg%3E%3Cg%3E%3Cpath d='M401.554,110.446C362.676,71.567,310.984,50.155,256,50.155s-106.675,21.412-145.553,60.291 C71.567,149.324,50.155,201.016,50.155,256s21.412,106.676,60.291,145.554c38.88,38.879,90.571,60.291,145.554,60.291 s106.675-21.412,145.553-60.291c38.88-38.879,60.292-90.571,60.292-145.554S440.433,149.324,401.554,110.446z M386.776,386.776 c-34.931,34.933-81.376,54.171-130.776,54.171s-95.845-19.238-130.777-54.171C90.292,351.845,71.053,305.401,71.053,256 s19.239-95.845,54.171-130.776C160.155,90.291,206.599,71.053,256,71.053s95.845,19.238,130.777,54.171 c34.931,34.931,54.17,81.376,54.17,130.776S421.708,351.845,386.776,386.776z'/%3E%3C/g%3E%3C/g%3E%3Cg%3E%3Cg%3E%3Cpath d='M410.158,282.12c-2.041-2.041-4.714-3.06-7.387-3.06c-2.675,0-5.35,1.021-7.389,3.062l-3.307,3.308 c-4.079,4.081-4.079,10.698,0.002,14.778c4.081,4.08,10.697,4.078,14.777-0.002l3.307-3.308 C414.239,292.816,414.239,286.2,410.158,282.12z'/%3E%3C/g%3E%3C/g%3E%3Cg%3E%3Cg%3E%3Cpath d='M382.949,309.332c-2.041-2.04-4.715-3.06-7.388-3.06s-5.349,1.021-7.388,3.06l-86.052,86.05 c-4.08,4.08-4.08,10.697,0,14.778c4.08,4.078,10.697,4.078,14.777,0l86.052-86.05 C387.029,320.029,387.029,313.413,382.949,309.332z'/%3E%3C/g%3E%3C/g%3E%3Cg%3E%3Cg%3E%3Cpath d='M413.623,221.026c-2.041-2.04-4.715-3.061-7.388-3.061c-2.674,0.001-5.348,1.021-7.388,3.061l-177.82,177.819 c-4.08,4.08-4.08,10.697,0,14.778c4.081,4.078,10.697,4.079,14.777,0l177.82-177.819 C417.703,231.724,417.703,225.108,413.623,221.026z'/%3E%3C/g%3E%3C/g%3E%3Cg%3E%3Cg%3E%3Cpath d='M407.696,169.323c-2.041-2.04-4.715-3.06-7.388-3.06s-5.349,1.021-7.388,3.06L169.323,392.918 c-4.08,4.08-4.08,10.697,0,14.778c4.08,4.079,10.697,4.079,14.778,0l223.596-223.596 C411.777,180.02,411.777,173.404,407.696,169.323z'/%3E%3C/g%3E%3C/g%3E%3Cg%3E%3Cg%3E%3Cpath d='M211.615,124.883c-4.76-0.001-8.686,3.925-8.686,8.686v75.802c0,15.902-13.351,29.263-29.255,29.255 c-15.859-0.008-29.174-13.41-29.174-29.255v-75.802c0-4.761-3.926-8.686-8.688-8.686c-4.761-0.001-8.686,3.925-8.686,8.686v75.802 c0,23.57,18.429,44.368,42.077,46.439c26.868,2.353,51.099-19.291,51.099-46.439v-75.802 C220.301,128.809,216.375,124.883,211.615,124.883z'/%3E%3C/g%3E%3C/g%3E%3Cg%3E%3Cg%3E%3Cpath d='M331.549,125.162c-4.241-1.084-8.696,0.706-10.287,4.884l-36.221,93.175l-36.22-93.175 c-1.886-4.24-6.574-6.224-10.998-4.687c-2.398,0.833-4.325,2.746-5.189,5.131c-0.91,2.511-0.488,5.075,0.453,7.504l44.005,112.515 c1.475,3.661,4.124,5.491,7.948,5.491s6.475-1.83,7.949-5.49l44.006-112.515C339.179,132.908,337.319,126.635,331.549,125.162z'/%3E%3C/g%3E%3C/g%3E%3Cg%3E%3C/g%3E%3Cg%3E%3C/g%3E%3Cg%3E%3C/g%3E%3Cg%3E%3C/g%3E%3Cg%3E%3C/g%3E%3Cg%3E%3C/g%3E%3Cg%3E%3C/g%3E%3Cg%3E%3C/g%3E%3Cg%3E%3C/g%3E%3Cg%3E%3C/g%3E%3Cg%3E%3C/g%3E%3Cg%3E%3C/g%3E%3Cg%3E%3C/g%3E%3Cg%3E%3C/g%3E%3Cg%3E%3C/g%3E%3C/svg%3E%0A");
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
