@extends('layouts.app')

{{-- Descripción sobre esta página --}}
@section('title', 'Smart Plant, monitorización de plantas')
@section('description', 'Monitorización, control y riego automatizado en plantas')
@section('keywords', 'Raúl Caro Pastorino, fryntiz, smartplant, smart plant, plant, plantas, riego automático, monitorizar planta, monitorización de planta, monitorización, riego')

{{-- Etiquetas para Redes sociales --}}
@section('rs-title', 'Smart Plant, monitorización de plantas')
@section('rs-sitename', 'Api Fryntiz')
@section('rs-description', 'Monitorización, control y riego automatizado en plantas')
@section('rs-image', asset('images/smartplant/social-thumbnail.jpg'))
@section('rs-url', route('smartplant.index'))
@section('rs-image-alt', 'Smartplant, monitorización de plantas')

@section('meta-twitter-title', 'Smart Plant, monitorización de plantas')

@section('content')
    <div class="leading-normal tracking-normal"
         style="font-family: 'Source Sans Pro', sans-serif;">

        <section class="bg-white border-b">
            <div class="container max-w-5xl mx-auto m-4">
                <h1 class="w-full my-2 text-5xl font-bold leading-tight text-center text-gray-800">
                    Smart Plant
                </h1>

                <h2 class="w-full my-2 text-4xl font-bold leading-tight text-center text-gray-800">
                    Monitorización y riego automatizado en plantas
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
                                para recopilar los datos (esp32, circuito
                                propio, conjunto de sensores y software propio)
                            </span>
                        </div>

                        <h3 class="text-3xl text-gray-800 font-bold leading-none mb-3">
                            Sobre esta api
                        </h3>

                        <p class="text-gray-600 mb-2">
                            Para obtener los datos que llegan a esta API utilizo
                            un dispositivo <strong>esp32</strong> y un software
                            propio escrito en <strong>C++</strong> con el que
                            obtengo lecturas desde cada planta (principalmente
                            son bonsais) para poder atenderlos con un mejor
                            cuidado y aprender en las situaciones
                            climatológicas adversas que se le presenten.
                        </p>

                        <div class="text-gray-600 mb-2">
                            Condiciones para encender motor de riego:

                            <ul class="ml-5">
                                <li>Tanque de agua lleno</li>
                                <li>Humedad de tierra menor al 35%</li>
                            </ul>
                        </div>

                        <div class="text-gray-600 mb-2">
                            Condiciones para vaporizador de agua:

                            <ul class="ml-5">
                                <li>Temperatura ambiente menor a 30ºC</li>
                                <li>Humedad ambiente menor a 60%</li>
                            </ul>
                        </div>

                        <p class="text-gray-600 mb-2">
                            Puedes ver el desarrollo del programa en
                            <strong>C++</strong>
                            para obtener los datos
                            de las plantas desde un esp32:

                            <a href="https://gitlab.com/fryntiz/esp32-smart-bonsai"
                               class="underline text-lightBlue-500 background-transparent font-bold text-xs outline-none focus:outline-none ease-linear transition-all duration-150"
                               type="button"
                               target="_blank"
                               title="Smart Plant con esp32 repository software">
                                https://gitlab.com/fryntiz/esp32-smart-bonsai
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

        <section class="bg-white border-b">
            <div class="container max-w-5xl mx-auto m-4">
                <h1 class="w-full my-2 text-5xl font-bold leading-tight text-center text-gray-800">
                    Estadísticas para 100 resultados
                </h1>
            </div>
        </section>

        @foreach($smartplants as $plant)
            <section class="bg-white border-b">
                <div class="container max-w-5xl mx-auto m-4 overflow-x-scroll">

                    <h2 class="w-full capitalize my-2 text-4xl font-bold leading-tight text-center text-gray-800">
                        {{$plant->name}}
                    </h2>


                    <h3 class="w-full capitalize my-2 text-2xl font-bold leading-tight text-center text-gray-800">
                        (<small>Scientific Name:</small> {{$plant->name_scientific}})
                    </h3>

                    <p class="text-gray-600 mb-2">
                        {!! $plant->description !!}
                    </p>

                    {{-- Añado imagen si existe --}}
                    @if($plant->image)
                        <div class="w-full">
                            <img src="{{$plant->urlImage}}"
                                 title="{{$plant->name}}"
                                 alt="{{$plant->name}}"
                                 style="width: 300px; margin: auto;" />
                        </div>
                    @endif

                    {{-- Detalles de la planta --}}
                    <p class="text-gray-600 mb-2 smartplant-details w-full">
                        {!! $plant->details !!}
                    </p>

                    <table class="min-w-max w-full table-auto">
                        <thead class="justify-between">
                        <tr class="bg-gray-800">
                            <th class="px-1 py-2 text-gray-300 capitalize text-center">UV</th>
                            <th class="px-1 py-2 text-gray-300 capitalize text-center">Temperatura</th>
                            <th class="px-1 py-2 text-gray-300 capitalize text-center">Humedad ambiente</th>
                            <th class="px-1 py-2 text-gray-300 capitalize text-center">Humedad en tierra</th>
                            <th class="px-1 py-2 text-gray-300 capitalize text-center">Tanque de agua lleno</th>
                            <th class="px-1 py-2 text-gray-300 capitalize text-center">Motor de riego activo</th>
                            <th class="px-1 py-2 text-gray-300 capitalize text-center">Vaporizador de agua activo</th>
                            <th class="px-1 py-2 text-gray-300 capitalize text-center">Momento del registro</th>
                        </tr>
                        </thead>

                        <tbody class="bg-gray-200">
                        @foreach($plant->last100registers() as $reg)
                            <tr class="bg-white border-4 border-gray-200">
                                <td class="px-1 py-2 items-center text-center">{{$reg->uv ?? 0}}</td>
                                <td class="px-1 py-2 items-center text-center">{{$reg->temperature ?? 0.00}}</td>
                                <td class="px-1 py-2 items-center text-center">{{$reg->humidity ?? 0.00}}</td>
                                <td class="px-1 py-2 items-center text-center">{{$reg->soil_humidity}}</td>
                                <td class="px-1 py-2 items-center text-center">{{$reg->full_water_tank ? 'Si' : 'No'}}</td>
                                <td class="px-1 py-2 items-center text-center">{{$reg->waterpump_enabled ? 'Si' : 'No'}}</td>
                                <td class="px-1 py-2 items-center text-center">{{$reg->vaporizer_enabled ? 'Si' : 'No'}}</td>
                                <td class="px-1 py-2 items-center text-center">{{$reg->created_at}}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        @endforeach
    </div>
@endsection

@section('css')
    <style>
        .smartplant-description {
            font-size: 0.8em;
        }

        .smartplant-details {
            font-size: 0.6em;
        }
    </style>
@endsection
