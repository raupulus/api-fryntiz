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

    {{-- Widget resumen del clima actual (Vue 3) --}}
    <section class="py-8 bg-surface-container-low flex justify-center">
        <div id="app-weather-chipiona"
             data-api-base-url="{{ url('/') }}"
             data-api-path="api/v2/weatherstation/resume"
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

    {{-- Secciones de datos --}}
    <section class="py-12 bg-surface-container-low">
        <div class="max-w-7xl mx-auto px-6">
            <h2 class="text-3xl font-bold text-on-surface mb-6">Datos de los sensores</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach($sections as $section)
                    <a href="{{ $section['url'] }}" class="bg-surface-container-lowest rounded-xl p-6 shadow-lg hover:shadow-xl transition-shadow group">
                        <div class="flex items-center justify-between mb-4">
                            <div class="w-12 h-12 bg-surface-container rounded-lg flex items-center justify-center">
                                <span class="material-symbols-outlined text-on-tertiary-container">{{ $section['icon'] }}</span>
                            </div>
                            @if($section['primary'])
                                <div class="text-right">
                                    <div class="text-xl font-bold text-on-surface">{{ $section['primary'] }}</div>
                                    @if($section['secondary'])
                                        <div class="text-xs text-on-surface-variant">{{ $section['secondary'] }}</div>
                                    @endif
                                </div>
                            @endif
                        </div>
                        <h3 class="text-lg font-bold text-on-surface mb-2">{{ $section['title'] }}</h3>
                        <span class="text-primary-container font-bold text-xs uppercase tracking-widest group-hover:underline">Ver datos →</span>
                    </a>
                @endforeach
            </div>
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
