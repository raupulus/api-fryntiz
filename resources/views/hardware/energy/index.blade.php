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
                        font-medium title-font w-full text-center">
                            Energía (Generadores y Consumo)
                        </h2>
                    </div>
                </div>
            </div>
        </section>

        {{-- Resumen --}}
        <section class="bg-white border-b">
            <div class="container max-w-5xl mx-auto m-1">
                <div class="row">
                    <span class="text-2xl subpixel-antialiased">
                        Histórico
                    </span>
                </div>

                @include('components.block-4-stats', ['stats' => $historicalStats])

                <div class="row">
                    <span class="text-2xl subpixel-antialiased">
                        Hoy
                    </span>
                </div>

                @include('components.block-4-stats', ['stats' => $todayStats])

                <div class="row">
                    <span class="text-2xl subpixel-antialiased">
                        Ahora
                    </span>
                </div>

                @include('components.block-4-stats', ['stats' => $currentStats])
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

                        @php
                            $hardwareStatistics = $hardware->currentEnergyStatistics;

                            $generator = [
                                $hardwareStatistics->generatorCurrent ? $hardwareStatistics->generatorCurrent->power : 0,
                                $hardwareStatistics->generatorToday ? $hardwareStatistics->generatorToday->power : 0,
                        ];
                            $load = [
                                $hardwareStatistics->loadCurrent ? $hardwareStatistics->loadCurrent->power : 0,
                                $hardwareStatistics->loadToday ? $hardwareStatistics->loadToday->power : 0,
                        ];
                            $battery = [
                                $hardwareStatistics->generatorCurrent ? $hardwareStatistics->generatorCurrent->battery_max : 0,
                                $hardwareStatistics->generatorToday ? $hardwareStatistics->generatorToday->battery_max : 0,
                        ];
                        @endphp

                        <div class="w-full sm:w-full md:w-1/2 2xl:w-1/3
                        px-2
                        md:pl-5 md:pr-5
                        lg:pl-10
                        lg:pr-10">
                            <div
                                class="items-center justify-items-center text-center">

                                <div class="flex-grow justify-items-center">
                                    <img alt="{{$hardware->name}}"
                                         class="text-center rounded-lg w-48 h-48
                                         object-cover object-center m-auto"
                                         src="{{$hardware->urlThumbnail('normal') }}">

                                    <h2 class="title-font font-medium text-lg text-gray-900 text-center">
                                        {{$hardware->name}}
                                    </h2>
                                    <h3 class="text-gray-500 mb-3 text-center">
                                        {{$hardware->software_version}}
                                    </h3>

                                    <div class="text-center">
                                        <span
                                            class="text-center text-sm text-green-500">
                                            <i class="pi pi-download font-bold"></i>:
                                            {{$hardwareStatistics->loadsHistorical ? $hardwareStatistics->loadsHistorical->sum('power')/1000
: 0}}KW

                                        </span>

                                        <span
                                            class="text-center text-sm text-yellow-500">
                                            <i class="pi pi-upload font-bold"></i>:
                                            {{$hardwareStatistics->generatorsHistorical ? $hardwareStatistics->generatorsHistorical->sum('power')/1000
: 0}}KW
                                        </span>


                                    </div>


                                    <div class="mb-4 border-b border-gray-200">
                                        <ul class="flex flex-wrap -mb-px text-sm font-medium text-center"
                                            id="hardwareTab-{{$hardware->id}}"
                                            data-tabs-toggle="#hardwareTabContent-{{$hardware->id}}"
                                            role="tablist">
                                            <li class="mr-2"
                                                role="presentation">
                                                <button
                                                    class="inline-block p-4 rounded-t-lg border-b-2 border-transparent hover:text-gray-600 hover:border-gray-300 dark:hover:text-gray-300 text-gray-500 dark:text-gray-400 border-gray-100 dark:border-gray-700"
                                                    id="energy-tab-{{$hardware->id}}"
                                                    data-tabs-target="#energy-{{$hardware->id}}"
                                                    type="button" role="tab"
                                                    aria-controls="energy-{{$hardware->id}}"
                                                    aria-selected="true">
                                                    Energía
                                                </button>
                                            </li>
                                            <li class="mr-2"
                                                role="presentation">
                                                <button class="inline-block
                                                p-4 rounded-t-lg border-b-2
                                                text-blue-600
                                                hover:text-blue-600
                                                dark:text-blue-500 dark:hover:text-blue-400 border-blue-600 dark:border-blue-500"
                                                        id="info-tab-{{$hardware->id}}"
                                                        data-tabs-target="#info-{{$hardware->id}}"
                                                        type="button" role="tab"
                                                        aria-controls="info-{{$hardware->id}}"
                                                        aria-selected="false">
                                                    Info
                                                </button>
                                            </li>
                                            <li class="mr-2"
                                                role="presentation">
                                                <button
                                                    class="inline-block p-4 rounded-t-lg border-b-2 border-transparent hover:text-gray-600 hover:border-gray-300 dark:hover:text-gray-300 text-gray-500 dark:text-gray-400 border-gray-100 dark:border-gray-700"
                                                    id="resume-tab-{{$hardware->id}}"
                                                    data-tabs-target="#resume-{{$hardware->id}}"
                                                    type="button" role="tab"
                                                    aria-controls="resume-{{$hardware->id}}"
                                                    aria-selected="false">
                                                    Resumen
                                                </button>
                                            </li>

                                        </ul>
                                    </div>
                                    <div
                                        id="hardwareTabContent-{{$hardware->id}}"
                                        style="min-height: 190px;"
                                    >
                                        <div
                                            class="p-4 bg-gray-50 rounded-lg dark:bg-gray-800"
                                            id="energy-{{$hardware->id}}"
                                            role="tabpanel"
                                            aria-labelledby="energy-tab-{{$hardware->id}}">

                                            <Chart type="bar"
                                                   :data="{
                                                       labels: ['Actual', 'Hoy'],
                                                       datasets: [
                                                       {
                                                       label: 'Generado (W)',
                                                       backgroundColor: '#42A5F5',
                                                       data: {{json_encode($generator)}}
                                                       },
                                                       {
                                                       label: 'Consumido (W)',
                                                       backgroundColor: '#FFA726',
                                                       data: {{json_encode($load)}}
                                                       },
                                                       {
                                                       label: 'Batería (%)',
                                                       backgroundColor: '#AF0026',
                                                       data: {{json_encode($battery)}}
                                                       }
                                                       ]
                                                       }"
                                                   :options="{
                                                       responsive: true,
                                                       maintainAspectRatio: false,
                                                       legend: {
                                                       display: false,
                                                       padding: 1
                                                       },
                                                       scales: {
                                                       yAxes: [{
                                                       ticks: {
                                                       beginAtZero: true
                                                       }
                                                       }]
                                                       }
                                                   }"
                                            />
                                        </div>

                                        <div
                                            class="hidden w-full p-4 bg-gray-50 rounded-lg dark:bg-gray-800"
                                            id="info-{{$hardware->id}}"
                                            role="tabpanel"
                                            aria-labelledby="info-tab-{{$hardware->id}}">
                                            <p class="text-left text-sm text-gray-500 dark:text-gray-400">
                                                TEST
                                                {{Str::limit($hardware->description, 250)}}
                                            </p>
                                        </div>

                                        <div
                                            class="hidden w-full p-4 bg-gray-50 rounded-lg dark:bg-gray-800"
                                            id="resume-{{$hardware->id}}"
                                            role="tabpanel"
                                            aria-labelledby="resume-tab-{{$hardware->id}}">
                                            <p class="text-left text-sm text-gray-500 dark:text-gray-400">
                                                {{Str::limit
                                                ($hardware->description, 400)}}
                                            </p>
                                        </div>

                                    </div>

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
