@extends('layouts.app')

@section('title', 'Estación Meteorológica en Chipiona | Api Raupulus')
@section('description', 'Lecturas meteorológicas en tiempo real en Chipiona: temperatura, humedad, presión atmosférica, viento, lluvia, UV, calidad del aire y mareas.')
@section('keywords', 'weather station, estación meteorológica, temperatura, humedad, presión, Chipiona, Raúl Caro Pastorino, raupulus')

@section('rs-title', 'Estación Meteorológica en Chipiona | Api Raupulus')
@section('rs-sitename', 'Api Raupulus')
@section('rs-description', 'Datos meteorológicos en tiempo real en Chipiona: sensores IoT locales, AEMET, mareas y alertas.')
@section('rs-image', asset('images/wheater-station/social-thumbnail.jpg'))
@section('rs-image-alt', 'Estación meteorológica en tiempo real de Chipiona')

@push('json-ld')
    @php
        $weatherDatasetSchema = [
            '@context' => 'https://schema.org',
            '@type' => 'Dataset',
            'name' => 'Estación Meteorológica en Chipiona — Datos en Tiempo Real',
            'description' => 'Serie de lecturas ambientales y meteorológicas en tiempo real de Chipiona registradas por sensores IoT propios y datos abiertos AEMET.',
            'url' => route('weather_station.index'),
            'inLanguage' => 'es-ES',
            'creator' => [
                '@type' => 'Person',
                'name' => 'Raúl Caro Pastorino',
                'url' => 'https://raupulus.dev',
            ],
            'spatialCoverage' => [
                '@type' => 'Place',
                'name' => 'Chipiona, Cádiz, España',
            ],
        ];
    @endphp
    <script type="application/ld+json">@json($weatherDatasetSchema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG)</script>
@endpush

