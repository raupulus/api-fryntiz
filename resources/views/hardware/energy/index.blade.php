@extends('layouts.app')

{{-- Descripción sobre esta página --}}
@section('title', 'Energía (Generadores y Consumo)')
@section('description', 'Estadísticas y datos para la energía producida y la consumida por mis sistemas y equipos.')
@section('keywords', 'energía, generador, generadores, consumo, panel solar, paneles solares, Raúl Caro Pastorino, fryntiz')

{{-- Etiquetas para Redes sociales --}}
@section('rs-title', 'Energía (Generadores y Consumo)')
@section('rs-sitename', 'Api Fryntiz')
@section('rs-description', 'Estadísticas y datos para la energía producida y la consumida por mis sistemas y equipos.')
@section('rs-image', asset('images/keycounter/social-thumbnail.jpg'))
@section('rs-url', route('hardware.energy.index'))
@section('rs-image-alt', 'Estadísticas y datos para la energía producida y la consumida por mis sistemas y equipos.')

@section('meta-twitter-title', 'Energía (Generadores y Consumo)')

@section('content')



    <div class="leading-normal tracking-normal"
         style="font-family: 'Source Sans Pro', sans-serif;">


        <section class="text-gray-600 body-font">
            <div class="container px-5 py-10 mx-auto flex flex-col">
                <div class="lg:w-4/6 mx-auto">
                    <div class="rounded-lg h-64 overflow-hidden">
                        <img alt="content"
                             class="object-cover object-center h-full w-full"
                             src="{{asset('images/energy/energy.png')}}">
                    </div>

                    <div class="container px-1 py-5 mx-auto flex flex-wrap">
                        <h2 class="sm:text-5xl text-4xl text-gray-900
                        font-medium title-font mb-2 w-full text-center">
                            Energía (Generadores y Consumo)
                        </h2>
                    </div>
                </div>
            </div>
        </section>

        {{-- Resumen --}}
        <section class="bg-white border-b">
            <div class="container max-w-5xl mx-auto m-4">
                <!-- State cards -->
                <div
                    class="grid grid-cols-1 gap-8 p-4 lg:grid-cols-2 xl:grid-cols-4">
                    <!-- Value card -->
                    <div
                        class="flex items-center justify-between p-4 bg-white rounded-md dark:bg-darker">
                        <div>
                            <h6 class="text-xs font-medium leading-none tracking-wider text-gray-500 uppercase dark:text-primary-light">
                                Generado
                            </h6>

                            <span class="text-xl font-semibold">
                                {{$generatorHistorical}}
                                kwh
                            </span>

                            <span
                                class="inline-block px-2 py-px ml-2 text-xs text-green-500 bg-green-100 rounded-md">
                      +0.0%
                    </span>
                        </div>
                        <div>
                    <span>
                      <svg version="1.1"
                           style="width: 50px; height: 50px"
                           id="Layer_1"
                           xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 112.12 122.88" style="enable-background:new 0 0 112.12 122.88" xml:space="preserve"><style type="text/css">.st0{fill-rule:evenodd;clip-rule:evenodd;}</style><g><path class="st0" d="M42.78,64.47l0,4.26c0,7.54,3.19,11.24,10.88,12.16v10.22h5.95V80.82c7.61-0.73,10.88-5.21,10.88-12.52v-3.83 h1.1c0.98,0,1.78-0.8,1.78-1.78v-2.06c0-0.98-0.8-1.78-1.78-1.78h-5.27v-10.9c0-1.55-1.27-2.81-2.81-2.81l0,0 c-1.55,0-2.81,1.27-2.81,2.81v10.9h-8.25v-10.9c0-1.55-1.27-2.81-2.81-2.81l0,0c-1.55,0-2.81,1.27-2.81,2.81v10.9h-5.42 c-0.98,0-1.78,0.8-1.78,1.78v2.06c0,0.98,0.8,1.78,1.78,1.78H42.78L42.78,64.47z M9.83,46.53l0.06,0.04 c13.55-16.35,18.52-17.09,22.05-36.7C26.76,21.56,12.07,31.12,9.83,46.53L9.83,46.53z M9.08,54.9C6.77,61.76,6.7,69.39,8.32,76.77 c2.16,9.83,7.3,19.16,14.13,25.66c5.09,4.84,10.88,8.41,17,10.75c9.13,3.49,18.95,4.23,28.16,2.33 c9.18-1.89,17.77-6.41,24.46-13.45c4.14-4.35,7.56-9.68,9.96-15.96c2.89-7.55,4.05-15.25,3.66-22.66 c-0.4-7.65-2.44-14.99-5.92-21.51c-2.7-5.06-6.28-9.63-10.62-13.48c-4.2-3.72-9.12-6.77-14.69-8.94c-1.63-0.63-2.45-2.47-1.82-4.1 c0.63-1.63,2.47-2.45,4.1-1.82c6.29,2.45,11.85,5.9,16.6,10.1c4.92,4.36,8.96,9.53,12.02,15.25c3.92,7.35,6.22,15.59,6.67,24.17 c0.43,8.28-0.86,16.86-4.06,25.24c-2.71,7.09-6.6,13.12-11.31,18.07c-7.61,8-17.37,13.14-27.79,15.29 c-10.39,2.14-21.44,1.31-31.69-2.61c-6.87-2.63-13.39-6.65-19.11-12.09C10.33,99.66,4.53,89.15,2.1,78.11 c-1.95-8.87-1.73-18.12,1.38-26.44l-0.04-0.09C-9.82,18.18,18.35,12.57,35.62,0C49.83,35.22,35.31,52.07,9.08,54.9L9.08,54.9z M49.14,65.99h14.98v2.23H49.14V65.99L49.14,65.99z"/></g></svg>
                    </span>
                        </div>
                    </div>

                    <!-- Users card -->
                    <div
                        class="flex items-center justify-between p-4 bg-white rounded-md dark:bg-darker">
                        <div>
                            <h6
                                class="text-xs font-medium leading-none tracking-wider text-gray-500 uppercase dark:text-primary-light"
                            >
                                Consumido
                            </h6>
                            <span class="text-xl font-semibold">
                                {{$loadHistorical}}
                                kwh
                            </span>

                            <span
                                class="inline-block px-2 py-px ml-2 text-xs text-green-500 bg-green-100 rounded-md">
                      +0.0%
                    </span>
                        </div>
                        <div>
                    <span>
                      <svg version="1.1"
                           style="width: 50px; height: 50px"
                           id="Layer_1"
                           xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 112.12 122.88" style="enable-background:new 0 0 112.12 122.88" xml:space="preserve"><style type="text/css">.st0{fill-rule:evenodd;clip-rule:evenodd;}</style><g><path class="st0" d="M42.78,64.47l0,4.26c0,7.54,3.19,11.24,10.88,12.16v10.22h5.95V80.82c7.61-0.73,10.88-5.21,10.88-12.52v-3.83 h1.1c0.98,0,1.78-0.8,1.78-1.78v-2.06c0-0.98-0.8-1.78-1.78-1.78h-5.27v-10.9c0-1.55-1.27-2.81-2.81-2.81l0,0 c-1.55,0-2.81,1.27-2.81,2.81v10.9h-8.25v-10.9c0-1.55-1.27-2.81-2.81-2.81l0,0c-1.55,0-2.81,1.27-2.81,2.81v10.9h-5.42 c-0.98,0-1.78,0.8-1.78,1.78v2.06c0,0.98,0.8,1.78,1.78,1.78H42.78L42.78,64.47z M9.83,46.53l0.06,0.04 c13.55-16.35,18.52-17.09,22.05-36.7C26.76,21.56,12.07,31.12,9.83,46.53L9.83,46.53z M9.08,54.9C6.77,61.76,6.7,69.39,8.32,76.77 c2.16,9.83,7.3,19.16,14.13,25.66c5.09,4.84,10.88,8.41,17,10.75c9.13,3.49,18.95,4.23,28.16,2.33 c9.18-1.89,17.77-6.41,24.46-13.45c4.14-4.35,7.56-9.68,9.96-15.96c2.89-7.55,4.05-15.25,3.66-22.66 c-0.4-7.65-2.44-14.99-5.92-21.51c-2.7-5.06-6.28-9.63-10.62-13.48c-4.2-3.72-9.12-6.77-14.69-8.94c-1.63-0.63-2.45-2.47-1.82-4.1 c0.63-1.63,2.47-2.45,4.1-1.82c6.29,2.45,11.85,5.9,16.6,10.1c4.92,4.36,8.96,9.53,12.02,15.25c3.92,7.35,6.22,15.59,6.67,24.17 c0.43,8.28-0.86,16.86-4.06,25.24c-2.71,7.09-6.6,13.12-11.31,18.07c-7.61,8-17.37,13.14-27.79,15.29 c-10.39,2.14-21.44,1.31-31.69-2.61c-6.87-2.63-13.39-6.65-19.11-12.09C10.33,99.66,4.53,89.15,2.1,78.11 c-1.95-8.87-1.73-18.12,1.38-26.44l-0.04-0.09C-9.82,18.18,18.35,12.57,35.62,0C49.83,35.22,35.31,52.07,9.08,54.9L9.08,54.9z M49.14,65.99h14.98v2.23H49.14V65.99L49.14,65.99z"/></g></svg>
                    </span>
                        </div>
                    </div>

                    <!-- Orders card -->
                    <div
                        class="flex items-center justify-between p-4 bg-white rounded-md dark:bg-darker">
                        <div>
                            <h6
                                class="text-xs font-medium leading-none tracking-wider text-gray-500 uppercase dark:text-primary-light"
                            >
                                Actual
                            </h6>
                            <span class="text-xl font-semibold">
                                {{$generatorToday}}
                                kwh
                            </span>
                            <span
                                class="inline-block px-2 py-px ml-2 text-xs text-green-500 bg-green-100 rounded-md">
                      +0.0%
                    </span>
                        </div>
                        <div>
                    <span>
                      <svg version="1.1"
                           style="width: 50px; height: 50px"
                           id="Layer_1"
                           xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 112.12 122.88" style="enable-background:new 0 0 112.12 122.88" xml:space="preserve"><style type="text/css">.st0{fill-rule:evenodd;clip-rule:evenodd;}</style><g><path class="st0" d="M42.78,64.47l0,4.26c0,7.54,3.19,11.24,10.88,12.16v10.22h5.95V80.82c7.61-0.73,10.88-5.21,10.88-12.52v-3.83 h1.1c0.98,0,1.78-0.8,1.78-1.78v-2.06c0-0.98-0.8-1.78-1.78-1.78h-5.27v-10.9c0-1.55-1.27-2.81-2.81-2.81l0,0 c-1.55,0-2.81,1.27-2.81,2.81v10.9h-8.25v-10.9c0-1.55-1.27-2.81-2.81-2.81l0,0c-1.55,0-2.81,1.27-2.81,2.81v10.9h-5.42 c-0.98,0-1.78,0.8-1.78,1.78v2.06c0,0.98,0.8,1.78,1.78,1.78H42.78L42.78,64.47z M9.83,46.53l0.06,0.04 c13.55-16.35,18.52-17.09,22.05-36.7C26.76,21.56,12.07,31.12,9.83,46.53L9.83,46.53z M9.08,54.9C6.77,61.76,6.7,69.39,8.32,76.77 c2.16,9.83,7.3,19.16,14.13,25.66c5.09,4.84,10.88,8.41,17,10.75c9.13,3.49,18.95,4.23,28.16,2.33 c9.18-1.89,17.77-6.41,24.46-13.45c4.14-4.35,7.56-9.68,9.96-15.96c2.89-7.55,4.05-15.25,3.66-22.66 c-0.4-7.65-2.44-14.99-5.92-21.51c-2.7-5.06-6.28-9.63-10.62-13.48c-4.2-3.72-9.12-6.77-14.69-8.94c-1.63-0.63-2.45-2.47-1.82-4.1 c0.63-1.63,2.47-2.45,4.1-1.82c6.29,2.45,11.85,5.9,16.6,10.1c4.92,4.36,8.96,9.53,12.02,15.25c3.92,7.35,6.22,15.59,6.67,24.17 c0.43,8.28-0.86,16.86-4.06,25.24c-2.71,7.09-6.6,13.12-11.31,18.07c-7.61,8-17.37,13.14-27.79,15.29 c-10.39,2.14-21.44,1.31-31.69-2.61c-6.87-2.63-13.39-6.65-19.11-12.09C10.33,99.66,4.53,89.15,2.1,78.11 c-1.95-8.87-1.73-18.12,1.38-26.44l-0.04-0.09C-9.82,18.18,18.35,12.57,35.62,0C49.83,35.22,35.31,52.07,9.08,54.9L9.08,54.9z M49.14,65.99h14.98v2.23H49.14V65.99L49.14,65.99z"/></g></svg>
                    </span>
                        </div>
                    </div>

                    <!-- Tickets card -->
                    <div
                        class="flex items-center justify-between p-4 bg-white rounded-md dark:bg-darker">
                        <div>
                            <h6
                                class="text-xs font-medium leading-none tracking-wider text-gray-500 uppercase dark:text-primary-light"
                            >
                                Hoy
                            </h6>
                            <span class="text-xl font-semibold">
                                {{$loadToday}}
                                kwh
                            </span>
                            <span
                                class="inline-block px-2 py-px ml-2 text-xs text-green-500 bg-green-100 rounded-md">
                      +0.0%
                    </span>
                        </div>
                        <div>
                    <span>
                      <svg version="1.1"
                           style="width: 50px; height: 50px"
                           id="Layer_1"
                           xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 112.12 122.88" style="enable-background:new 0 0 112.12 122.88" xml:space="preserve"><style type="text/css">.st0{fill-rule:evenodd;clip-rule:evenodd;}</style><g><path class="st0" d="M42.78,64.47l0,4.26c0,7.54,3.19,11.24,10.88,12.16v10.22h5.95V80.82c7.61-0.73,10.88-5.21,10.88-12.52v-3.83 h1.1c0.98,0,1.78-0.8,1.78-1.78v-2.06c0-0.98-0.8-1.78-1.78-1.78h-5.27v-10.9c0-1.55-1.27-2.81-2.81-2.81l0,0 c-1.55,0-2.81,1.27-2.81,2.81v10.9h-8.25v-10.9c0-1.55-1.27-2.81-2.81-2.81l0,0c-1.55,0-2.81,1.27-2.81,2.81v10.9h-5.42 c-0.98,0-1.78,0.8-1.78,1.78v2.06c0,0.98,0.8,1.78,1.78,1.78H42.78L42.78,64.47z M9.83,46.53l0.06,0.04 c13.55-16.35,18.52-17.09,22.05-36.7C26.76,21.56,12.07,31.12,9.83,46.53L9.83,46.53z M9.08,54.9C6.77,61.76,6.7,69.39,8.32,76.77 c2.16,9.83,7.3,19.16,14.13,25.66c5.09,4.84,10.88,8.41,17,10.75c9.13,3.49,18.95,4.23,28.16,2.33 c9.18-1.89,17.77-6.41,24.46-13.45c4.14-4.35,7.56-9.68,9.96-15.96c2.89-7.55,4.05-15.25,3.66-22.66 c-0.4-7.65-2.44-14.99-5.92-21.51c-2.7-5.06-6.28-9.63-10.62-13.48c-4.2-3.72-9.12-6.77-14.69-8.94c-1.63-0.63-2.45-2.47-1.82-4.1 c0.63-1.63,2.47-2.45,4.1-1.82c6.29,2.45,11.85,5.9,16.6,10.1c4.92,4.36,8.96,9.53,12.02,15.25c3.92,7.35,6.22,15.59,6.67,24.17 c0.43,8.28-0.86,16.86-4.06,25.24c-2.71,7.09-6.6,13.12-11.31,18.07c-7.61,8-17.37,13.14-27.79,15.29 c-10.39,2.14-21.44,1.31-31.69-2.61c-6.87-2.63-13.39-6.65-19.11-12.09C10.33,99.66,4.53,89.15,2.1,78.11 c-1.95-8.87-1.73-18.12,1.38-26.44l-0.04-0.09C-9.82,18.18,18.35,12.57,35.62,0C49.83,35.22,35.31,52.07,9.08,54.9L9.08,54.9z M49.14,65.99h14.98v2.23H49.14V65.99L49.14,65.99z"/></g></svg>
                    </span>
                        </div>
                    </div>
                </div>
            </div>
        </section>


        {{-- Listado de dispositivos --}}
        <section class="text-gray-600 body-font">
            <div class="container px-5 py-24 mx-auto">
                <div class="flex flex-col text-center w-full mb-20">
                    <h1 class="text-2xl font-medium title-font mb-4 text-gray-900 tracking-widest">
                        Dispositivos
                    </h1>

                    <p class="lg:w-2/3 mx-auto leading-relaxed text-base">
                        Descripción de la sección
                    </p>
                </div>

                <div class="flex flex-wrap -m-4">

                    @foreach($hardwares as $hardware)

                        <div class="p-4 lg:w-1/2">
                            <div
                                class="h-full flex sm:flex-row flex-col items-center sm:justify-start justify-center text-center sm:text-left">

                                <img alt="team"
                                     class="flex-shrink-0 rounded-lg w-48 h-48 object-cover object-center sm:mb-0 mb-4"
                                     src="https://dummyimage.com/200x200">

                                <div class="flex-grow sm:pl-8">
                                    <h2 class="title-font font-medium text-lg text-gray-900">
                                        {{$hardware->name}}
                                    </h2>
                                    <h3 class="text-gray-500 mb-3">
                                        {{$hardware->version}}
                                    </h3>

                                    <p class="mb-4">
                                        {{$hardware->description}}
                                    </p>

                                    <p class="mb-4">
                                        !Gráfico consumo-generación!
                                    </p>

                                    <span class="inline-flex">

                                </span>
                                </div>
                            </div>
                        </div>

                    @endforeach

                </div>
            </div>
        </section>


        <section class="bg-white border-b">
            <div class="container max-w-5xl mx-auto m-4">
                <h1 class="w-full my-2 text-5xl font-bold leading-tight text-center text-gray-800">
                    Section name
                </h1>

                <div class="w-full py-3 px-6 text-center">
                    <p class="text-gray-600 mb-1">
                        Estadísticas
                    </p>
                </div>

                <div class="w-full mb-4 text-center">
                    block 1
                </div>

                <div class="w-full mb-4 text-center">
                    block 1
                </div>


            </div>
        </section>
    </div>







    @include('hardware.energy.layouts._generators')
    @include('hardware.energy.layouts._loads')
@endsection
