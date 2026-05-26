@extends('layouts.app')

@section('title', 'Vuelos en tiempo real sobre Chipiona')
@section('description', 'Registro de vuelos en tiempo real para aviones en Chipiona y alrededores')
@section('keywords', 'vuelos, aviones, chipiona, Raúl Caro Pastorino, raupulus, airplanes, airflight')

@section('rs-title', 'Vuelos en tiempo real sobre Chipiona')
@section('rs-sitename', 'Api Raupulus')
@section('rs-description', 'Registro de vuelos en tiempo real para aviones en Chipiona y alrededores')
@section('rs-image', asset('images/airflight/social-thumbnail.jpg'))
@section('rs-url', route('airflight.index'))
@section('rs-image-alt', 'Vuelos en tiempo real sobre Chipiona')

@push('head')
    {{-- CSS del mapa de vuelos (jQuery + OpenLayers) --}}
    <link rel="stylesheet" href="{{ asset('resources/airflight/style.css') }}" type="text/css"/>
    <link rel="stylesheet" href="{{ asset('resources/airflight/jquery/jquery-ui-1.11.4-smoothness.css') }}" type="text/css"/>
    <link rel="stylesheet" href="{{ asset('resources/airflight/ol3/ol-3.17.1.css') }}" type="text/css"/>
    <link rel="stylesheet" href="{{ asset('resources/airflight/ol3/ol3-layerswitcher.css') }}" type="text/css"/>
@endpush

