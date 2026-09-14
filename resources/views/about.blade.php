@extends('layouts.app')

@section('title', 'Sobre el proyecto | Api Raupulus')
@section('description', 'Qué es Api Raupulus: una plataforma multi-API en Laravel que centraliza los proyectos IoT personales de Raúl Caro Pastorino.')
@section('keywords', 'sobre, about, api raupulus, Raúl Caro Pastorino, raupulus, proyecto, IoT, laravel, arquitectura')

@section('rs-title', 'Sobre Api Raupulus')
@section('rs-sitename', 'Api Raupulus')
@section('rs-description', 'Qué es Api Raupulus y qué proyectos centraliza')
@section('rs-image', asset('images/social-thumbnail.jpg'))
@section('rs-url', route('about'))

@section('content')
    {{-- Hero --}}
    <section class="hero-gradient min-h-[40vh] flex items-center pt-20">
        <div class="max-w-7xl mx-auto px-6 text-white">
            <h1 class="text-4xl md:text-6xl font-bold tracking-tighter mb-4">Sobre el proyecto</h1>
            <p class="text-xl text-white/80 max-w-3xl">Una plataforma multi-API que centraliza todos mis proyectos personales: módulos IoT, un CMS, currículum y newsletter, todo bajo un mismo tejado.</p>
        </div>
    </section>

    {{-- Qué es --}}
    <section class="py-12 bg-surface">
        <div class="max-w-5xl mx-auto px-6">
            <h2 class="text-3xl text-on-surface font-bold leading-none mb-4">Qué es Api Raupulus</h2>
            <p class="text-on-surface-variant mb-3">
                Api Raupulus es una plataforma en <strong>Laravel</strong> donde centralizo los proyectos personales que voy montando en casa: sensores, dispositivos IoT, radares, contadores... Cada gadget sube sus lecturas aquí, y esta misma API las sirve tanto a la web pública como a mis propias herramientas de depuración.
            </p>
            <p class="text-on-surface-variant mb-3">
                El contenido puede ser poco preciso o irreal: lo utilizo como herramienta para desarrollar y depurar, no como un servicio de cara a terceros. Aun así busco afinar la entrada de datos y tratarlo como parte real de la aplicación, con datos y comportamiento fiables.
            </p>
            <p class="text-on-surface-variant mb-3">
                El objetivo de fondo es una solución robusta, escalable, segura y <strong>fácil de gestionar desde la intranet</strong>: casi todo lo que hace la plataforma se puede administrar desde el panel de control sin tener que abrir una terminal.
            </p>
            <p class="text-on-surface-variant mb-3">
                Código público en GitLab:
                <a href="https://gitlab.com/raupulus/api-fryntiz/tree/master"
                   class="underline text-on-tertiary-container font-bold text-xs"
                   target="_blank"
                   title="Api Raupulus en GitLab">
                    https://gitlab.com/raupulus/api-fryntiz
                </a>
            </p>
        </div>
    </section>

    {{-- Módulos --}}
    <section class="py-12 bg-surface-container-low">
        <div class="max-w-7xl mx-auto px-6">
            <h2 class="text-3xl font-bold text-on-surface mb-2">Módulos de la plataforma</h2>
            <p class="text-on-surface-variant mb-8">Un vistazo rápido a cada pieza del ecosistema.</p>

            @php
                $modules = [
                    [
                        'icon' => 'thermostat',
                        'title' => 'Estación Meteorológica',
                        'url' => route('weather_station.index'),
                        'description' => 'Raspberry Pi, Arduino y una serie de sensores tomando datos en Chipiona: humedad, temperatura, presión, viento, luz, índice UV/UVA/UVB, CO2-eCO2, TVOC, calidad del aire y relámpagos.',
                    ],
                    [
                        'icon' => 'potted_plant',
                        'title' => 'Smart Plant',
                        'url' => route('smartplant.index'),
                        'description' => 'Monitorización de plantas delicadas (sobre todo bonsáis) con un ESP32 conectado a placa solar: humedad de tierra y aire, temperatura, luz y nivel del depósito antes de regar.',
                    ],
                    [
                        'icon' => 'keyboard',
                        'title' => 'Key Counter',
                        'url' => route('keycounter.index'),
                        'description' => 'Contador de teclas pulsadas y clics de ratón, agrupado por rachas de actividad, sin comprometer la privacidad: sólo estadísticas generales.',
                    ],
                    [
                        'icon' => 'flight',
                        'title' => 'Radar de vuelo (AirFlight)',
                        'url' => route('airflight.index'),
                        'description' => 'Radar ADS-B con una Raspberry Pi 4 y una capturadora de TDT en la banda de 1090MHz: aviones cercanos a Chipiona, con sus rutas reconstruidas a partir de las coordenadas recibidas.',
                    ],
                    [
                        'icon' => 'bolt',
                        'title' => 'Energía',
                        'url' => route('hardware.energy.index'),
                        'description' => 'Balance fotovoltaico y consumos en tiempo real: generación solar, batería y consumo por dispositivo monitorizado.',
                    ],
                    [
                        'icon' => 'badge',
                        'title' => 'Currículum',
                        'url' => route('cv.index'),
                        'description' => 'Varios currículums públicos, uno por perfil u objetivo, con vista en línea y descarga en PDF generado desde la base de datos.',
                    ],
                ];
            @endphp

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach($modules as $module)
                    <a href="{{ $module['url'] }}"
                       class="bg-surface-container-lowest rounded-xl p-6 shadow-lg hover:shadow-xl transition-shadow flex flex-col group">
                        <div class="w-12 h-12 bg-surface-container rounded-lg flex items-center justify-center mb-4">
                            <span class="material-symbols-outlined text-primary">{{ $module['icon'] }}</span>
                        </div>
                        <h3 class="text-lg font-bold text-on-surface mb-2 group-hover:underline underline-offset-2">{{ $module['title'] }}</h3>
                        <p class="text-on-surface-variant text-sm flex-1">{{ $module['description'] }}</p>
                    </a>
                @endforeach
            </div>
        </div>
    </section>

    {{-- CMS / Blog --}}
    <section class="py-12 bg-surface">
        <div class="max-w-5xl mx-auto px-6">
            <h2 class="text-3xl text-on-surface font-bold leading-none mb-3">CMS y contenidos</h2>
            <p class="text-on-surface-variant mb-3">
                Además de los módulos IoT, la plataforma incluye un CMS multi-plataforma para gestionar contenidos —artículos, páginas, categorías, tecnologías— que consumen mis distintos proyectos, con edición en Markdown y sincronización en la nube desde cualquier equipo.
            </p>
        </div>
    </section>

    {{-- Stack tecnológico --}}
    <section class="bg-surface-dim/30 py-24">
        <div class="max-w-7xl mx-auto px-6 text-center">
            <p class="text-secondary font-bold text-xs uppercase tracking-widest mb-12">Construido con</p>
            <div class="flex flex-wrap justify-center items-center gap-12 md:gap-24 opacity-60 grayscale hover:grayscale-0 transition-all duration-500">
                <span class="font-bold text-2xl text-on-surface">Laravel</span>
                <span class="font-bold text-2xl text-on-surface">Vue.js</span>
                <span class="font-bold text-2xl text-on-surface">Python</span>
                <span class="font-bold text-2xl text-on-surface">Docker</span>
                <span class="font-bold text-2xl text-on-surface">PostgreSQL</span>
            </div>
        </div>
    </section>

    {{-- Autor --}}
    <section class="py-12 bg-surface-container-low">
        <div class="max-w-5xl mx-auto px-6 text-center">
            <h2 class="text-3xl text-on-surface font-bold leading-none mb-3">Quién hay detrás</h2>
            <p class="text-on-surface-variant mb-6 max-w-2xl mx-auto">
                Todo esto lo mantiene <strong>Raúl Caro Pastorino</strong> (@raupulus) en su tiempo libre, como terreno de pruebas y como forma de tener sus propios proyectos IoT bajo un mismo techo.
            </p>
            <a href="https://raupulus.dev"
               target="_blank"
               title="Web de Raúl Caro Pastorino (raupulus)"
               class="inline-block gradient text-white font-bold rounded-full py-4 px-8 shadow-lg hover:scale-105 transition-transform duration-300">
                Ir a raupulus.dev
            </a>
        </div>
    </section>
@endsection
