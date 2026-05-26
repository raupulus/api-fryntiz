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
             data-api-path="api/weatherstation/v1/resume"
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
            <p class="text-on-surface-variant mb-3">
                Puedes ver el desarrollo de rpi (python3 y sqlite) aquí:
                <a href="https://gitlab.com/raupulus/raspberry-weather-station" class="underline text-on-tertiary-container font-bold text-xs" target="_blank">
                    https://gitlab.com/raupulus/raspberry-weather-station
                </a>
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
                        <div class="w-12 h-12 bg-surface-container rounded-lg flex items-center justify-center mb-4">
                            <span class="material-symbols-outlined text-on-tertiary-container">{{ $section['icon'] }}</span>
                        </div>
                        <h3 class="text-lg font-bold text-on-surface mb-2">{{ $section['title'] }}</h3>
                        <span class="text-primary-container font-bold text-xs uppercase tracking-widest group-hover:underline">Ver datos →</span>
                    </a>
                @endforeach
            </div>
        </div>
    </section>
@endsection

@push('scripts')
    @vite('resources/js/vue.js')
@endpush