@section('content')
    {{-- Hero --}}
    <section class="hero-gradient min-h-[40svh] flex items-center pt-20 pb-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 text-on-primary">
            <h1 class="text-4xl md:text-6xl font-bold tracking-tighter mb-4">Weather Station</h1>
            <p class="text-xl text-on-primary/85">Datos meteorológicos en tiempo real — Chipiona</p>
        </div>
    </section>

    @if(session('notice'))
        <div class="max-w-7xl mx-auto px-4 sm:px-6 mt-4">
            <x-alert type="info">{{ session('notice') }}</x-alert>
        </div>
    @endif

    {{-- Widget resumen del clima actual (SSR inicial + hidratación Vue 3) --}}
    <section class="py-8 px-4 sm:px-6 bg-surface-container-low flex justify-center">
        @if(!empty($initialWeatherWidget))
            <script id="weather-widget-initial-data" type="application/json">@json($initialWeatherWidget, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG)</script>
        @endif
        <div id="app-weather-chipiona"
             data-api-base-url="{{ url('/') }}"
             data-api-path="weatherstation/widget"
             @if(!empty($mainZone)) data-zone="{{ $mainZone }}" data-location-type="outdoor" @endif
             @if(empty($mainZone) && !empty($mainStationId)) data-station="{{ $mainStationId }}" @endif
             class="w-full max-w-lg min-h-[410px]">
            @if(!empty($initialWeatherWidget))
                <div class="rounded-2xl bg-surface-container-lowest border border-outline-variant/20 shadow-lg p-6 text-on-surface">
                    <div class="flex items-start justify-between gap-4 border-b border-outline-variant/15 pb-4">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-widest text-on-tertiary-container">
                                {{ $initialWeatherWidget['location_label'] ?: ($initialWeatherWidget['name'] ?? 'Chipiona, Es') }}
                            </p>
                            <p class="text-4xl font-bold tracking-tight mt-2">
                                {{ isset($initialWeatherWidget['temperature']) && $initialWeatherWidget['temperature'] !== null ? number_format((float) $initialWeatherWidget['temperature'], 1).' °C' : '-- °C' }}
                            </p>
                        </div>
                        <div class="text-right text-sm text-on-surface-variant">
                            <p>{{ $initialWeatherWidget['instant']['date_human_format'] ?? '' }}</p>
                            <p class="font-semibold text-on-surface">{{ $initialWeatherWidget['instant']['time'] ?? '' }}</p>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4 pt-4 text-sm">
                        <div class="rounded-xl bg-surface-container p-3">
                            <span class="block text-xs text-on-surface-variant">Humedad</span>
                            <span class="font-bold text-on-surface">{{ isset($initialWeatherWidget['humidity']) && $initialWeatherWidget['humidity'] !== null ? number_format((float) $initialWeatherWidget['humidity'], 1).' %' : '-- %' }}</span>
                        </div>
                        <div class="rounded-xl bg-surface-container p-3">
                            <span class="block text-xs text-on-surface-variant">Presión</span>
                            <span class="font-bold text-on-surface">{{ isset($initialWeatherWidget['pressure']) && $initialWeatherWidget['pressure'] !== null ? number_format((float) $initialWeatherWidget['pressure'], 1).' hPa' : '-- hPa' }}</span>
                        </div>
                    </div>
                </div>
            @else
                <p class="text-on-surface-variant text-center py-8">Cargando datos del clima...</p>
            @endif
        </div>
    </section>

    {{--
        Datos de fuentes externas (AEMET / Open-Meteo): luna, marea, ozono y
        aviso AEMET vigente en Cádiz. Agrupados sobre un bloque de color propio
        (tertiary-fixed) a propósito, para que se note a simple vista que esto
        NO son datos de las estaciones propias — esas van más abajo, en
        "Datos de los sensores", sobre el fondo neutro habitual.
    --}}
    <section class="pb-12 bg-surface-container-low">
        <div class="max-w-7xl mx-auto px-4 sm:px-6">
            <div class="rounded-2xl bg-tertiary-fixed p-6">
                <div class="flex items-center justify-between flex-wrap gap-2 mb-5">
                    <h2 class="text-sm font-bold uppercase tracking-widest text-on-tertiary-fixed">
                        Datos externos — no son de mis estaciones
                    </h2>
                    <p class="text-xs text-on-tertiary-fixed/85">
                        {{ config('aemet.attribution.short') }} ·
                        <a href="https://open-meteo.com/" target="_blank" rel="noopener noreferrer" class="underline underline-offset-2">Weather data by Open-Meteo.com</a>
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    @include('weather_station.partials.moon-card', ['moon' => $moon, 'sun' => $sun])
                    @include('weather_station.partials.tide-card', ['nextTide' => $nextTide, 'seaState' => $seaState, 'seaStateUpdatedAt' => $seaStateUpdatedAt])
                    @include('weather_station.partials.ozone-card', ['ozone' => $ozone])
                </div>

                <div class="mt-6">
                    @include('weather_station.partials.aemet-alert-card', ['aemetAlert' => $aemetAlert])
                </div>
            </div>
        </div>
    </section>

    {{-- Descripción --}}
    <section class="py-12 bg-surface">
        <div class="max-w-5xl mx-auto px-6">
            <h2 class="text-3xl text-on-surface font-bold leading-none mb-3">Sobre este módulo</h2>
            <p class="text-on-surface-variant mb-3">
                La estación meteorológica es un proyecto con raspberry pi, arduino y una serie de sensores tomando datos en mi localidad, <strong>Chipiona</strong>.
            </p>
            <p class="text-on-surface-variant mb-3">
                Humedad, Temperatura, Presión atmosférica, Viento (Velocidad, dirección y ráfagas), Cantidad de luz en general, Indice UV, UVA, UVB, CO2-ECO2, TVOC, Calidad del aire, Relámpagos (Cantidad, distancia y potencia)
            </p>
        </div>
    </section>

    @include('weather_station.partials.gdacs-banner', ['gdacsLatestActive' => $gdacsLatestActive])

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
                        <span class="material-symbols-outlined text-on-tertiary-container" aria-hidden="true">
                            {{ $group['location_type'] === 'outdoor' ? 'wb_sunny' : 'home' }}
                        </span>
                        <h3 class="text-2xl font-bold text-on-surface">{{ $group['label'] }}</h3>
                    </div>

                    @foreach($group['zones'] as $zone)
                        @foreach($zone['stations'] as $station)
                            {{-- Franja por estación: borde de color + fondo sutil para separarlas visualmente --}}
                            <div class="mb-8 rounded-xl border-l-4 {{ $group['location_type'] === 'outdoor' ? 'border-secondary' : 'border-tertiary-container' }} bg-surface-container/40 pl-5 pr-5 py-5">
                                {{-- Cabecera de estación (zona + nombre) --}}
                                <div class="flex flex-wrap items-center gap-2 mb-4">
                                    <span class="material-symbols-outlined text-on-surface-variant text-lg shrink-0" aria-hidden="true">location_on</span>
                                    <h4 class="text-lg font-bold text-on-surface min-w-0 break-words">{{ $station['name'] }}</h4>
                                    <span class="text-xs text-on-surface-variant">{{ $zone['zone'] }}</span>
                                    @if($station['is_main'])
                                        <span class="text-xs font-bold uppercase tracking-widest bg-primary-container text-on-primary rounded-full px-2.5 py-0.5">Principal</span>
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
                    <a href="{{ $repo['url'] }}" target="_blank" rel="noopener noreferrer"
                       class="bg-surface-container-lowest rounded-xl p-5 shadow-lg hover:shadow-xl transition-shadow flex items-start gap-3 group">
                        <span class="material-symbols-outlined text-on-tertiary-container mt-0.5 shrink-0" aria-hidden="true">code</span>
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
