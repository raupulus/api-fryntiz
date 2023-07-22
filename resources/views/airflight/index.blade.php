@extends('layouts.app')

{{-- Descripción sobre esta página --}}
@section('title', 'Vuelos en tiempo real sobre Chipiona')
@section('description', 'Registro de vuelos en tiempo real para aviones en Chipiona y alrededores')
@section('keywords', 'vuelos, aviones, chipiona, Raúl Caro Pastorino, fryntiz, airplanes, airflight')

{{-- Etiquetas para Redes sociales --}}
@section('rs-title', 'Vuelos en tiempo real sobre Chipiona')
@section('rs-sitename', 'Api Raupulus')
@section('rs-description', 'Registro de vuelos en tiempo real para aviones en Chipiona y alrededores')
@section('rs-image', asset('images/airflight/social-thumbnail.jpg'))
@section('rs-url', route('airflight.index'))
@section('rs-image-alt', 'Vuelos en tiempo real sobre Chipiona')

@section('meta-twitter-title', 'Vuelos en tiempo real sobre Chipiona')

@section('content')
    <div class="leading-normal tracking-normal"
         style="font-family: 'Source Sans Pro', sans-serif;">

        <section class="bg-white border-b">
            <div class="container max-w-5xl mx-auto m-4">
                <h1 class="w-full my-2 text-5xl font-bold leading-tight text-center text-gray-800">
                    Registros de vuelos en tiempo real
                </h1>

                <h2 class="w-full my-2 text-4xl font-bold leading-tight text-center text-gray-800">
                    Chipiona
                </h2>

                {{-- Información general sobre el proyecto --}}
                <div class="flex flex-wrap content-center">
                    <div class="w-full p-6">
                        <div
                            class="bg-red-100 border border-red-400 m-2 mb-5 text-red-700 px-2 py-2 rounded relative"
                            role="alert">
                            <span class="inline text-sm">
                                 Sitio
                                <strong>temporal</strong>
                                con la finalidad de comprobar el
                                funcionamiento de los dispositivos que uso
                                para recopilar los datos (raspberry pi y
                                capturadora de televisión digital con una antena
                                modificada para 1090Mhz)

                                <br/>

                                Una vez acabada la aplicación desaparecerá quedando
                                en el subdominio:
                                <a href="https://desdechipiona.es/vuelos"
                                   class="underline text-lightBlue-500 background-transparent font-bold text-xs outline-none focus:outline-none ease-linear transition-all duration-150"
                                   type="button"
                                   target="_blank"
                                   title="Vuelos en tiempo real en Chipiona">
                                    https://eltiempo.desdechipiona.es/vuelos
                                </a>
                            </span>
                        </div>

                        <h3 class="text-3xl text-gray-800 font-bold leading-none mb-3">
                            Sobre esta api
                        </h3>

                        <p class="text-gray-600 mb-2">
                            Básicamente monitorizo la señal que emiten los
                            aviones enviando su posición para no chocarse
                            entre ellos obteniendo esos datos por ondas que
                            decodifico y parseo para poder sacar estadísticas
                            y trazar rutas de vuelos en tiempo real.
                        </p>

                        <p class="text-gray-600 mb-2">
                            Los datos pueden no ser precisos debido a que aún se
                            encuentra en depuración mientras voy detectando
                            posibles problemas en el hardware que he
                            manipulado y el software que le he construido.
                        </p>

                        <p class="text-gray-600 mb-2">
                            Puedes ver el desarrollo del programa para obtener
                            los datos
                            de los aviones detectados con la tarjeta de
                            televisión y una
                            raspberry pi con el que los exporto a una db:

                            <a href="https://gitlab.com/fryntiz/dump1090-to-db"
                               class="underline text-lightBlue-500 background-transparent font-bold text-xs outline-none focus:outline-none ease-linear transition-all duration-150"
                               type="button"
                               target="_blank"
                               title="Exportador dump1090 a db postgresql">
                                https://gitlab.com/fryntiz/dump1090-to-db
                            </a>
                        </p>

                        <div
                            class="bg-yellow-100 border border-red-400 m-2 my-4 text-red-700 px-2 py-2 rounded relative text-center"
                            role="alert">
                            <span class="inline font-bold">
                                Franja horaria UTC +0:00
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- Mapa con los vuelos --}}
        <section class="bg-white border-b">
            <div class="container max-w-5xl mx-auto m-4">
                <h2 class="w-full my-2 text-4xl font-bold leading-tight text-center text-gray-800">
                    Aviones en Tiempo Real
                </h2>

                <p class="text-sm text-center">
                    (Selecciona el avión en el mapa o la primera tabla para
                    ver el recorrido que realiza.)
                </p>

                <div id="box-airflight" class="w-full">
                    <div class="box-information">
                        <div class="box-buttons">
                            <div class="btn-map btn-downto-map pointer">
                                Ir debajo del Mapa
                            </div>
                        </div>
                    </div>

                    <!-- Mapa -->
                    <div id="map_container" class="box-map">
            <span id="loader" class="display-hidden box-map-loader">
            <img src="{{asset('resources/airflight/spinny.gif')}}"
                 id="spinny"
                 class="spinny"
                 alt="Cargando..."/>
            <progress id="loader_progress" class="loader-progress"></progress>
            </span>

                        <div id="map_canvas" class="map-canvas"></div>
                    </div>

                    <!-- Información -->
                    <div id="sidebar_container" class="box-information">
                        <div id="sidebar_canvas">
                            <div id="sudo_buttons" class="box-buttons">
                                <div class="btn-map btn-reset-map pointer">
                                    Reiniciar Zoom
                                </div>

                                <div class="btn-map btn-upto-map pointer">
                                    Ir encima del Mapa
                                </div>

                                <div class="btn-map btn-reload-map pointer">
                                    Recargar datos
                                </div>
                            </div>

                            <div id="dump1090_infoblock">
                                <table style="width: 100%">
                                    <tr class="infoblock_heading">
                                        <td class="center">
                                            <b id="infoblock_name">Detalles del
                                                Vuelo</b>
                                        </td>

                                        <td style="text-align: right">
                                            <a
                                                href="https://api.fryntiz.dev/airflight"
                                                id="dump1090_version"
                                                target="_blank"
                                            ></a>
                                        </td>
                                    </tr>

                                    <tr class="infoblock_body">
                                        <td>&nbsp;</td>
                                        <td>&nbsp;</td>
                                    </tr>

                                    <tr class="infoblock_body dim">
                                        <td>Selecciona un avión de la tabla
                                            inferior para ver sus datos
                                        </td>
                                        <td>&nbsp;</td>
                                    </tr>

                                    <tr class="infoblock_body">
                                        <td>&nbsp;</td>
                                        <td>&nbsp;</td>
                                    </tr>

                                    <tr class="infoblock_body">
                                        <td>
                                            Aviones (total): <span
                                                id="dump1090_total_ac">n/a</span>
                                        </td>
                                        <td>
                                            Mensajes: <span
                                                id="dump1090_message_rate">n/a</span>/sec
                                        </td>
                                    </tr>

                                    <tr class="infoblock_body">
                                        <td>
                                            (con posiciones):
                                            <span
                                                id="dump1090_total_ac_positions">n/a</span>
                                        </td>
                                        <td>
                                            Historial:
                                            <span id="dump1090_total_history">n/a</span>
                                            posiciones
                                        </td>
                                    </tr>
                                </table>
                            </div>
                            <!-- dump1090_infoblock -->

                            <div id="selected_infoblock" class="display-hidden">
                                <table style="width: 100%">
                                    <tr class="infoblock_heading">
                                        <td colspan="2">
                                            <b>
                                                <span id="selected_callsign"
                                                      class="map-selected-callsign pointer"
                                                >n/a
                                                </span>
                                            </b>

                                            <span id="selected_follow"
                                                  class="map-selected-follow pointer">
                                                &#x21D2;
                                            </span>

                                            <span id="selected_flag"
                                                  class="selected-flag">
                                                <img
                                                    class="selected-flag-image"
                                                    src="about:blank"
                                                    alt="Flag"
                                                />
                                            </span>

                                            <span id="selected_registration"
                                                  class="selected-registration"></span>

                                            <span id="selected_icaotype"
                                                  class="selected-icao-type"></span>

                                            <span
                                                id="selected_emergency"></span>

                                            <div style="display: inline-block;">
                                                <span
                                                    style="color: green; font-size: small;">
                                                    &nbsp;
                                                    Ver otras plataformas:
                                                </span>

                                                <a id="selected_flightaware_link"
                                                   href=""
                                                   target="_blank">
                                                    [FlightAware]
                                                </a>

                                                <span id="selected_links">
                                                    <a id="selected_fr24_link"
                                                       href=""
                                                       target="_blank">
                                                        [FR24]
                                                    </a>

                                                    <a id="selected_flightstats_link"
                                                       href=""
                                                       target="_blank">
                                                        [FlightStats]
                                                    </a>

                                                    <a id="selected_planefinder_link"
                                                       href=""
                                                       target="_blank">
                                                        [PlaneFinder]
                                                    </a>
                                                </span>
                                            </div>

                                        </td>
                                    </tr>

                                    <tr id="infoblock_country"
                                        class="infoblock_body">
                                        <td colspan="2">
                                            País del registro: <span
                                                id="selected_country">n/a</span>
                                        </td>
                                    </tr>

                                    <tr class="infoblock_body">
                                        <td style="width: 55%;">
                                            Altitud: <span
                                                id="selected_altitude"></span>
                                        </td>
                                        <td style="width: 45%;">
                                            Squawk: <span
                                                id="selected_squawk"></span>
                                        </td>
                                    </tr>

                                    <tr class="infoblock_body">
                                        <td>Velocidad: <span
                                                id="selected_speed">n/a</span>
                                        </td>
                                        <td>RSSI: <span
                                                id="selected_rssi">n/a</span>
                                        </td>
                                    </tr>

                                    <tr class="infoblock_body">
                                        <td>Track: <span
                                                id="selected_track">n/a</span>
                                        </td>
                                        <td>Visto hace: <span
                                                id="selected_seen">n/a</span>
                                        </td>
                                    </tr>

                                    <tr class="infoblock_body">
                                        <td colspan="2">
                                            Posición: <span
                                                id="selected_position">n/a</span>
                                        </td>
                                    </tr>

                                    <tr class="infoblock_body">
                                        <td colspan="2">
                                            Distancia al centro de Chipiona:
                                            <span
                                                id="selected_sitedist">n/a</span>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                            <!-- selected_infoblock -->

                            <div id="planes_table" class="box-table-planes">
                                <table id="tableinfo" class="table-info">
                                    <thead>
                                    <tr>
                                        <td id="icao"
                                            class="map-tableinfo-title-icao">
                                            ICAO
                                        </td>

                                        <td id="flag"
                                            class="map-tableinfo-title-country">
                                            <!-- column for flag image -->
                                        </td>

                                        <td id="flight"
                                            class="map-tableinfo-title-flight">
                                            Vuelo
                                        </td>

                                        <td id="squawk"
                                            class="map-tableinfo-title-squawk">
                                            Squawk
                                        </td>

                                        <td id="altitude"
                                            class="map-tableinfo-title-altitude">
                                            Altitud ft
                                        </td>

                                        <td id="speed"
                                            class="map-tableinfo-title-speed">
                                            Velocidad kt
                                        </td>

                                        <td id="distance"
                                            class="map-tableinfo-title-distance">
                                            Distancia nm
                                        </td>

                                        <td id="track"
                                            class="map-tableinfo-title-track">
                                            Trackº
                                        </td>

                                        <td id="msgs"
                                            class="map-tableinfo-title-messages">
                                            Msgs
                                        </td>

                                        <td id="seen"
                                            class="map-tableinfo-title-seen">
                                            Visto s
                                        </td>
                                    </tr>
                                    </thead>

                                    <tbody>
                                    <tr id="plane_row_template"
                                        class="plane_table_row display-hidden">
                                        <td>ICAO</td>
                                        <td>
                                            <img
                                                class="plane-table-image"
                                                src="about:blank"
                                                alt="Flag"
                                            />
                                        </td>
                                        <td>VUELO</td>
                                        <td>SQUAWK</td>
                                        <td>ALTITUD</td>
                                        <td>VELOCIDAD</td>
                                        <td>DISTANCIA</td>
                                        <td>TRACK</td>
                                        <td>MSGS</td>
                                        <td>EDAD</td>
                                    </tr>
                                    </tbody>
                                </table>
                            </div>
                            <!-- planes_table -->
                        </div>
                        <!-- sidebar_canvas -->
                    </div>
                    <!-- sidebar_container -->

                    <!-- Bloque de información -->
                    <div id="SpecialSquawkWarning"
                         class="display-hidden special-squawk-warning">
                        <b>Squawk 7x00 se informa y se muestra.</b><br/>
                        lo más probable es que se trate de un error en la
                        recepción o decodificación.
                        <br/> Por favor, no llame a las
                        autoridades locales, ya lo saben si es un chillido
                        válido.
                    </div>

                    <div id="update_error" class="display-hidden update-error">
                        <b>Problema sincronizando datos.</b><br/>
                        <span id="update_error_detail"></span><br/>
                        El mapa mostrado puede estar desactualizado, recarguelo
                        para
                        reintentarlo.
                    </div>

                    <div id="container_splitter"></div>
                </div>
            </div>
        </section>


        <section class="bg-white border-b">
            <div class="container max-w-5xl mx-auto m-4 overflow-x-scroll">

                <h2 class="w-full my-2 text-4xl font-bold leading-tight text-center text-gray-800">
                    Aviones en la última hora
                </h2>

                <table class="min-w-max w-full table-auto">
                    <thead class="justify-between">
                    <tr class="bg-gray-800">
                        <th class="px-1 py-2 text-gray-300 capitalize text-center">

                        </th>

                        <th class="px-1 py-2 text-gray-300 capitalize text-center">
                            ICAO
                        </th>

                        <th class="px-1 py-2 text-gray-300 capitalize text-center">
                            País Origen
                        </th>

                        <th class="px-1 py-2 text-gray-300 capitalize text-center">
                            Categoría
                        </th>

                        <th class="px-1 py-2 text-gray-300 capitalize text-center">
                            Visto última vez
                        </th>

                        <th class="px-1 py-2 text-gray-300 capitalize text-center">
                            Visto primera vez
                        </th>
                    </tr>
                    </thead>

                    <tbody class="bg-gray-200">
                    @foreach($planes as $plane)
                        <tr class="bg-white border-4 border-gray-200">
                            <td class="px-1 py-2 items-center text-center">
                                <img src="{{$plane->urlFlag}}"
                                     title="{{$plane->country}}"
                                     alt="{{$plane->icao}}"/>
                            </td>

                            <td class="px-1 py-2 items-center text-center">
                                {{$plane->icao}}
                            </td>

                            <td class="px-1 py-2 items-center text-center">
                                {{$plane->country}}
                            </td>

                            <td class="px-1 py-2 items-center text-center">
                                {{$plane->category}}
                            </td>

                            <td class="px-1 py-2 items-center text-center">
                                {{$plane->seen_last_at}}
                            </td>

                            <td class="px-1 py-2 items-center text-center">
                                {{$plane->seen_first_at}}
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    </div>
@endsection