@section('content')
    {{-- Hero --}}
    <section class="hero-gradient min-h-[40vh] flex items-center pt-20">
        <div class="max-w-7xl mx-auto px-6 text-white">
            <h1 class="text-4xl md:text-6xl font-bold tracking-tighter mb-4">Airflight</h1>
            <p class="text-xl text-white/80">Registros de vuelos en tiempo real — Chipiona</p>
        </div>
    </section>

    {{-- Descripción --}}
    <section class="py-12 bg-surface">
        <div class="max-w-5xl mx-auto px-6">
            <div class="bg-error-container border border-error/30 mb-5 text-on-surface px-4 py-3 rounded-lg" role="alert">
                <span class="text-sm">
                    Sitio <strong>temporal</strong> con la finalidad de comprobar el funcionamiento de los dispositivos que uso para recopilar los datos (raspberry pi y capturadora de televisión digital con una antena modificada para 1090Mhz)
                </span>
            </div>

            <h3 class="text-3xl text-on-surface font-bold leading-none mb-3">Sobre esta api</h3>
            <p class="text-on-surface-variant mb-2">
                Básicamente monitorizo la señal que emiten los aviones enviando su posición para no chocarse entre ellos obteniendo esos datos por ondas que decodifico y parseo para poder sacar estadísticas y trazar rutas de vuelos en tiempo real.
            </p>
            <p class="text-on-surface-variant mb-2">
                Los datos pueden no ser precisos debido a que aún se encuentra en depuración mientras voy detectando posibles problemas en el hardware que he manipulado y el software que le he construido.
            </p>
            <p class="text-on-surface-variant mb-2">
                Puedes ver el desarrollo del programa:
                <a href="https://gitlab.com/raupulus/dump1090-to-db" class="underline text-on-tertiary-container font-bold text-xs" target="_blank">
                    https://gitlab.com/raupulus/dump1090-to-db
                </a>
            </p>
            <div class="bg-surface-container border border-outline-variant/30 my-4 px-4 py-2 rounded-lg text-center">
                <span class="font-bold text-on-surface">Franja horaria UTC +0:00</span>
            </div>
        </div>
    </section>

    {{-- Mapa interactivo de vuelos --}}
    <section class="py-8 bg-surface-container-low">
        <div class="max-w-7xl mx-auto px-6">
            <h2 class="text-3xl font-bold text-on-surface mb-4 text-center">Aviones en Tiempo Real</h2>
            <p class="text-sm text-center text-on-surface-variant mb-6">
                (Selecciona el avión en el mapa o la tabla para ver el recorrido que realiza.)
            </p>

            <div id="box-airflight" class="w-full bg-surface-container-lowest rounded-xl shadow-lg overflow-hidden">
                <div class="box-information">
                    <div class="box-buttons">
                        <div class="btn-map btn-downto-map pointer">Ir debajo del Mapa</div>
                    </div>
                </div>

                <!-- Mapa -->
                <div id="map_container" class="box-map">
                    <span id="loader" class="display-hidden box-map-loader">
                        <img src="{{ asset('resources/airflight/spinny.gif') }}" id="spinny" class="spinny" alt="Cargando..."/>
                        <progress id="loader_progress" class="loader-progress"></progress>
                    </span>
                    <div id="map_canvas" class="map-canvas"></div>
                </div>

                <!-- Información del vuelo seleccionado -->
                <div id="sidebar_container" class="box-information">
                    <div id="sidebar_canvas">
                        <div id="sudo_buttons" class="box-buttons">
                            <div class="btn-map btn-reset-map pointer">Reiniciar Zoom</div>
                            <div class="btn-map btn-upto-map pointer">Ir encima del Mapa</div>
                            <div class="btn-map btn-reload-map pointer">Recargar datos</div>
                        </div>

                        <div id="dump1090_infoblock">
                            <table style="width: 100%">
                                <tr class="infoblock_heading">
                                    <td class="center"><b id="infoblock_name">Detalles del Vuelo</b></td>
                                    <td style="text-align: right"><a href="https://api.fryntiz.dev/airflight" id="dump1090_version" target="_blank"></a></td>
                                </tr>
                                <tr class="infoblock_body"><td>&nbsp;</td><td>&nbsp;</td></tr>
                                <tr class="infoblock_body dim"><td>Selecciona un avión de la tabla inferior para ver sus datos</td><td>&nbsp;</td></tr>
                                <tr class="infoblock_body"><td>&nbsp;</td><td>&nbsp;</td></tr>
                                <tr class="infoblock_body">
                                    <td>Aviones (total): <span id="dump1090_total_ac">n/a</span></td>
                                    <td>Mensajes: <span id="dump1090_message_rate">n/a</span>/sec</td>
                                </tr>
                                <tr class="infoblock_body">
                                    <td>(con posiciones): <span id="dump1090_total_ac_positions">n/a</span></td>
                                    <td>Historial: <span id="dump1090_total_history">n/a</span> posiciones</td>
                                </tr>
                            </table>
                        </div>

                        <div id="selected_infoblock" class="display-hidden">
                            <table style="width: 100%">
                                <tr class="infoblock_heading">
                                    <td colspan="2">
                                        <b><span id="selected_callsign" class="map-selected-callsign pointer">n/a</span></b>
                                        <span id="selected_follow" class="map-selected-follow pointer">&#x21D2;</span>
                                        <span id="selected_flag" class="selected-flag"><img class="selected-flag-image" src="about:blank" alt="Flag"/></span>
                                        <span id="selected_registration" class="selected-registration"></span>
                                        <span id="selected_icaotype" class="selected-icao-type"></span>
                                        <span id="selected_emergency"></span>
                                        <div style="display: inline-block;">
                                            <span style="color: green; font-size: small;">&nbsp;Ver otras plataformas:</span>
                                            <a id="selected_flightaware_link" href="" target="_blank">[FlightAware]</a>
                                            <span id="selected_links">
                                                <a id="selected_fr24_link" href="" target="_blank">[FR24]</a>
                                                <a id="selected_flightstats_link" href="" target="_blank">[FlightStats]</a>
                                                <a id="selected_planefinder_link" href="" target="_blank">[PlaneFinder]</a>
                                            </span>
                                        </div>
                                    </td>
                                </tr>
                                <tr id="infoblock_country" class="infoblock_body"><td colspan="2">País del registro: <span id="selected_country">n/a</span></td></tr>
                                <tr class="infoblock_body">
                                    <td style="width: 55%;">Altitud: <span id="selected_altitude"></span></td>
                                    <td style="width: 45%;">Squawk: <span id="selected_squawk"></span></td>
                                </tr>
                                <tr class="infoblock_body">
                                    <td>Velocidad: <span id="selected_speed">n/a</span></td>
                                    <td>RSSI: <span id="selected_rssi">n/a</span></td>
                                </tr>
                                <tr class="infoblock_body">
                                    <td>Track: <span id="selected_track">n/a</span></td>
                                    <td>Visto hace: <span id="selected_seen">n/a</span></td>
                                </tr>
                                <tr class="infoblock_body"><td colspan="2">Posición: <span id="selected_position">n/a</span></td></tr>
                                <tr class="infoblock_body"><td colspan="2">Distancia al centro de Chipiona: <span id="selected_sitedist">n/a</span></td></tr>
                            </table>
                        </div>

                        <div id="planes_table" class="box-table-planes">
                            <table id="tableinfo" class="table-info">
                                <thead>
                                <tr>
                                    <td id="icao" class="map-tableinfo-title-icao">ICAO</td>
                                    <td id="flag" class="map-tableinfo-title-country"></td>
                                    <td id="flight" class="map-tableinfo-title-flight">Vuelo</td>
                                    <td id="squawk" class="map-tableinfo-title-squawk">Squawk</td>
                                    <td id="altitude" class="map-tableinfo-title-altitude">Altitud ft</td>
                                    <td id="speed" class="map-tableinfo-title-speed">Velocidad kt</td>
                                    <td id="distance" class="map-tableinfo-title-distance">Distancia nm</td>
                                    <td id="track" class="map-tableinfo-title-track">Track</td>
                                    <td id="msgs" class="map-tableinfo-title-messages">Msgs</td>
                                    <td id="seen" class="map-tableinfo-title-seen">Visto s</td>
                                </tr>
                                </thead>
                                <tbody>
                                <tr id="plane_row_template" class="plane_table_row display-hidden">
                                    <td>ICAO</td>
                                    <td><img class="plane-table-image" src="about:blank" alt="Flag"/></td>
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
                    </div>
                </div>

                <div id="SpecialSquawkWarning" class="display-hidden special-squawk-warning">
                    <b>Squawk 7x00 se informa y se muestra.</b><br/>
                    Lo más probable es que se trate de un error en la recepción o decodificación.
                </div>
                <div id="update_error" class="display-hidden update-error">
                    <b>Problema sincronizando datos.</b><br/>
                    <span id="update_error_detail"></span><br/>
                    El mapa mostrado puede estar desactualizado, recárguelo para reintentarlo.
                </div>
                <div id="container_splitter"></div>
            </div>
        </div>
    </section>

    {{-- Tabla de aviones detectados --}}
    <section class="py-12 bg-surface">
        <div class="max-w-7xl mx-auto px-6">
            <h2 class="text-3xl font-bold text-on-surface mb-6">Aviones detectados (última hora)</h2>

            @if($planes->count() > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-max w-full table-auto text-sm">
                        <thead>
                            <tr class="bg-inverse-surface text-inverse-on-surface">
                                <td class="px-3 py-2 text-center">ICAO</td>
                                <td class="px-3 py-2 text-center">Callsign</td>
                                <td class="px-3 py-2 text-center">Altitud (ft)</td>
                                <td class="px-3 py-2 text-center">Velocidad (kt)</td>
                                <td class="px-3 py-2 text-center">Dirección</td>
                                <td class="px-3 py-2 text-center">Latitud</td>
                                <td class="px-3 py-2 text-center">Longitud</td>
                                <td class="px-3 py-2 text-center">Squawk</td>
                                <td class="px-3 py-2 text-center">Visto última vez</td>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($planes as $plane)
                                <tr class="bg-surface-container-lowest border-b border-outline-variant/20">
                                    <td class="px-3 py-2 text-center text-on-surface font-mono">{{ $plane->icao ?? '-' }}</td>
                                    <td class="px-3 py-2 text-center text-on-surface font-bold">{{ $plane->callsign ?? '-' }}</td>
                                    <td class="px-3 py-2 text-center text-on-surface">{{ $plane->altitude ?? '-' }}</td>
                                    <td class="px-3 py-2 text-center text-on-surface">{{ $plane->speed ?? '-' }}</td>
                                    <td class="px-3 py-2 text-center text-on-surface">{{ $plane->track ?? '-' }}°</td>
                                    <td class="px-3 py-2 text-center text-on-surface">{{ $plane->lat ?? '-' }}</td>
                                    <td class="px-3 py-2 text-center text-on-surface">{{ $plane->lon ?? '-' }}</td>
                                    <td class="px-3 py-2 text-center text-on-surface">{{ $plane->squawk ?? '-' }}</td>
                                    <td class="px-3 py-2 text-center text-on-surface">{{ $plane->seen_last_at ?? '-' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-6">
                    {{ $planes->links() }}
                </div>
            @else
                <div class="bg-surface-container rounded-xl p-12 text-center">
                    <span class="material-symbols-outlined text-6xl text-on-surface-variant mb-4">flight_land</span>
                    <p class="text-on-surface-variant text-lg">No se han detectado aviones en la última hora.</p>
                </div>
            @endif
        </div>
    </section>
@endsection

@push('scripts')
    {{-- jQuery y dependencias del mapa (solo en esta página) --}}
    <script type="text/javascript" src="{{ asset('resources/airflight/functions.js') }}"></script>
    <script type="text/javascript" src="{{ asset('resources/airflight/jquery/jquery-3.0.0.min.js') }}"></script>
    <script type="text/javascript" src="{{ asset('resources/airflight/jquery/jquery-ui-1.11.4.min.js') }}"></script>
    <script src="{{ asset('resources/airflight/ol3/ol-3.17.1.js') }}" type="text/javascript"></script>
    <script src="{{ asset('resources/airflight/ol3/ol3-layerswitcher.js') }}" type="text/javascript"></script>
    <script type="text/javascript" src="{{ asset('resources/airflight/config.js') }}"></script>
    <script type="text/javascript" src="{{ asset('resources/airflight/markers.js') }}"></script>
    <script type="text/javascript" src="{{ asset('resources/airflight/dbloader.js') }}"></script>
    <script type="text/javascript" src="{{ asset('resources/airflight/registrations.js') }}"></script>
    <script type="text/javascript" src="{{ asset('resources/airflight/planeObject.js') }}"></script>
    <script type="text/javascript" src="{{ asset('resources/airflight/formatter.js') }}"></script>
    <script type="text/javascript" src="{{ asset('resources/airflight/flags.js') }}"></script>
    <script type="text/javascript" src="{{ asset('resources/airflight/layers.js') }}"></script>
    <script type="text/javascript" src="{{ asset('resources/airflight/script.js') }}"></script>

    <script>
        document.addEventListener("DOMContentLoaded", () => {
            initialize();

            // Botón para ir debajo del mapa
            let btnsDownTo = document.getElementsByClassName('btn-downto-map');
            Array.from(btnsDownTo).forEach((e) => {
                e.addEventListener('click', () => {
                    let position = e.getBoundingClientRect().top + window.scrollY;
                    window.scrollTo({ top: position + 440, left: 0, behavior: 'smooth' });
                });
            });

            // Botón para ir encima del mapa
            let btnsUpTo = document.getElementsByClassName('btn-upto-map');
            Array.from(btnsUpTo).forEach((e) => {
                e.addEventListener('click', () => {
                    let position = e.getBoundingClientRect().top - window.scrollY;
                    window.scrollTo({ top: position, left: 0, behavior: 'smooth' });
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
@endpush
