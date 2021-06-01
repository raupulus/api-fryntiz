@extends('layouts.app')

{{-- Descripción sobre esta página --}}
@section('title', '')
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
    <div class="leading-normal tracking-normal text-white gradient"
         style="font-family: 'Source Sans Pro', sans-serif;">

        <div class="pt-24">
            <div
                class="container px-3 mx-auto flex flex-wrap flex-col md:flex-row items-center">
                <!--Left Col-->
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

                    <button
                        class="mx-auto lg:mx-0 hover:underline bg-white text-gray-800 font-bold rounded-full my-6 py-4 px-8 shadow-lg focus:outline-none focus:shadow-outline transform transition hover:scale-105 duration-300 ease-in-out">
                        Sobre Mi
                    </button>
                </div>
                <!--Right Col-->
                <div class="w-full md:w-3/5 py-6 text-center">
                    <img class="m-auto w-full md:w-4/5 z-50"
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
                            que desarrollo como código público principalmente
                            en gitlab.
                        </p>

                        <p class="text-gray-600 mb-3">
                            El contenido puede no ser preciso o real ya que este
                            proyecto es una herramienta más para desarrollar y
                            depurar.
                        </p>

                        <p class="text-gray-600 mb-3">
                            Aún así, puede solicitarme acceso al consumo de
                            alguna parte de mi api para trabajar con sus
                            valores.
                            En algunos casos como la estación meteorológica o
                            el radar de vuelos si que <strong>intento</strong>
                            tomar datos reales afinando los sensores de mi
                            propia estación.
                        </p>

                        <p class="text-gray-600 mb-3">
                            Puedes ver el desarrollo de la api aquí:

                            <a href="https://gitlab.com/fryntiz/api-fryntiz/tree/master"
                               class="underline text-lightBlue-500 background-transparent font-bold text-xs outline-none focus:outline-none ease-linear transition-all duration-150"
                               type="button"
                               target="_blank"
                               title="Api fryntiz en GitLab">
                                https://gitlab.com/fryntiz/api-fryntiz
                            </a>
                        </p>
                    </div>

                    {{-- Imagen --}}
                    <div class="w-full sm:w-1/2 p-6">
                        <img src="{{asset('images/logo-fryntiz.png')}}"
                             class="m-auto w-192 h-192"
                             alt="Logo fryntiz"/>
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

                {{-- Estación Meteorológica --}}
                <div class="flex flex-wrap flex-col-reverse sm:flex-row">
                    {{-- Imagen --}}
                    <div class="w-full sm:w-1/2 p-6 mt-6">
                        <img src="{{asset('images/logo-fryntiz.png')}}"
                             alt="Logo fryntiz"/>
                    </div>

                    <div class="w-full sm:w-1/2 p-6 mt-6">
                        <div class="align-middle">
                            <h3 class="text-3xl text-gray-800 font-bold leading-none mb-3">
                                Estación Meteorológica
                            </h3>
                            <p class="text-gray-600 mb-8">
                                La estación meteorológica es un proyecto que
                                realizo utilizando raspberry pi, arduino y
                                una serie de sensores en mi localidad,
                                <strong>Chipiona</strong>.
                            </p>

                            <p class="text-gray-600 mb-8">
                                Las lecturas tomadas hasta el momento son:
                            </p>

                            <ul class="text-gray-600 list-disc pl-8">
                                <li>Humedad</li>
                                <li>Temperatura</li>
                                <li>Presión atmosférica</li>
                                <li>Viento (Velocidad, dirección y ráfagas)</li>
                                <li>Cantidad de luz en general</li>
                                <li>Indice UV</li>
                                <li>UVA</li>
                                <li>UVB</li>
                                <li>CO2-ECO2</li>
                                <li>TVOC</li>
                                <li>Calidad del aire</li>
                                <li>Relámpagos (Cantidad, distancia y
                                    potencia)
                                </li>
                            </ul>

                            <p class="text-gray-600 mb-8">
                                Puedes ver el desarrollo de este software en
                                python3 usando una base de datos sqlite como
                                caché temporal dónde almacenar lecturas hasta
                                subirlas a esta API desde el siguiente enlace:

                                <a href="https://gitlab.com/fryntiz/raspberry-weather-station"
                                   class="underline text-lightBlue-500 background-transparent font-bold text-xs outline-none focus:outline-none ease-linear transition-all duration-150"
                                   type="button"
                                   target="_blank"
                                   title="Enlace al repositorio de mi estación meteorológica de Chipiona en python3 para raspberry pi">
                                    https://gitlab.com/fryntiz/raspberry-weather-station
                                </a>
                            </p>
                        </div>
                    </div>
                </div>

                {{-- Smart Plant --}}
                <div class="flex flex-wrap content-center">
                    <div class="w-5/6 sm:w-1/2 p-6">
                        <h3 class="text-3xl text-gray-800 font-bold leading-none mb-3">
                            Smart Plant
                        </h3>

                        <p class="text-gray-600 mb-8">
                            Este proyecto pretende monitorizar plantas
                            delicadas, principalmente bonsais pudiendo
                            observar el comportamiento de la misma ante
                            determinadas variaciones y asegurar que se cubren
                            las necesidades básicas de cada una de elles como
                            podría ser la cantidad/horas de luz o agua.
                        </p>

                        <p class="text-gray-600 mb-8">
                            Para el proyecto he optado utilizar un
                            <strong>esp32 lite</strong> conectado a una placa
                            solar y sensores para medir la humedad en tierra,
                            humedad en aire, temperatura, cantidad de luz y
                            detectar si el tanque de agua está lleno además
                            de activar un pequeño motor de riego.
                        </p>

                        <p class="text-gray-600 mb-8">
                            El código para este proyecto está realizado en
                            <strong>C++</strong> publicado en gitlab desde el
                            siguiente enlace:

                            <a href="https://gitlab.com/fryntiz/esp32-smart-bonsai"
                               class="underline text-lightBlue-500 background-transparent font-bold text-xs outline-none focus:outline-none ease-linear transition-all duration-150"
                               type="button"
                               target="_blank"
                               title="Enlace al repositorio con el código para el smart plant con esp32 lite en c++">
                                https://gitlab.com/fryntiz/esp32-smart-bonsai
                            </a>
                        </p>
                    </div>

                    {{-- Imagen --}}
                    <div class="w-full sm:w-1/2 p-6">
                        <img src="{{asset('images/logo-smart-plant.png')}}"
                             class="m-auto w-192 h-192"
                             alt="Logo fryntiz"/>
                    </div>
                </div>

                {{-- Contador de pulsaciones --}}
                <div class="flex flex-wrap flex-col-reverse sm:flex-row">
                    {{-- Imagen --}}
                    <div class="w-full sm:w-1/2 p-6 mt-6">
                        <img src="{{asset('images/logo-keycounter.png')}}"
                             alt="Logo fryntiz"/>
                    </div>

                    <div class="w-full sm:w-1/2 p-6 mt-6">
                        <div class="align-middle">
                            <h3 class="text-3xl text-gray-800 font-bold leading-none">
                                Key Counter
                            </h3>

                            <p class="text-gray-900 mb-3">
                                (Contador de teclas pulsadas y clicks de ratón)
                            </p>

                            <p class="text-gray-600 mb-8">
                                El contador de pulsaciones realiza una
                                monitorización de las teclas pulsadas además
                                de los clicks de ratón distinguiendo teclas
                                especiales de las comunes para escribir
                                normalmente (Control, enter, alt..)
                                almacenando solo estadísticas generales sin
                                comprometer la privacidad.
                            </p>

                            <p class="text-gray-600 mb-8">
                                Esta aplicación para tomar datos que luego se
                                subirán a la api realiza las estadísticas por
                                rachas recomenzando una nueva racha tras 15
                                segundos sin ninguna interacción.
                            </p>

                            <p class="text-gray-600 mb-8">
                                El código solo para sistemas GNU/Linux lo he
                                realizado en
                                <strong>Python3</strong>
                                y puedes ver el desarrollo aquí:

                                <a href="https://gitlab.com/fryntiz/python-keycounter"
                                   class="underline text-lightBlue-500 background-transparent font-bold text-xs outline-none focus:outline-none ease-linear transition-all duration-150"
                                   type="button"
                                   target="_blank"
                                   title="Enlace al repositorio con el código para el contador de teclas escrito en python 3">
                                    https://gitlab.com/fryntiz/python-keycounter
                                </a>
                            </p>
                        </div>
                    </div>
                </div>

                {{-- Airfligth --}}
                <div class="flex flex-wrap content-center">
                    <div class="w-5/6 sm:w-1/2 p-6">
                        <h3 class="text-3xl text-gray-800 font-bold leading-none mb-3">
                            Radar de vuelo
                        </h3>

                        <p class="text-gray-600 mb-8">
                            Con este radar monitorizo los aviones que
                            transmiten su posición cerca de mi ciudad.
                        </p>

                        <p class="text-gray-600 mb-8">
                            Para tal fin utilizo una <strong>raspberry pi 4</strong>
                            y una capturadora de televisión digital usando
                            la banda de
                            <strong>1090Mhz</strong>
                            y decodificando los datos recibidos para
                            agruparlos y preparar rutas a partir de las
                            coordenadas si las tuvieras.
                        </p>

                        <p class="text-gray-600 mb-8">
                            Puedes ver el código del exportador para la
                            herramienta
                            <strong>dump1090</strong>
                            a sql escrito en php desde aquí:

                            <a href="https://gitlab.com/fryntiz/dump1090-to-db"
                               class="underline text-lightBlue-500 background-transparent font-bold text-xs outline-none focus:outline-none ease-linear transition-all duration-150"
                               type="button"
                               target="_blank"
                               title="Enlace al repositorio con el código para exportar datos capturados con dump1090 a sql escrito en php">
                                https://gitlab.com/fryntiz/dump1090-to-db
                            </a>
                        </p>
                    </div>

                    {{-- Imagen --}}
                    <div class="w-full sm:w-1/2 p-6">
                        <img src="{{asset('images/logo-airflight.png')}}"
                             class="m-auto w-192 h-192"
                             alt="Logo fryntiz"/>
                    </div>
                </div>

                {{-- Blog --}}
                <div class="flex flex-wrap flex-col-reverse sm:flex-row">
                    {{-- Imagen --}}
                    <div class="w-full sm:w-1/2 p-6 mt-6">
                        <img src="{{asset('images/logo-blog.png')}}"
                             alt="Logo fryntiz"/>
                    </div>

                    <div class="w-full sm:w-1/2 p-6 mt-6">
                        <div class="align-middle">
                            <h3 class="text-3xl text-gray-800 font-bold leading-none mb-3">
                                Blog
                            </h3>
                            <p class="text-gray-600 mb-8">
                                Description
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
                            <a href="#"
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
                                    Lorem ipsum dolor sit amet, consectetur
                                    adipiscing elit. Aliquam at ipsum eu nunc
                                    commodo posuere et sit amet ligula.
                                </p>
                            </a>
                        </div>

                        <div
                            class="flex-none mt-auto bg-white rounded-b rounded-t-none overflow-hidden shadow p-6">
                            <div class="flex items-center justify-start">
                                <button
                                    class="mx-auto lg:mx-0 hover:underline gradient text-white font-bold rounded-full my-6 py-4 px-8 shadow-lg focus:outline-none focus:shadow-outline transform transition hover:scale-105 duration-300 ease-in-out">
                                    Ir
                                </button>
                            </div>
                        </div>
                    </div>

                    {{-- Contacto --}}
                    <div
                        class="w-full md:w-1/3 p-6 flex flex-col flex-grow flex-shrink">
                        <div
                            class="flex-1 bg-white rounded-t rounded-b-none overflow-hidden shadow">
                            <a href="#"
                               class="flex flex-wrap no-underline hover:no-underline">
                                <p class="w-full text-gray-600 text-xs md:text-sm px-6 text-right">
                                    Puedes contactar con el autor
                                </p>
                                <div
                                    class="w-full font-bold text-xl text-gray-800 px-6">
                                    Contactar
                                </div>
                                <p class="text-gray-800 text-base px-6 mb-5">
                                    Lorem ipsum dolor sit amet, consectetur
                                    adipiscing elit. Aliquam at ipsum eu nunc
                                    commodo posuere et sit amet ligula.
                                </p>
                            </a>
                        </div>

                        <div
                            class="flex-none mt-auto bg-white rounded-b rounded-t-none overflow-hidden shadow p-6">
                            <div class="flex items-center justify-center">
                                <button
                                    class="mx-auto lg:mx-0 hover:underline gradient text-white font-bold rounded-full my-6 py-4 px-8 shadow-lg focus:outline-none focus:shadow-outline transform transition hover:scale-105 duration-300 ease-in-out">
                                    Ir
                                </button>
                            </div>
                        </div>
                    </div>

                    {{-- --}}
                    <div
                        class="w-full md:w-1/3 p-6 flex flex-col flex-grow flex-shrink">
                        <div
                            class="flex-1 bg-white rounded-t rounded-b-none overflow-hidden shadow">
                            <a href="#"
                               class="flex flex-wrap no-underline hover:no-underline">
                                <p class="w-full text-gray-600 text-xs md:text-sm px-6 text-right">
                                    other info
                                </p>
                                <div
                                    class="w-full font-bold text-xl text-gray-800 px-6">
                                    Lorem ipsum dolor sit amet.
                                </div>
                                <p class="text-gray-800 text-base px-6 mb-5">
                                    Lorem ipsum dolor sit amet, consectetur
                                    adipiscing elit. Aliquam at ipsum eu nunc
                                    commodo posuere et sit amet ligula.
                                </p>
                            </a>
                        </div>
                        <div
                            class="flex-none mt-auto bg-white rounded-b rounded-t-none overflow-hidden shadow p-6">
                            <div class="flex items-center justify-end">
                                <button
                                    class="mx-auto lg:mx-0 hover:underline gradient text-white font-bold rounded-full my-6 py-4 px-8 shadow-lg focus:outline-none focus:shadow-outline transform transition hover:scale-105 duration-300 ease-in-out">
                                    Ir
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
@endsection

@section('css')
    <link href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:400,700"
          rel="stylesheet"/>
    <style>
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
