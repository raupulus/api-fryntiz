@extends('layouts.app')

{{-- Descripción sobre esta página --}}
@section('title', 'Energía (Generadores y Consumo)')
@section('description', 'Estadísticas y datos para la energía producida y la consumida por mis sistemas y equipos.')
@section('keywords', 'energía, generador, generadores, consumo, panel solar, paneles solares, Raúl Caro Pastorino, fryntiz')

{{-- Etiquetas para Redes sociales --}}
@section('rs-title', 'Energía (Generadores y Consumo)')
@section('rs-sitename', 'Api Fryntiz')
@section('rs-description', 'Estadísticas y datos para la energía producida y la consumida por mis sistemas y equipos.')
@section('rs-image', asset('images/energy/energy.png'))
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
                <div class="grid grid-cols-2 gap-8 p-4 md:grid-cols-4
                    xl:grid-cols-4">
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
                        <img src="{{asset('images/icons/energy-green.svg')}}"
                             alt="Energía"
                             class="w-20 h-20">
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
                      <img src="{{asset('images/icons/energy-green.svg')}}"
                           alt="Energía"
                           class="w-20 h-20">
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
                                Generado Hoy
                            </h6>
                            <span class="text-xl font-semibold">
                                {{$generatorToday}}
                                wh
                            </span>
                            <span
                                class="inline-block px-2 py-px ml-2 text-xs text-green-500 bg-green-100 rounded-md">
                      +0.0%
                    </span>
                        </div>
                        <div>
                    <span>
                      <img src="{{asset('images/icons/energy-green.svg')}}"
                           alt="Energía"
                           class="w-20 h-20">
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
                                Consumido Hoy
                            </h6>
                            <span class="text-xl font-semibold">
                                {{$loadToday}}
                                wh
                            </span>
                            <span
                                class="inline-block px-2 py-px ml-2 text-xs text-green-500 bg-green-100 rounded-md">
                      +0.0%
                    </span>
                        </div>
                        <div>
                    <span>
                      <img src="{{asset('images/icons/energy-green.svg')}}"
                           alt="Energía"
                           class="w-20 h-20">
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
