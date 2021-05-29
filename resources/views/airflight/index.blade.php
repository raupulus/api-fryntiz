@extends('layouts.app')

{{-- Descripción sobre esta página --}}
@section('title', 'Vuelos en Chipiona')
@section('description', '')
@section('keywords', '')

{{-- Etiquetas para Redes sociales --}}
@section('rs-title', '')
@section('rs-sitename', '')
@section('rs-description', '')
@section('rs-image', '')
@section('rs-url', '')
@section('rs-image-alt', '')

@section('twitter-site', '')
@section('twitter-creator', '')

{{-- Marca el elemento del menú que se encuentra activo --}}
@section('active-index', 'active')

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
                        <div class="bg-red-100 border border-red-400 m-2 mb-5 text-red-700 px-2 py-2 rounded relative"
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
                            Puedes ver el desarrollo del programa para obtener los datos
                            de los aviones detectados con la tarjeta de televisión y una
                            raspberry pi con el que los exporto a una db:

                            <a href="https://gitlab.com/fryntiz/dump1090-to-db"
                               class="underline text-lightBlue-500 background-transparent font-bold text-xs outline-none focus:outline-none ease-linear transition-all duration-150"
                               type="button"
                               target="_blank"
                               title="Exportador dump1090 a db postgresql">
                                https://gitlab.com/fryntiz/dump1090-to-db
                            </a>
                        </p>

                        <div class="bg-yellow-100 border border-red-400 m-2 my-4 text-red-700 px-2 py-2 rounded relative text-center"
                             role="alert">
                            <span class="inline font-bold">
                                Franja horaria UTC +0:00
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>

    {{-- Mapa con los vuelos --}}
    <section class="bg-white border-b">
        <div class="container max-w-5xl mx-auto m-4">
            <h2 class="w-full my-2 text-4xl font-bold leading-tight text-center text-gray-800">
                Vuelos recientes
            </h2>

            <div id="map_container">
                <div id="map_canvas"></div>
            </div>

            <div id="map_container">
                <div id="map_canvas"></div>
            </div>

            <div id="sidebar_container">
                <div id="sidebar_canvas">
                    <div id="timestamps">
                        <table style="width: 100%">
                            <tr>
                                <td style="text-align: center"> <canvas id="utcclock"></canvas> </td>
                                <td style="text-align: center"> <canvas id="receiverclock"></canvas> </td>
                            </tr>

                            <tr>
                                <td style="text-align: center">UTC</td>
                                <td style="text-align: center">Last Update</td>
                            </tr>
                        </table>
                    </div> <!-- timestamps -->

                    <div id="sudo_buttons">
                        <table style="width: 100%">
                            <tr>
                                <td style="width: 150px; text-align: center;
                                cursor: pointer;"
                                    class="pointer">
                                    [ <span onclick="resetMap();">Reset Map</span> ]
                                </td>
                            </tr>
                        </table>
                    </div> <!-- sudo_buttons -->



                    <div id="planes_table">
                        <table id="tableinfo" style="width: 100%">
                            <thead style="background-color: #BBBBBB; cursor: pointer;">
                            <tr>
                                <td id="icao" onclick="sortByICAO();">ICAO</td>
                                <td id="flag" onclick="sortByCountry()"><!-- column for flag image --></td>
                                <td id="flight" onclick="sortByFlight();">Flight</td>
                                <td id="squawk" onclick="sortBySquawk();" style="text-align: right">Squawk</td>
                                <td id="altitude" onclick="sortByAltitude();" style="text-align: right">Altitude</td>
                                <td id="speed" onclick="sortBySpeed();" style="text-align: right">Speed</td>
                                <td id="distance" onclick="sortByDistance();" style="text-align: right">Distance</td>
                                <td id="track" onclick="sortByTrack();" style="text-align: right">Track</td>
                                <td id="msgs" onclick="sortByMsgs();" style="text-align: right">Msgs</td>
                                <td id="seen" onclick="sortBySeen();" style="text-align: right">Age</td>
                            </tr>
                            </thead>
                            <tbody>
                            <tr id="plane_row_template" class="plane_table_row hidden">
                                <td>ICAO</td>
                                <td><img style="width: 20px; height=12px" src="about:blank" alt="Flag"></td>
                                <td>FLIGHT</td>
                                <td style="text-align: right">SQUAWK</td>
                                <td style="text-align: right">ALTITUDE</td>
                                <td style="text-align: right">SPEED</td>
                                <td style="text-align: right">DISTANCE</td>
                                <td style="text-align: right">TRACK</td>
                                <td style="text-align: right">MSGS</td>
                                <td style="text-align: right">SEEN</td>
                            </tr>
                            </tbody>
                        </table>
                    </div> <!-- planes_table -->

                </div> <!-- sidebar_canvas -->
            </div> <!-- sidebar_container -->
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
                            ICAO
                        </th>

                        <th class="px-1 py-2 text-gray-300 capitalize text-center">
                            Categoría
                        </th>

                        <th class="px-1 py-2 text-gray-300 capitalize text-center">
                            Visto primera vez
                        </th>

                        <th class="px-1 py-2 text-gray-300 capitalize text-center">
                            Visto última vez
                        </th>
                    </tr>
                    </thead>

                    <tbody class="bg-gray-200">
                    @foreach($planes as $plane)
                        <tr class="bg-white border-4 border-gray-200">
                            <td class="px-1 py-2 items-center text-center">
                                {{$plane->icao}}
                            </td>

                            <td class="px-1 py-2 items-center text-center">
                                {{$plane->category}}
                            </td>

                            <td class="px-1 py-2 items-center text-center">
                                {{$plane->seen_first_at}}
                            </td>

                            <td class="px-1 py-2 items-center text-center">
                                {{$plane->seen_last_at}}
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
    </section>
