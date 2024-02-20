@extends('layouts.app')

{{-- Descripción sobre esta página --}}
@section('title', 'API Privada de Raúl Caro Pastorino')
@section('description', 'Api privada dónde depuro desarrollos y monitorizo datos de mis aplicaciones')
@section('keywords', 'Raúl Caro Pastorino, raupulus, chipiona, api, laravel, vue.js, javascript, php, tailwind, tailwindcss, postgresql, mysql, mariadb, linux, debian, fedora, gnu, open source, software libre, programador, desarrollador, developer, web, web developer, informático, redes, sistema, ingeniero')

{{-- Etiquetas para Redes sociales --}}
@section('rs-title', 'API Privada de Raúl Caro Pastorino')
@section('rs-sitename', 'API Raupulus')
@section('rs-description', 'Api privada dónde depuro desarrollos y monitorizo datos de mis aplicaciones')
@section('rs-image', asset('images/social-thumbnail.jpg'))
@section('rs-url', route('weather_station.index'))
@section('rs-image-alt', 'API Privada de Raúl Caro Pastorino')

@section('meta-twitter-title', 'API Privada de Raúl Caro Pastorino')

@section('content')
    <div class="leading-normal tracking-normal text-white gradient"
         style="font-family: 'Source Sans Pro', sans-serif;">

        <div class="pt-24">
            <div
                class="container px-3 mx-auto flex flex-wrap flex-col md:flex-row items-center">
                {{-- Columna Izquierda --}}
                <div
                    class="flex flex-col w-full md:w-2/5 justify-center items-start text-center md:text-left">
                    <p class="tracking-loose w-full">
                        {{request()->url()}}
                    </p>

                    <h1 class="my-4 text-5xl font-bold leading-tight">
                        API
                        <br/>
                        Raúl Caro Pastorino
                    </h1>

                    <p class="leading-normal text-2xl mb-8 font-medieval">
                        Una api para depurarlas a todas...
                    </p>

                    <a href="https://raupulus.dev"
                       target="_blank"
                       class="mx-auto lg:mx-0 hover:underline bg-white text-gray-800 font-bold rounded-full my-6 py-4 px-8 shadow-lg focus:outline-none focus:shadow-outline transform transition hover:scale-105 duration-300 ease-in-out">
                        Sobre Mi
                    </a>
                </div>

                {{-- Columna Derecha --}}
                <div class="w-full md:w-3/5 py-6 text-center">
                    <img class="m-auto w-full sm:4/5 md:w-3/5 z-50"
                         src="{{asset('images/logo-fryntiz-512x512.png')}}"/>
                </div>
            </div>
        </div>

        <div class="relative -mt-11 lg:-mt-22">
            <svg viewBox="0 0 1428 174" version="1.1"
                 xmlns="http://www.w3.org/2000/svg">
                <g stroke="none" stroke-width="1" fill="none"
                   fill-rule="evenodd">
                    <g transform="translate(-2.000000, 44.000000)"
                       fill="#FFFFFF" fill-rule="nonzero">
                        <path
                            d="M0,0 C90.7283404,0.927527913 147.912752,27.187927 291.910178,59.9119003 C387.908462,81.7278826 543.605069,89.334785 759,82.7326078 C469.336065,156.254352 216.336065,153.6679 0,74.9732496"
                            opacity="0.100000001"></path>
                        <path
                            d="M100,104.708498 C277.413333,72.2345949 426.147877,52.5246657 546.203633,45.5787101 C666.259389,38.6327546 810.524845,41.7979068 979,55.0741668 C931.069965,56.122511 810.303266,74.8455141 616.699903,111.243176 C423.096539,147.640838 250.863238,145.462612 100,104.708498 Z"
                            opacity="0.100000001"
                        ></path>
                        <path
                            d="M1046,51.6521276 C1130.83045,29.328812 1279.08318,17.607883 1439,40.1656806 L1439,120 C1271.17211,77.9435312 1140.17211,55.1609071 1046,51.6521276 Z"
                            id="Path-4" opacity="0.200000003"></path>
                    </g>
                    <g transform="translate(-4.000000, 76.000000)"
                       fill="#FFFFFF" fill-rule="nonzero">
                        <path
                            d="M0.457,34.035 C57.086,53.198 98.208,65.809 123.822,71.865 C181.454,85.495 234.295,90.29 272.033,93.459 C311.355,96.759 396.635,95.801 461.025,91.663 C486.76,90.01 518.727,86.372 556.926,80.752 C595.747,74.596 622.372,70.008 636.799,66.991 C663.913,61.324 712.501,49.503 727.605,46.128 C780.47,34.317 818.839,22.532 856.324,15.904 C922.689,4.169 955.676,2.522 1011.185,0.432 C1060.705,1.477 1097.39,3.129 1121.236,5.387 C1161.703,9.219 1208.621,17.821 1235.4,22.304 C1285.855,30.748 1354.351,47.432 1440.886,72.354 L1441.191,104.352 L1.121,104.031 L0.457,34.035 Z"
                        ></path>
                    </g>
                </g>
            </svg>
        </div>

        <section class="bg-white border-b py-8">
            <div class="container max-w-5xl mx-auto m-8">
                <h1 class="w-full my-2 text-5xl font-bold leading-tight text-center text-gray-800">
                    API Privada de Raúl Caro Pastorino
                </h1>

                <div class="w-full mb-4">
                    <div
                        class="h-1 mx-auto gradient w-64 opacity-25 my-0 py-0 rounded-t"></div>
                </div>

                {{-- Información general sobre el proyecto --}}
                <div class="flex flex-wrap content-center">
                    <div class="w-5/6 sm:w-1/2 p-6">
                        <h3 class="text-3xl text-gray-800 font-bold leading-none mb-3">
                            Sobre esta api
                        </h3>

                        <p class="text-gray-600 mb-3">
                            Esta api es para consumo personal en proyectos
                            que desarrollo como código público .
                        </p>

                        <p class="text-gray-600 mb-3">
                            El contenido puede ser poco preciso o irreal ya
                            que lo utilizo como una herramienta más para
                            desarrollar y depurar.
                        </p>

                        <p class="text-gray-600 mb-3">
                            Aún así busco afinar la entrada de datos y
                            utilizarlo como parte de la aplicación para la
                            parte del cliente final.
                        </p>

                        <p class="text-gray-600 mb-3">
                            Puedes ver el desarrollo de la api aquí:

                            <a href="https://gitlab.com/raupulus/api-fryntiz/tree/master"
                               class="underline text-lightBlue-500 background-transparent font-bold text-xs outline-none focus:outline-none ease-linear transition-all duration-150"
                               type="button"
                               target="_blank"
                               title="Api Raupulus en GitLab">
                                https://gitlab.com/raupulus/api-fryntiz
                            </a>
                        </p>
                    </div>

                    {{-- Imagen --}}
                    <div class="w-full sm:w-1/2 p-6">
                        <img src="{{asset('images/main-thumbnail.jpg')}}"
                             class="m-auto w-192 h-192"
                             alt="Logo Raupulus"/>
                    </div>
                </div>


                {{-- Estación Meteorológica --}}
                <div class="flex flex-wrap flex-col-reverse sm:flex-row mt-12">
                    {{-- Imagen --}}
                    <div class="w-full sm:w-1/2 p-6">
                        <img
                            src="{{asset('images/wheater-station/wheater-station-main-thumbnail.jpg')}}"
                            alt="Logo estación meteorológica en Chipiona"/>
                    </div>

                    <div class="w-full sm:w-1/2 p-6">
                        <div class="align-middle">
                            <h3 class="text-3xl text-gray-800 font-bold leading-none mb-3">
                                <a href="{{route('weather_station.index')}}">
                                    Estación Meteorológica
                                </a>
                            </h3>

                            <p class="text-gray-600 mb-3">
                                La estación meteorológica es un proyecto
                                con raspberry pi, arduino y una serie de
                                sensores
                                tomando datos en mi localidad,
                                <strong>Chipiona</strong>.
                            </p>

                            <p class="text-gray-600 mb-3">
                                Humedad, Temperatura, Presión atmosférica,
                                Viento (Velocidad, dirección y ráfagas),
                                Cantidad de luz en general, Indice UV, UVA,
                                UVB, CO2-ECO2, TVOC, Calidad del aire,
                                Relámpagos (Cantidad, distancia y potencia)
                            </p>

                            <p class="text-gray-600 mb-3">
                                Puedes ver el desarrollo de rpi (python3 y
                                sqlite) aquí:

                                <a href="https://gitlab.com/raupulus/raspberry-weather-station"
                                   class="underline text-lightBlue-500 background-transparent font-bold text-xs outline-none focus:outline-none ease-linear transition-all duration-150"
                                   type="button"
                                   target="_blank"
                                   title="Enlace al repositorio de mi estación meteorológica de Chipiona en python3 para raspberry pi">
                                    https://gitlab.com/raupulus/raspberry-weather-station
                                </a>
                            </p>
                        </div>
                    </div>
                </div>

                {{-- Smart Plant --}}
                <div class="flex flex-wrap content-center mt-12">
                    <div class="w-5/6 sm:w-1/2 p-6">
                        <h3 class="text-3xl text-gray-800 font-bold leading-none mb-3">
                            <a href="{{route('smartplant.index')}}">
                                Smart Plant
                            </a>
                        </h3>

                        <p class="text-gray-600 mb-3">
                            Proyecto para monitorizar plantas
                            delicadas, principalmente bonsais pudiendo
                            observar el comportamiento asegurando cubrir
                            necesidades manteniendo así la salud o
                            descubrir problemas.
                        </p>

                        <p class="text-gray-600 mb-3">
                            Utilizo un <strong>esp32 lite</strong> conectado
                            a una placa solar y sensores para medir la
                            humedad en tierra/aire, temperatura, luz y tanque de
                            agua está lleno antes de activar el riego.
                        </p>

                        <p class="text-gray-600 mb-3">
                            Código del proyecto en
                            <strong>C++</strong>:

                            <a href="https://gitlab.com/raupulus/esp32-smart-bonsai"
                               class="underline text-lightBlue-500 background-transparent font-bold text-xs outline-none focus:outline-none ease-linear transition-all duration-150"
                               type="button"
                               target="_blank"
                               title="Enlace al repositorio con el código para el smart plant con esp32 lite en c++">
                                https://gitlab.com/raupulus/esp32-smart-bonsai
                            </a>
                        </p>
                    </div>

                    {{-- Imagen --}}
                    <div class="w-full sm:w-1/2 p-6">
                        <img
                            src="{{asset('images/smart-plant/smart-plant-main-thumbnail.jpg')}}"
                            class="m-auto w-192 h-192"
                            alt="Circuitos para el smart plant"/>
                    </div>
                </div>

                {{-- Contador de pulsaciones --}}
                <div class="flex flex-wrap flex-col-reverse sm:flex-row mt-12">
                    {{-- Imagen --}}
                    <div class="w-full sm:w-1/2 p-6 mt-6">
                        <img
                            src="{{asset('images/keycounter/keycounter-main-thumbnail.jpg')}}"
                            alt="Pantalla para la visualización de mi contador de teclas"/>
                    </div>

                    <div class="w-full sm:w-1/2 p-6 mt-6">
                        <div class="align-middle">
                            <h3 class="text-3xl text-gray-800 font-bold leading-none">
                                <a href="{{route('keycounter.index')}}">
                                    Key Counter
                                </a>
                            </h3>

                            <p class="text-gray-900 mb-2">
                                (Contador de teclas pulsadas y clicks de ratón)
                            </p>

                            <p class="text-gray-600 mb-3">
                                El contador de pulsaciones realiza una
                                monitorización de las teclas y clicks
                                distinguiendo teclas especiales de las
                                comunes y almacenando solo estadísticas generales sin
                                comprometer la privacidad.
                            </p>

                            <p class="text-gray-600 mb-3">
                                Se suben a esta api las estadísticas por
                                rachas tras 15 segundos sin ninguna interacción.
                            </p>

                            <p class="text-gray-600 mb-3">
                                Código para GNU/Linux en
                                <strong>Python3</strong>:

                                <a href="https://gitlab.com/raupulus/python-keycounter"
                                   class="underline text-lightBlue-500 background-transparent font-bold text-xs outline-none focus:outline-none ease-linear transition-all duration-150"
                                   type="button"
                                   target="_blank"
                                   title="Enlace al repositorio con el código para el contador de teclas escrito en python 3">
                                    https://gitlab.com/raupulus/python-keycounter
                                </a>
                            </p>
                        </div>
                    </div>
                </div>

                {{-- Airfligth --}}
                <div class="flex flex-wrap content-center mt-12">
                    <div class="w-5/6 sm:w-1/2 p-6">
                        <h3 class="text-3xl text-gray-800 font-bold leading-none mb-3">
                            <a href="{{route('airflight.index')}}">
                                Radar de vuelo
                            </a>
                        </h3>

                        <p class="text-gray-600 mb-3">
                            Con este radar monitorizo los aviones que
                            transmiten su posición cerca de mi ciudad.
                        </p>

                        <p class="text-gray-600 mb-3">
                            Utilizo una <strong>raspberry pi 4</strong>
                            y una capturadora de TDT usando la banda de
                            <strong>1090Mhz</strong>
                            y decodificando los datos recibidos para
                            agruparlos y preparar rutas a partir de sus
                            coordenadas.
                        </p>

                        <p class="text-gray-600 mb-3">
                            Puedes ver el código del exportador para la
                            herramienta
                            <strong>dump1090</strong>
                            a sql escrito en php desde aquí:

                            <a href="https://gitlab.com/raupulus/dump1090-to-db"
                               class="underline text-lightBlue-500 background-transparent font-bold text-xs outline-none focus:outline-none ease-linear transition-all duration-150"
                               type="button"
                               target="_blank"
                               title="Enlace al repositorio con el código para exportar datos capturados con dump1090 a sql escrito en php">
                                https://gitlab.com/raupulus/dump1090-to-db
                            </a>
                        </p>
                    </div>

                    {{-- Imagen --}}
                    <div class="w-full sm:w-1/2 p-6">
                        <img
                            src="{{asset('images/airflight/airflight-main-thumbnail.jpg')}}"
                            class="m-auto w-192 h-192"
                            alt="Dispositivos utilizados para el radar de vuelos en chipiona"/>
                    </div>
                </div>

                {{-- Blog --}}
                <div class="flex flex-wrap flex-col-reverse sm:flex-row">
                    {{-- Imagen --}}
                    <div class="w-full sm:w-1/2 p-6 mt-6">
                        <img src="{{asset('images/logo-blog.png')}}"
                             alt="Logo raupulus"/>
                    </div>

                    <div class="w-full sm:w-1/2 p-6 mt-12">
                        <div class="align-middle">
                            <h3 class="text-3xl text-gray-800 font-bold leading-none mb-3">
                                Blogs
                            </h3>

                            <p class="text-gray-600 mb-3">
                                Sistema de blogs para diversos proyectos
                                desde los que consumo las entradas de esta
                                api centralizando su mantenimiento y además
                                sincronización mediante nube, pudiendo editar
                                las entradas en Markdown desde cualquier
                                equipo y actualizándose dinámicamente en la api.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="bg-gray-100 py-8">
            <div class="container mx-auto px-2 pt-4 pb-12 text-gray-800">
                <div class="container mx-auto flex flex-wrap pt-4 pb-12">
                    <h1 class="w-full my-2 text-5xl font-bold leading-tight text-center text-gray-800">
                        Más información
                    </h1>

                    <div class="w-full mb-4">
                        <div
                            class="h-1 mx-auto gradient w-64 opacity-25 my-0 py-0 rounded-t"></div>
                    </div>

                    {{-- Author --}}
                    <div
                        class="w-full md:w-1/3 p-6 flex flex-col flex-grow flex-shrink">
                        <div
                            class="flex-1 bg-white rounded-t rounded-b-none overflow-hidden shadow">
                            <a href="https://raupulus.dev"
                               title="Web de Raúl Caro Pastorino (raupulus)"
                               target="_blank"
                               class="flex flex-wrap no-underline hover:no-underline">
                                <p class="w-full text-gray-600 text-xs md:text-sm px-6 text-right">
                                    Información sobre el autor
                                </p>

                                <div
                                    class="w-full font-bold text-xl text-gray-800 px-6">
                                    Raúl Caro Pastorino
                                </div>

                                <p class="text-gray-800 text-base px-6 mb-5">
                                    Puedes visitar mi sitio web personal en
                                    el que describo mis trabajos,
                                    colaboraciones, proyectos, formación..
                                </p>
                            </a>
                        </div>

                        <div
                            class="flex-none mt-auto bg-white rounded-b rounded-t-none overflow-hidden shadow p-6">
                            <div class="flex items-center justify-center">
                                <a href="https://raupulus.dev"
                                   title="Web de Raúl Caro Pastorino (raupulus)"
                                   target="_blank"
                                   class="mx-auto lg:mx-0 hover:underline gradient text-white font-bold rounded-full my-6 py-4 px-8 shadow-lg focus:outline-none focus:shadow-outline transform transition hover:scale-105 duration-300 ease-in-out">
                                    Explorar
                                </a>
                            </div>
                        </div>
                    </div>

                    {{-- Contacto --}}
                    <div
                        class="w-full md:w-1/3 p-6 flex flex-col flex-grow flex-shrink">
                        <div
                            class="flex-1 bg-white rounded-t rounded-b-none overflow-hidden shadow">
                            <a href="https://raupulus.dev/contact"
                               title="Contactar con Raúl Caro Pastorino (raupulus)"
                               target="_blank"
                               class="flex flex-wrap no-underline hover:no-underline">
                                <p class="w-full text-gray-600 text-xs md:text-sm px-6 text-right">
                                    Puedes contactar con el autor
                                </p>

                                <div
                                    class="w-full font-bold text-xl text-gray-800 px-6">
                                    Contactar
                                </div>

                                <p class="text-gray-800 text-base px-6 mb-5">
                                    ¿Has detectado algún error?
                                    <br/>
                                    ¿Alguna sugerencia o propuesta?
                                    <br/>
                                    ¡Contacta conmigo, estaré encantado!
                                </p>
                            </a>
                        </div>

                        <div
                            class="flex-none mt-auto bg-white rounded-b rounded-t-none overflow-hidden shadow p-6">
                            <div class="flex items-center justify-center">
                                <a href="https://raupulus.dev/contact"
                                   title="Contactar con Raúl Caro Pastorino (raupulus)"
                                   target="_blank"
                                   class="mx-auto lg:mx-0 hover:underline gradient text-white font-bold rounded-full my-6 py-4 px-8 shadow-lg focus:outline-none focus:shadow-outline transform transition hover:scale-105 duration-300 ease-in-out">
                                    Contactar
                                </a>
                            </div>
                        </div>
                    </div>

                    {{-- --}}
                    <div
                        class="w-full md:w-1/3 p-6 flex flex-col flex-grow flex-shrink">
                        <div
                            class="flex-1 bg-white rounded-t rounded-b-none overflow-hidden shadow">
                            <a href="{{route('dashboard.index')}}"
                               title="Acceso al panel de gestión para la api de Raúl Caro Pastorino"
                               target="_blank"
                               class="flex flex-wrap no-underline hover:no-underline">
                                <p class="w-full text-gray-600 text-xs md:text-sm px-6 text-right">
                                    Dashboard
                                </p>

                                <div
                                    class="w-full font-bold text-xl text-gray-800 px-6">
                                    Panel de gestión.
                                </div>

                                <p class="text-gray-800 text-base px-6 mb-5">
                                    Desde el panel de gestión se pueden crear
                                    tokens para conectar y subir o consumir
                                    los datos que se ofrecen en esta api.
                                </p>
                            </a>
                        </div>
                        <div
                            class="flex-none mt-auto bg-white rounded-b rounded-t-none overflow-hidden shadow p-6">
                            <div class="flex items-center justify-center">
                                <a href="{{route('dashboard.index')}}"
                                   title="Acceso al panel de gestión para la api de Raúl Caro Pastorino"
                                   target="_blank"
                                   class="mx-auto lg:mx-0 hover:underline gradient text-white font-bold rounded-full my-6 py-4 px-8 shadow-lg focus:outline-none focus:shadow-outline transform transition hover:scale-105 duration-300 ease-in-out">
                                    Acceder
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
@endsection

@section('css')
    <style>
        @font-face {
            font-family: Source Sans Pro;
            src: url("{{asset('fonts/SourceSans3-VariableFont_wght.ttf')}}");
        }

        @font-face {
            font-family: medieval;
            src: url("{{asset('fonts/morris-roman.black.ttf')}}");
        }

        .gradient {
            background: linear-gradient(90deg, #000062 0%, #4c4c6d 100%);
        }

        .font-medieval {
            font-family: 'medieval', 'sans-serif';
        }
    </style>
@endsection
