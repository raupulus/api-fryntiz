@extends('layouts.app')

@section('title', 'Hardware | Api Raupulus')
@section('description', 'Catálogo y monitorización pública de dispositivos hardware, nodos IoT y servidores de desarrollo')
@section('keywords', 'hardware, iot, servidores, microcontroladores, esp32, raspberry pi, telemetría, raupulus')

@section('rs-title', 'Hardware - Catálogo de dispositivos y nodos')
@section('rs-sitename', 'Api Raupulus')
@section('rs-description', 'Catálogo y monitorización de hardware y nodos IoT de desarrollo')
@section('rs-url', route('hardware.index'))

@section('content')
    {{-- Hero --}}
    <section class="hero-gradient min-h-[35vh] flex items-center pt-20">
        <div class="max-w-7xl mx-auto px-6 text-white w-full">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
                <div>
                    <h1 class="text-4xl md:text-6xl font-bold tracking-tighter mb-3">Hardware</h1>
                    <p class="text-xl text-white/80 max-w-2xl">
                        Catálogo de equipos, nodos IoT y microcontroladores con telemetría de salud en tiempo real.
                    </p>
                </div>

                {{-- Badges de resumen --}}
                <div class="flex flex-wrap gap-3">
                    <div class="bg-white/10 backdrop-blur-md rounded-xl px-5 py-3 border border-white/10 text-center">
                        <span class="block text-2xl font-extrabold text-white">{{ $totalCount }}</span>
                        <span class="text-xs uppercase tracking-wider text-white/70">Dispositivos</span>
                    </div>
                    <div class="bg-white/10 backdrop-blur-md rounded-xl px-5 py-3 border border-white/10 text-center">
                        <span class="block text-2xl font-extrabold text-emerald-300 dark:text-emerald-400">{{ $onlineCount }}</span>
                        <span class="text-xs uppercase tracking-wider text-white/70">En línea</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Contenedor interactivo con filtros Alpine.js --}}
    <div x-data="{ selectedType: 'all', onlineOnly: false }" class="bg-surface">

        {{-- Barra de filtros --}}
        <section class="border-b border-outline-variant/15 py-6">
            <div class="max-w-7xl mx-auto px-6 flex flex-wrap items-center justify-between gap-4">

                {{-- Filtro por categoría --}}
                <div class="flex flex-wrap items-center gap-2">
                    <button type="button"
                            @click="selectedType = 'all'"
                            :class="selectedType === 'all'
                                ? 'bg-primary-container text-on-primary font-semibold shadow-sm'
                                : 'bg-surface-container text-on-surface-variant hover:text-on-surface hover:bg-surface-container-high'"
                            class="px-4 py-2 rounded-full text-xs transition-colors">
                        Todos ({{ $totalCount }})
                    </button>

                    @foreach($types as $type)
                        <button type="button"
                                @click="selectedType = '{{ $type }}'"
                                :class="selectedType === '{{ $type }}'
                                    ? 'bg-primary-container text-on-primary font-semibold shadow-sm'
                                    : 'bg-surface-container text-on-surface-variant hover:text-on-surface hover:bg-surface-container-high'"
                                class="px-4 py-2 rounded-full text-xs transition-colors">
                            {{ $type }}
                        </button>
                    @endforeach
                </div>

                {{-- Toggle: solo en línea --}}
                <div class="flex items-center">
                    <button type="button"
                            @click="onlineOnly = !onlineOnly"
                            :class="onlineOnly
                                ? 'bg-emerald-100 dark:bg-emerald-950 text-emerald-800 dark:text-emerald-200 border-emerald-300 dark:border-emerald-800 font-semibold'
                                : 'bg-surface-container border-outline-variant/20 text-on-surface-variant hover:text-on-surface'"
                            class="inline-flex items-center gap-2 px-4 py-2 rounded-full text-xs border transition-colors">
                        <span class="relative flex h-2.5 w-2.5">
                            <span :class="onlineOnly ? 'animate-ping' : ''" class="absolute inline-flex h-full w-full rounded-full bg-emerald-400 dark:bg-emerald-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-emerald-500 dark:bg-emerald-500"></span>
                        </span>
                        <span>Solo en línea</span>
                    </button>
                </div>
            </div>
        </section>

        {{-- Grid de dispositivos --}}
        <section class="py-12 bg-surface-container-low min-h-[50vh]">
            <div class="max-w-7xl mx-auto px-6">

                @if($devices->count() > 0)
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        @foreach($devices as $device)
                            <article x-show="(selectedType === 'all' || selectedType === '{{ $device->typeName }}') && (!onlineOnly || {{ $device->isOnline ? 'true' : 'false' }})"
                                     x-transition
                                     class="bg-surface-container-lowest rounded-xl shadow-lg overflow-hidden flex flex-col justify-between border border-outline-variant/15 hover:shadow-xl transition-all duration-300">

                                {{-- Cabecera / Miniatura --}}
                                <div class="relative">
                                    @if($device->imageThumbnailUrl)
                                        <img src="{{ $device->imageThumbnailUrl }}"
                                             alt="{{ $device->displayName }}"
                                             class="w-full h-48 object-cover">
                                    @else
                                        <div class="w-full h-48 bg-surface-container flex items-center justify-center">
                                            <span class="material-symbols-outlined text-outline-variant text-6xl">developer_board</span>
                                        </div>
                                    @endif

                                    {{-- Badge de estado Online/Offline --}}
                                    <div class="absolute top-3 right-3">
                                        @if($device->isOnline)
                                            <span class="inline-flex items-center gap-1.5 bg-emerald-100 dark:bg-emerald-950 text-emerald-800 dark:text-emerald-200 text-xs font-semibold px-3 py-1 rounded-full border border-emerald-300 dark:border-emerald-800 shadow-sm">
                                                <span class="relative flex h-2 w-2">
                                                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 dark:bg-emerald-400 opacity-75"></span>
                                                    <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500 dark:bg-emerald-500"></span>
                                                </span>
                                                En línea
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1.5 bg-surface-container-highest/90 backdrop-blur-md text-on-surface-variant text-xs font-medium px-3 py-1 rounded-full border border-outline-variant/20 shadow-sm">
                                                <span class="h-2 w-2 rounded-full bg-outline-variant"></span>
                                                Desconectado
                                            </span>
                                        @endif
                                    </div>

                                    {{-- Tipo de hardware flotante --}}
                                    @if($device->typeName)
                                        <div class="absolute bottom-3 left-3">
                                            <span class="inline-block bg-surface-container-lowest/90 backdrop-blur-md text-on-surface text-xs font-bold px-3 py-1 rounded-lg border border-outline-variant/15 shadow-sm">
                                                {{ $device->typeName }}
                                            </span>
                                        </div>
                                    @endif
                                </div>

                                {{-- Cuerpo de la tarjeta --}}
                                <div class="p-6 flex-1 flex flex-col justify-between">
                                    <div>
                                        <div class="flex items-start justify-between gap-2 mb-1">
                                            <h3 class="text-xl font-bold text-on-surface hover:text-primary transition-colors">
                                                <a href="{{ route('hardware.show', $device->id) }}">
                                                    {{ $device->displayName }}
                                                </a>
                                            </h3>
                                        </div>

                                        {{-- Marca y modelo / Versión --}}
                                        @if($device->brand || $device->model || $device->softwareVersion)
                                            <p class="text-xs text-on-surface-variant mb-3 font-medium">
                                                {{ trim(($device->brand ? $device->brand.' ' : '').($device->model ?? '')) }}
                                                @if($device->softwareVersion)
                                                    <span class="ml-1 px-2 py-0.5 bg-surface-container rounded text-xs">v{{ $device->softwareVersion }}</span>
                                                @endif
                                            </p>
                                        @endif

                                        {{-- Descripción --}}
                                        <p class="text-on-surface-variant text-sm mb-4 line-clamp-2">
                                            {{ $device->description ?: 'Dispositivo físico integrado en la red de monitorización.' }}
                                        </p>

                                        {{-- Métricas de telemetría destacadas --}}
                                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 mb-4">
                                            {{-- Temperatura --}}
                                            @if($device->temp !== null)
                                                <div class="bg-surface-container rounded-lg p-2 text-center">
                                                    <span class="text-xs text-on-surface-variant block">Temp</span>
                                                    <span class="text-sm font-bold {{ $device->tempColor === 'emerald' ? 'text-emerald-600 dark:text-emerald-400' : ($device->tempColor === 'amber' ? 'text-amber-600 dark:text-amber-400' : 'text-rose-600 dark:text-rose-400') }}">
                                                        {{ number_format($device->temp, 1) }}°C
                                                    </span>
                                                </div>
                                            @endif

                                            {{-- CPU --}}
                                            @if($device->cpu !== null)
                                                <div class="bg-surface-container rounded-lg p-2 text-center">
                                                    <span class="text-xs text-on-surface-variant block">CPU</span>
                                                    <span class="text-sm font-bold text-on-surface">
                                                        {{ number_format($device->cpu, 0) }}%
                                                    </span>
                                                </div>
                                            @endif

                                            {{-- RAM --}}
                                            @if($device->ram !== null)
                                                <div class="bg-surface-container rounded-lg p-2 text-center">
                                                    <span class="text-xs text-on-surface-variant block">RAM</span>
                                                    <span class="text-sm font-bold text-on-surface">
                                                        {{ number_format($device->ram, 0) }}%
                                                    </span>
                                                </div>
                                            @endif

                                            {{-- Disco o Batería --}}
                                            @if($device->batteryLevel !== null)
                                                <div class="bg-surface-container rounded-lg p-2 text-center">
                                                    <span class="text-xs text-on-surface-variant block">Batería</span>
                                                    <span class="text-sm font-bold text-emerald-600 dark:text-emerald-400">
                                                        {{ $device->batteryLevel }}%
                                                    </span>
                                                </div>
                                            @elseif($device->disk !== null)
                                                <div class="bg-surface-container rounded-lg p-2 text-center">
                                                    <span class="text-xs text-on-surface-variant block">Disco</span>
                                                    <span class="text-sm font-bold text-on-surface">
                                                        {{ number_format($device->disk, 0) }}%
                                                    </span>
                                                </div>
                                            @endif
                                        </div>

                                        {{-- Componentes acoplados (tags) --}}
                                        @if(!empty($device->components))
                                            <div class="flex flex-wrap gap-1.5 mb-4">
                                                @foreach(array_slice($device->components, 0, 3) as $comp)
                                                    <span class="inline-flex items-center gap-1 bg-surface-container rounded-md px-2 py-0.5 text-xs text-on-surface-variant font-medium">
                                                        <span class="material-symbols-outlined text-xs">extension</span>
                                                        {{ $comp['name'] }}
                                                    </span>
                                                @endforeach
                                                @if(count($device->components) > 3)
                                                    <span class="text-xs text-on-surface-variant self-center font-medium">
                                                        +{{ count($device->components) - 3 }} más
                                                    </span>
                                                @endif
                                            </div>
                                        @endif
                                    </div>

                                    {{-- Pie de tarjeta: último visto y botón ficha técnica --}}
                                    <div class="pt-4 border-t border-outline-variant/15 flex items-center justify-between gap-2 mt-auto">
                                        <div class="text-xs text-on-surface-variant">
                                            @if($device->lastSeenDiff)
                                                <span class="flex items-center gap-1">
                                                    <span class="material-symbols-outlined text-xs">schedule</span>
                                                    {{ $device->lastSeenDiff }}
                                                </span>
                                            @elseif($device->uptimeFormatted)
                                                <span class="flex items-center gap-1">
                                                    <span class="material-symbols-outlined text-xs">timer</span>
                                                    Up: {{ $device->uptimeFormatted }}
                                                </span>
                                            @else
                                                <span>Sin telemetría</span>
                                            @endif
                                        </div>

                                        <a href="{{ route('hardware.show', $device->id) }}"
                                           class="inline-flex items-center gap-1 text-xs font-bold text-primary-container hover:underline underline-offset-2">
                                            Ficha técnica
                                            <span class="material-symbols-outlined text-sm">arrow_forward</span>
                                        </a>
                                    </div>
                                </div>
                            </article>
                        @endforeach
                    </div>
                @else
                    {{-- Estado vacío si no hay dispositivos públicos --}}
                    <div class="bg-surface-container-lowest rounded-xl p-12 text-center max-w-xl mx-auto shadow-md border border-outline-variant/15">
                        <span class="material-symbols-outlined text-5xl text-outline-variant mb-4 block">devices</span>
                        <h3 class="text-xl font-bold text-on-surface mb-2">No hay dispositivos visibles</h3>
                        <p class="text-sm text-on-surface-variant">
                            Actualmente no hay dispositivos hardware configurados para mostrarse públicamente en este catálogo.
                        </p>
                    </div>
                @endif
            </div>
        </section>
    </div>
@endsection
