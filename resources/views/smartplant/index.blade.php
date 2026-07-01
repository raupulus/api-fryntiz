@extends('layouts.app')

@section('title', 'Smart Plant | Api Raupulus')
@section('description', 'Monitorización inteligente de plantas y bonsais con sensores de humedad, temperatura y luz')
@section('keywords', 'smart plant, bonsai, plantas, sensores, Raúl Caro Pastorino, raupulus, esp32, riego')

@section('rs-title', 'Smart Plant - Monitorización de plantas')
@section('rs-sitename', 'Api Raupulus')
@section('rs-description', 'Monitorización inteligente de plantas y bonsais')
@section('rs-image', asset('images/smart-plant/social-thumbnail.jpg'))
@section('rs-url', route('smartplant.index'))

@section('content')
    {{-- Hero --}}
    <section class="hero-gradient min-h-[40vh] flex items-center pt-20">
        <div class="max-w-7xl mx-auto px-6 text-white">
            <h1 class="text-4xl md:text-6xl font-bold tracking-tighter mb-4">Smart Plant</h1>
            <p class="text-xl text-white/80">Monitorización inteligente de plantas y bonsais</p>
        </div>
    </section>

    {{-- Descripción --}}
    <section class="py-12 bg-surface">
        <div class="max-w-5xl mx-auto px-6">
            <h3 class="text-3xl text-on-surface font-bold leading-none mb-3">Sobre este módulo</h3>
            <p class="text-on-surface-variant mb-3">
                SmartPlant es mi sistema de monitorización inteligente para plantas delicadas, principalmente bonsais. Cada planta lleva sensores que registran su estado (humedad de tierra y ambiente, temperatura, luz) para poder observar su comportamiento, asegurar que se cubren sus necesidades, mantener su salud y detectar problemas a tiempo.
            </p>
        </div>
    </section>

    {{-- Listado de plantas --}}
    <section class="py-12 bg-surface-container-low">
        <div class="max-w-7xl mx-auto px-6">
            <h2 class="text-3xl font-bold text-on-surface mb-6">Plantas registradas</h2>

            @if($smartplants->count() > 0)
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach($smartplants as $plant)
                        <div class="bg-surface-container-lowest rounded-xl p-6 shadow-lg">
                            <a href="{{ route('smartplant.show', $plant) }}">
                                <img src="{{ $plant->url_image }}"
                                     alt="{{ $plant->name ?? 'Planta #'.$plant->id }}"
                                     class="w-full h-40 object-cover rounded-lg mb-4">
                                <h3 class="text-xl font-bold text-on-surface mb-2 hover:underline underline-offset-2">{{ $plant->name ?? 'Planta #'.$plant->id }}</h3>
                            </a>
                            <p class="text-on-surface-variant text-sm mb-2">{{ $plant->description ?? 'Sin descripción' }}</p>
                            @if($plant->start_at)
                                <div class="inline-flex items-center gap-1 bg-surface-container rounded-full px-3 py-1 text-xs text-on-surface-variant mb-3">
                                    <span class="material-symbols-outlined text-sm">eco</span>
                                    Siembra: {{ $plant->start_at->format('d/m/Y') }}
                                </div>
                            @endif
                            @if($plant->registers && $plant->registers->count() > 0)
                                @php $lastRegister = $plant->registers->first(); @endphp
                                <div class="grid grid-cols-2 gap-2 mt-4 text-sm">
                                    <div class="bg-surface-container rounded-lg p-2 text-center">
                                        <span class="text-on-surface-variant text-xs">Humedad Tierra</span>
                                        <p class="text-on-surface font-bold">{{ $lastRegister->soil_humidity ?? '-' }}%</p>
                                    </div>
                                    <div class="bg-surface-container rounded-lg p-2 text-center">
                                        <span class="text-on-surface-variant text-xs">Temperatura</span>
                                        <p class="text-on-surface font-bold">{{ $lastRegister->temperature ?? '-' }}°C</p>
                                    </div>
                                    <div class="bg-surface-container rounded-lg p-2 text-center">
                                        <span class="text-on-surface-variant text-xs">Humedad Aire</span>
                                        <p class="text-on-surface font-bold">{{ $lastRegister->humidity ?? '-' }}%</p>
                                    </div>
                                    <div class="bg-surface-container rounded-lg p-2 text-center">
                                        <span class="text-on-surface-variant text-xs">UV</span>
                                        <p class="text-on-surface font-bold">{{ $lastRegister->uv ?? '-' }}</p>
                                    </div>
                                </div>

                                {{-- Estado de tanque, riego y vaporizador --}}
                                <div class="grid grid-cols-3 gap-2 mt-2 text-sm">
                                    <div class="rounded-lg p-2 text-center {{ $lastRegister->full_water_tank ? 'bg-green-100' : 'bg-surface-container' }}">
                                        <span class="material-symbols-outlined block {{ $lastRegister->full_water_tank ? 'text-green-700' : 'text-on-surface-variant' }}">water_full</span>
                                        <span class="text-on-surface-variant text-xs">Tanque lleno</span>
                                    </div>
                                    <div class="rounded-lg p-2 text-center {{ $lastRegister->waterpump_enabled ? 'bg-green-100' : 'bg-surface-container' }}">
                                        <span class="material-symbols-outlined block {{ $lastRegister->waterpump_enabled ? 'text-green-700' : 'text-on-surface-variant' }}">sprinkler</span>
                                        <span class="text-on-surface-variant text-xs">Riego activo</span>
                                    </div>
                                    <div class="rounded-lg p-2 text-center {{ $lastRegister->vaporizer_enabled ? 'bg-green-100' : 'bg-surface-container' }}">
                                        <span class="material-symbols-outlined block {{ $lastRegister->vaporizer_enabled ? 'text-green-700' : 'text-on-surface-variant' }}">humidity_high</span>
                                        <span class="text-on-surface-variant text-xs">Vaporizador</span>
                                    </div>
                                </div>

                                {{-- Tabla de últimas 10 lecturas --}}
                                @if($plant->registers->count() > 1)
                                    <details class="mt-4">
                                        <summary class="text-xs text-on-tertiary-container font-bold cursor-pointer hover:underline">
                                            Ver últimas {{ $plant->registers->count() }} lecturas
                                        </summary>
                                        <div class="overflow-x-auto mt-2">
                                            <table class="w-full text-xs text-on-surface">
                                                <thead class="bg-surface-container text-on-surface-variant uppercase">
                                                    <tr>
                                                        <th class="px-2 py-1">Fecha</th>
                                                        <th class="px-2 py-1">Hum. Tierra</th>
                                                        <th class="px-2 py-1">Temp.</th>
                                                        <th class="px-2 py-1">Hum. Aire</th>
                                                        <th class="px-2 py-1">UV</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($plant->registers as $register)
                                                        <tr class="border-b border-surface-container">
                                                            <td class="px-2 py-1">{{ $register->created_at?->format('d/m/Y H:i') ?? '-' }}</td>
                                                            <td class="px-2 py-1 text-center">{{ $register->soil_humidity ?? '-' }}%</td>
                                                            <td class="px-2 py-1 text-center">{{ $register->temperature ?? '-' }}°C</td>
                                                            <td class="px-2 py-1 text-center">{{ $register->humidity ?? '-' }}%</td>
                                                            <td class="px-2 py-1 text-center">{{ $register->uv ?? '-' }}</td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </details>
                                @endif
                            @endif

                            <a href="{{ route('smartplant.show', $plant) }}"
                               class="inline-flex items-center gap-1 text-xs text-on-tertiary-container font-bold uppercase tracking-widest mt-4 hover:underline">
                                Ver perfil completo
                                <span class="material-symbols-outlined text-sm">arrow_forward</span>
                            </a>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="bg-surface-container rounded-xl p-12 text-center">
                    <span class="material-symbols-outlined text-6xl text-on-surface-variant mb-4">potted_plant</span>
                    <p class="text-on-surface-variant text-lg">No hay plantas registradas actualmente.</p>
                </div>
            @endif
        </div>
    </section>

    {{-- Hardware --}}
    <section class="py-12 bg-surface">
        <div class="max-w-5xl mx-auto px-6">
            <h3 class="text-3xl text-on-surface font-bold leading-none mb-3">Hardware del proyecto</h3>
            <p class="text-on-surface-variant mb-6">
                El gadget que sube los datos de cada planta existe en dos variantes de hardware, ambas con el mismo objetivo: leer los sensores y sincronizar las lecturas con esta API.
            </p>

            @php
                $iotProjects = [
                    [
                        'url' => 'https://gitlab.com/raupulus/esp32-smart-bonsai',
                        'name' => 'esp32-smart-bonsai',
                        'icon' => 'memory',
                        'tags' => ['ESP32 Lite', 'C++', 'Placa solar'],
                        'description' => 'Automatiza el cuidado de las plantas con un ESP32: controla la humedad ambiente, la humedad de la tierra, el riego y la luz recibida. Integra sensores de temperatura, humedad, presión y radiación UV, activando la bomba de agua y el vaporizador según los parámetros detectados, y sincroniza opcionalmente los datos con esta API.',
                    ],
                    [
                        'url' => 'https://github.com/raupulus/rpi-pico-smartplant-gadget',
                        'name' => 'rpi-pico-smartplant-gadget',
                        'icon' => 'developer_board',
                        'tags' => ['Raspberry Pi Pico', 'MicroPython', 'BME280'],
                        'description' => 'Monitoriza entre 1 y 8 plantas con Raspberry Pi Pico y MicroPython: lee la humedad del suelo con sensores analógicos y las condiciones ambientales (temperatura, humedad y presión) con un BME280, descargando su configuración y enviando las lecturas a esta API.',
                    ],
                ];
            @endphp

            <div class="grid grid-cols-1 gap-4">
                @foreach($iotProjects as $project)
                    <a href="{{ $project['url'] }}" target="_blank" rel="noopener"
                       class="bg-surface-container-lowest rounded-xl p-6 shadow-lg hover:shadow-xl transition-shadow flex flex-col sm:flex-row sm:items-center gap-5 group">
                        <div class="w-14 h-14 shrink-0 bg-tertiary-fixed rounded-full flex items-center justify-center">
                            <span class="material-symbols-outlined text-on-tertiary-container text-3xl">{{ $project['icon'] }}</span>
                        </div>
                        <div class="flex-1">
                            <span class="block text-lg font-bold text-on-surface group-hover:underline underline-offset-2">{{ $project['name'] }}</span>
                            <p class="text-on-surface-variant text-sm mt-1 mb-3">{{ $project['description'] }}</p>
                            <div class="flex flex-wrap gap-2">
                                @foreach($project['tags'] as $tag)
                                    <span class="text-xs font-bold text-on-tertiary-container bg-tertiary-fixed rounded-full px-3 py-1">{{ $tag }}</span>
                                @endforeach
                            </div>
                        </div>
                        <span class="material-symbols-outlined text-on-surface-variant shrink-0 self-start sm:self-center">open_in_new</span>
                    </a>
                @endforeach
            </div>
        </div>
    </section>
@endsection
