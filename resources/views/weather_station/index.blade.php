@extends('layouts.app')

{{-- Descripción sobre esta página --}}
@section('title', 'Chipiona Estación Meteorológica')
@section('description', 'Estación meteorológica en tiempo real para la ciudad de Chipiona tomando Humedad, Temperatura, Presión atmosférica, Viento (Velocidad, dirección y ráfagas), Cantidad de luz en general, Indice UV, UVA, UVB, CO2-ECO2, TVOC, Calidad del aire, Relámpagos (Cantidad, distancia y potencia)')
@section('keywords', 'Raúl Caro Pastorino, fryntiz, chipiona, clima, meteorología, tiempo, el tiempo, el clima, tiempo en chipiona, el tiempo en chipiona, humedad, calor, temperatura, presión, presión atmosférica, viento, luz, uv, uva, uvb, co2, eco2, co2-eco2, tvoc, relámpagos, relámpago, rayo, trueno, tormenta, tormentas')

{{-- Etiquetas para Redes sociales --}}
@section('rs-title', 'Chipiona Estación Meteorológica')
@section('rs-sitename', 'Api Fryntiz')
@section('rs-description', 'Estación meteorológica en tiempo real para la ciudad de Chipiona tomando Humedad, Temperatura, Presión atmosférica, Viento (Velocidad, dirección y ráfagas), Cantidad de luz en general, Indice UV, UVA, UVB, CO2-ECO2, TVOC, Calidad del aire, Relámpagos (Cantidad, distancia y potencia)')
@section('rs-image', asset('images/wheater-station/social-thumbnail.jpg'))
@section('rs-url', route('weather_station.index'))
@section('rs-image-alt', 'Chipiona Estación Meteorológica')

@section('meta-twitter-title', 'Chipiona Estación Meteorológica')

@section('content')

    <nav
        class="flex items-center justify-between flex-wrap bg-white py-4 px-4 shadow border-solid border-t-2 border-blue-700">

        <div class="menu w-full block flex-grow flex items-center w-auto">
            <div class="text-md font-bold text-blue-700 flex-grow text-center">

                @foreach($sections as $title => $url)
                    <a href="#{{$title}}"
                       class="block mt-4 inline-block mt-0 px-2 py-2 rounded mr-2">
                        {{$title}}
                    </a>
                @endforeach
            </div>
        </div>
    </nav>


    <div class="leading-normal tracking-normal"
         style="font-family: 'Source Sans Pro', sans-serif;">

        <section class="bg-white border-b">
            <div class="container max-w-5xl mx-auto m-4">
                <h1 class="w-full my-2 text-5xl font-bold leading-tight text-center text-gray-800">
                    Estación Meteorológica
                    <br/>
                    Modo depuración
                </h1>

                <div class="flex flex-wrap content-center">
                    <div class="w-full p-6">
                        <div class="bg-red-100 border border-red-400 m-2 mb-5 text-red-700 px-2 py-2 rounded relative"
                             role="alert">
                            <span class="inline text-sm">
                                Sitio
                                <strong>temporal</strong>
                                con la finalidad de detectar posibles
                                caídas/cuelgues o mediciones imprecisas en los
                                programas que desarrollo para sensores,
                                arduinos, raspberrys, servidores...
                                <br/>
                                Una vez acabada la aplicación, este sitio
                                desaparecerá quedando en el subdominio:
                                <a href="https://eltiempo.desdechipiona.es"
                                   class="underline text-lightBlue-500 background-transparent font-bold text-xs outline-none focus:outline-none ease-linear transition-all duration-150"
                                   type="button"
                                   target="_blank"
                                   title="El tiempo Desde Chipiona">
                                    https://eltiempo.desdechipiona.es
                                </a>
                            </span>
                        </div>

                        <h3 class="text-3xl text-gray-800 font-bold leading-none mb-3">
                            Sobre esta api
                        </h3>

                        <p class="text-gray-600 mb-2">
                            Los datos pueden no ser precisos debido a que aún se
                            encuentra en depuración y calibración de sensores.
                        </p>

                        <p class="text-gray-600 mb-2">
                            Esta API se encuentra en construcción y mide el
                            tiempo
                            actual en

                            <a href="https://es.wikipedia.org/wiki/Chipiona"
                               class="underline text-lightBlue-500 background-transparent font-bold text-xs outline-none focus:outline-none ease-linear transition-all duration-150"
                               type="button"
                               target="_blank">
                                Chipiona
                            </a>

                            con una frecuencia de 1-2 minutos por el momento.
                        </p>

                        <p class="text-gray-600 mb-1">
                            Puedes ver el desarrollo de la estación
                            meteorológica
                            para la raspberry en python3 desde aquí:

                            <a href="https://gitlab.com/fryntiz/raspberry-weather-station"
                               class="underline text-lightBlue-500 background-transparent font-bold text-xs outline-none focus:outline-none ease-linear transition-all duration-150"
                               type="button"
                               target="_blank"
                               title="Raspberry pi 4 estación meteorológica">
                                https://gitlab.com/fryntiz/raspberry-weather-station
                            </a>
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <section class="bg-white border-b">
            <div class="container max-w-5xl mx-auto m-4">
                <v-chipiona-weather-component></v-chipiona-weather-component>
            </div>
        </section>

        <section class="bg-white border-b">

            @foreach($sections as $title => $url)
                <div class="leading-normal tracking-normal"
                     style="font-family: 'Source Sans Pro', sans-serif;">

                    <section id="{{$title}}" class="bg-white border-b">
                        <div class="container max-w-5xl mx-auto m-4">
                            <v-table-component title="{{$title}}"
                                               url="{{$url}}" />
                        </div>
                    </section>
                </div>
            @endforeach

        </section>
    </div>
--}}
@endsection
