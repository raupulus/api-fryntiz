<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <script
        src="https://code.jquery.com/jquery-3.6.0.min.js"
        integrity="sha256-/xUj+3OJU5yExlq6GSYGSHk7tPXikynS7ogEvDej/m4="
        crossorigin="anonymous"></script>

    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js" integrity="sha256-VazP97ZCwtekAsvgPBSUwPFKdrwD3unUfSGVYrahUqU=" crossorigin="anonymous"></script>

    <link rel="stylesheet"
          href="{{asset('resources/airflight/ol3/ol-3.17.1.css')}}"
          type="text/css" />
    <script src="{{asset('resources/airflight/ol3/ol-3.17.1.js')}}"
            type="text/javascript"></script>

    <link rel="stylesheet"
          href="{{asset('resources/airflight/ol3/ol3-layerswitcher.css')}}" type="text/css"/>
    <script src="{{asset('resources/airflight/ol3/ol3-layerswitcher.js')}}"
            type="text/javascript"></script>

    <link
        href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css"
        rel="stylesheet"
        integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T"
        crossorigin="anonymous">

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

    <title>Vuelos en Chipiona</title>
</head>

<body onload="initialize()">

<div class="container mt-5">
    <div class="row">
        <div class="col-md-12 text-center">
            <h1>API - Modo depuración temporalmente</h1>

            <p>
                Datos no fiables ni sitio final, solo se muestran datos para
                depurar subida e integridad de datos.
            </p>

            <p class="alert alert-danger text-left">
                <span class="text-danger ">
                    Los datos pueden estar manipulados puntualmente o ser borrados
                    inesperadamente ya que a veces el objetivo es preparar
                    entornos en los que quiero observar el comportamiento ante
                    ciertas situaciones.

                    <br/>

                    Aún así los datos mostrados son tomados por una
                    capturadora de televisión digital que alcanza el rango 1090Mhz

                    <br/>

                    Una vez acabada la aplicación desaparecerá quedando
                    en el subdominio:
                    <a href="https://eltiempo.desdechipiona.es"
                       title="El tiempo Desde Chipiona">
                        https://eltiempo.desdechipiona.es
                    </a>
                </span>
            </p>

            <p>
                Puedes ver el desarrollo de la API aquí:

                <a href="https://gitlab.com/fryntiz/api-fryntiz/tree/master"
                   title="Api fryntiz en GitLab">
                    https://gitlab.com/fryntiz/api-fryntiz
                </a>
            </p>

            <p>
                Puedes ver el desarrollo del programa para obtener los datos
                de los aviones detectados con la tarjeta de televisión y una
                raspberry pi:

                <a href="https://gitlab.com/fryntiz/dump1090-to-db"
                   title="Exportador dump1090 a db postgresql">
                    https://gitlab.com/fryntiz/dump1090-to-db
                </a>
            </p>

            <p class="alert alert-danger text-left">
                <span class="text-danger">
                    Todas las horas están en formato UTC
                </span>
            </p>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <h2>
                Vuelos recientes
            </h2>
        </div>

        <div class="col-md-12">
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
    </div>

    <div class="row">
        <div class="col-md-12">
            <h2>
                Aviones en la última hora
            </h2>
        </div>

        <div class="col-md-12">
            <table class="table table-bordered table-dark">
                <head>
                    <tr>
                        <th>
                            ICAO
                        </th>

                        <th>
                            Categoría
                        </th>

                        <th>
                            Visto primera vez
                        </th>

                        <th>
                            Visto última vez
                        </th>
                    </tr>
                </head>

                <tbody>
                @foreach($planes as $plane)
                    <tr>
                        <td>
                            {{$plane->icao}}
                        </td>

                        <td>
                            {{$plane->category}}
                        </td>

                        <td>
                            {{$plane->seen_first_at}}
                        </td>

                        <td>
                            {{$plane->seen_last_at}}
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>

            {{-- Paginación --}}
            {{$planes->links()}}
        </div>
    </div>
</div>
</body>
</html>