@section('css')
    <link rel="stylesheet"
          href="{{asset('resources/airflight/style.css')}}"
          type="text/css"/>

    <link rel="stylesheet"
          href="{{asset('resources/airflight/jquery/jquery-ui-1.11.4-smoothness.css')}}"
          type="text/css"/>

    <link rel="stylesheet"
          href="{{asset('resources/airflight/ol3/ol-3.17.1.css')}}"
          type="text/css"/>

    <link rel="stylesheet"
          href="{{asset('resources/airflight/ol3/ol3-layerswitcher.css')}}"
          type="text/css"/>
@endsection

@section('js')
    <script type="text/javascript"
            src="{{asset('resources/airflight/functions.js')}}"></script>
    <script type="text/javascript"
            src="{{asset('resources/airflight/jquery/jquery-3.0.0.min.js')}}"></script>
    <script type="text/javascript"
            src="{{asset('resources/airflight/jquery/jquery-ui-1.11.4.min.js')}}"></script>

    <script src="{{asset('resources/airflight/ol3/ol-3.17.1.js')}}"
            type="text/javascript"></script>

    <script src="{{asset('resources/airflight/ol3/ol3-layerswitcher.js')}}"
            type="text/javascript"></script>

    <script type="text/javascript"
            src="{{asset('resources/airflight/config.js')}}"></script>
    <script type="text/javascript"
            src="{{asset('resources/airflight/markers.js')}}"></script>
    <script type="text/javascript"
            src="{{asset('resources/airflight/dbloader.js')}}"></script>
    <script type="text/javascript"
            src="{{asset('resources/airflight/registrations.js')}}"></script>
    <script type="text/javascript"
            src="{{asset('resources/airflight/planeObject.js')}}"></script>
    <script type="text/javascript"
            src="{{asset('resources/airflight/formatter.js')}}"></script>
    <script type="text/javascript"
            src="{{asset('resources/airflight/flags.js')}}"></script>
    <script type="text/javascript"
            src="{{asset('resources/airflight/layers.js')}}"></script>
    <script type="text/javascript"
            src="{{asset('resources/airflight/script.js')}}"></script>

    <script>
        document.addEventListener("DOMContentLoaded", () => {
            initialize();

            // Botón para ir debajo del mapa
            let btnsDownTo = document.getElementsByClassName('btn-downto-map');
            Array.from(btnsDownTo).forEach((e) => {
                e.addEventListener('click', () => {
                    let position = e.getBoundingClientRect().top + window.scrollY;

                    window.scrollTo({
                        top:position + 440,
                        left:0,
                        behavior:'smooth'
                    });
                });
            });

            // Botón para ir encima del mapa
            let btnsUpTo = document.getElementsByClassName('btn-upto-map');
            Array.from(btnsUpTo).forEach((e) => {
                e.addEventListener('click', () => {
                    let position = e.getBoundingClientRect().top - window.scrollY;

                    window.scrollTo({
                        top:position,
                        left:0,
                        behavior:'smooth'
                    });
                });
            });

            addEventOnClick('.btn-reset-map', resetMap);
            addEventOnClick('.btn-reload-map', reloadHistorial);
            addEventOnClick('.map-selected-callsign', toggleFollowSelected);
            addEventOnClick('.map-selected-follow', toggleFollowSelected);
            addEventOnClick('.map-tableinfo-title-icao', sortByICAO);
            addEventOnClick('.map-tableinfo-title-country', sortByCountry);
            addEventOnClick('.map-tableinfo-title-flight', sortByFlight);
            addEventOnClick('.map-tableinfo-title-squawk', sortBySquawk);
            addEventOnClick('.map-tableinfo-title-altitude', sortByAltitude);
            addEventOnClick('.map-tableinfo-title-speed', sortBySpeed);
            addEventOnClick('.map-tableinfo-title-distance', sortByDistance);
            addEventOnClick('.map-tableinfo-title-track', sortByTrack);
            addEventOnClick('.map-tableinfo-title-messages', sortByMsgs);
            addEventOnClick('.map-tableinfo-title-seen', sortBySeen);
        });
    </script>
@endsection