@endsection

@section('css')
    <link rel="stylesheet"
          href="{{asset('resources/airflight/ol3/ol-3.17.1.css')}}"
          type="text/css" />

    <link rel="stylesheet"
          href="{{asset('resources/airflight/ol3/ol3-layerswitcher.css')}}" type="text/css"/>
@endsection

@section('js')
    <script
        src="https://code.jquery.com/jquery-3.6.0.min.js"
        integrity="sha256-/xUj+3OJU5yExlq6GSYGSHk7tPXikynS7ogEvDej/m4="
        crossorigin="anonymous"></script>

    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js" integrity="sha256-VazP97ZCwtekAsvgPBSUwPFKdrwD3unUfSGVYrahUqU=" crossorigin="anonymous"></script>

    <script src="{{asset('resources/airflight/ol3/ol-3.17.1.js')}}"
            type="text/javascript"></script>

    <script src="{{asset('resources/airflight/ol3/ol3-layerswitcher.js')}}"
            type="text/javascript"></script>

    <script type="text/javascript" src="{{asset('resources/airflight/config.js')}}"></script>
    <script type="text/javascript" src="{{asset('resources/airflight/markers.js')}}"></script>
    <script type="text/javascript" src="{{asset('resources/airflight/dbloader.js')}}"></script>
    <script type="text/javascript" src="{{asset('resources/airflight/registrations.js')}}"></script>
    <script type="text/javascript" src="{{asset('resources/airflight/planeObject.js')}}"></script>
    <script type="text/javascript" src="{{asset('resources/airflight/formatter.js')}}"></script>
    <script type="text/javascript" src="{{asset('resources/airflight/flags.js')}}"></script>
    <script type="text/javascript" src="{{asset('resources/airflight/layers.js')}}"></script>
    <script type="text/javascript" src="{{asset('resources/airflight/script.js')}}"></script>
    <script type="text/javascript" src="{{asset('resources/airflight/coolclock/excanvas.js')}}"></script>
    <script type="text/javascript" src="{{asset('resources/airflight/coolclock/coolclock.js')}}"></script>
    <script type="text/javascript" src="{{asset('resources/airflight/coolclock/moreskins.js')}}"></script>

    <script>
        window.document.addEventListener('DOMContentLoaded', () => {
            initialize();
        });
    </script>
@endsection
