@extends('layouts.app')

@section('title', 'Weather Station | Api Raupulus')
@section('description', 'Estación meteorológica con datos en tiempo real de Chipiona')
@section('keywords', 'weather station, estación meteorológica, temperatura, humedad, presión, Chipiona, Raúl Caro Pastorino, raupulus')

@section('rs-title', 'Weather Station - Datos meteorológicos en tiempo real')
@section('rs-sitename', 'Api Raupulus')
@section('rs-description', 'Estación meteorológica con datos en tiempo real de Chipiona')

@section('content')
    {{-- Hero --}}
    <section class="hero-gradient min-h-[40vh] flex items-center pt-20">
        <div class="max-w-7xl mx-auto px-6 text-white">
            <h1 class="text-4xl md:text-6xl font-bold tracking-tighter mb-4">Weather Station</h1>
            <p class="text-xl text-white/80">Datos meteorológicos en tiempo real — Chipiona</p>
        </div>
    </section>

    @if(session('notice'))
        <div class="max-w-7xl mx-auto px-6 mt-4">
            <div class="bg-tertiary-container/10 border border-tertiary-container text-on-surface rounded-lg px-4 py-3 text-sm flex items-center gap-2">
                <span class="material-symbols-outlined text-base">info</span>
                {{ session('notice') }}
            </div>
        </div>
    @endif

    {{-- Widget resumen del clima actual (Vue 3) --}}
    <section class="py-8 bg-surface-container-low flex justify-center">
        <div id="app-weather-chipiona"
             data-api-base-url="{{ url('/') }}"
             data-api-path="api/v2/weatherstation/station"
             @if(!empty($mainStationId)) data-station="{{ $mainStationId }}" @endif
             class="w-full max-w-lg">
            <p class="text-on-surface-variant text-center py-8">Cargando datos del clima...</p>
        </div>
    </section>

    {{-- Descripción --}}
    <section class="py-12 bg-surface">
        <div class="max-w-5xl mx-auto px-6">
            <h3 class="text-3xl text-on-surface font-bold leading-none mb-3">Sobre este módulo</h3>
            <p class="text-on-surface-variant mb-3">
                La estación meteorológica es un proyecto con raspberry pi, arduino y una serie de sensores tomando datos en mi localidad, <strong>Chipiona</strong>.
            </p>
            <p class="text-on-surface-variant mb-3">
                Humedad, Temperatura, Presión atmosférica, Viento (Velocidad, dirección y ráfagas), Cantidad de luz en general, Indice UV, UVA, UVB, CO2-ECO2, TVOC, Calidad del aire, Relámpagos (Cantidad, distancia y potencia)
            </p>
        </div>
    </section>

    {{-- Secciones de datos, agrupadas por ubicación (interior/exterior) y zona --}}
    <section class="py-12 bg-surface-container-low">
        <div class="max-w-7xl mx-auto px-6">
            <h2 class="text-3xl font-bold text-on-surface mb-2">Datos de los sensores</h2>
            <p class="text-on-surface-variant mb-8">
                Las estaciones se agrupan por interior y exterior, y dentro de estas por zona, para no mezclar lecturas dispares.
            </p>

            @forelse($groups as $group)
                <div class="mb-12">
                    {{-- Cabecera de grupo interior/exterior --}}
                    <div class="flex items-center gap-3 mb-6">
                        <span class="material-symbols-outlined text-on-tertiary-container">
                            {{ $group['location_type'] === 'outdoor' ? 'wb_sunny' : 'home' }}
                        </span>
                        <h3 class="text-2xl font-bold text-on-surface">{{ $group['label'] }}</h3>
                    </div>

                    @foreach($group['zones'] as $zone)
                        @foreach($zone['stations'] as $station)
                            {{-- Franja por estación: borde de color + fondo sutil para separarlas visualmente --}}
                            <div class="mb-8 rounded-xl border-l-4 {{ $group['location_type'] === 'outdoor' ? 'border-secondary' : 'border-tertiary-container' }} bg-surface-container/40 pl-5 pr-5 py-5">
                                {{-- Cabecera de estación (zona + nombre) --}}
                                <div class="flex items-center gap-2 mb-4">
                                    <span class="material-symbols-outlined text-on-surface-variant text-lg">location_on</span>
                                    <h4 class="text-lg font-bold text-on-surface">{{ $station['name'] }}</h4>
                                    <span class="text-xs text-on-surface-variant">{{ $zone['zone'] }}</span>
                                    @if($station['is_main'])
                                        <span class="text-[10px] font-bold uppercase tracking-widest bg-primary-container text-white rounded-full px-2 py-0.5">Principal</span>
                                    @endif
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                                    @foreach($station['sections'] as $section)
                                        @include('weather_station.partials.sensor-card', ['section' => $section])
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    @endforeach
                </div>
            @empty
                {{-- Reserva: aún no hay estaciones clasificadas por interior/exterior --}}
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach($ungrouped as $section)
                        @include('weather_station.partials.sensor-card', ['section' => $section])
                    @endforeach
                </div>
            @endforelse
        </div>
    </section>

    {{-- Repositorios: hardware y software que alimentan la estación --}}
    <section class="py-12 bg-surface">
        <div class="max-w-7xl mx-auto px-6">
            <h2 class="text-3xl font-bold text-on-surface mb-2">Repositorios del proyecto</h2>
            <p class="text-on-surface-variant mb-6">
                Código fuente del hardware y software que capturan los datos de esta estación meteorológica.
            </p>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @php
                    $repositories = [
                        [
                            'url' => 'https://gitlab.com/raupulus/raspberry-weather-station',
                            'description' => 'Backend de la estación en Raspberry Pi (Python3 y SQLite) que centraliza la lectura de todos los sensores.',
                        ],
                        [
                            'url' => 'https://gitlab.com/raupulus/rpi-pico-gadget-weatherstation',
                            'description' => 'Gadget con Raspberry Pi Pico que recopila datos ambientales de los sensores conectados.',
                        ],
                        [
                            'url' => 'https://gitlab.com/raupulus/rpi-pico-weather-station-light-radiation',
                            'description' => 'Monitor de luz y radiación (UV, UVA, UVB) con Raspberry Pi Pico y MicroPython.',
                        ],
                        [
                            'url' => 'https://gitlab.com/raupulus/raspberry-project-weather-station',
                            'description' => 'Estación meteorológica en Raspberry Pi con anemómetro y otros sensores, desarrollada en Python3.',
                        ],
                        [
                            'url' => 'https://gitlab.com/raupulus/esp32-weatherstation-bresser-read-868mhz-cc1101',
                            'description' => 'Intercepta y decodifica por radio (868MHz, CC1101) los datos de una estación Bresser usando un ESP32.',
                        ],
                        [
                            'url' => 'https://gitlab.com/raupulus/rpi-pico-sensor-lightning-cjmcu-3935',
                            'description' => 'Librería en MicroPython para el sensor de rayos CJMCU-3935 (AS3935): detecta descargas y su distancia.',
                        ],
                    ];
                @endphp
                @foreach($repositories as $repo)
                    <a href="{{ $repo['url'] }}" target="_blank" rel="noopener"
                       class="bg-surface-container-lowest rounded-xl p-5 shadow-lg hover:shadow-xl transition-shadow flex items-start gap-3 group">
                        <span class="material-symbols-outlined text-on-tertiary-container mt-0.5">code</span>
                        <div>
                            <span class="block text-sm font-bold text-on-surface underline-offset-2 group-hover:underline break-all">{{ $repo['url'] }}</span>
                            <span class="block text-xs text-on-surface-variant mt-1">{{ $repo['description'] }}</span>
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
    </section>
@endsection

@push('scripts')
    @vite('resources/js/vue.js')
@endpush
