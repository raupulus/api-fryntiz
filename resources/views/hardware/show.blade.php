@extends('layouts.app')

@section('title', $device->displayName.' | Hardware | Api Raupulus')
@section('description', 'Ficha técnica y estado en tiempo real del dispositivo '.$device->displayName.' ('.$device->typeName.')')
@section('keywords', 'hardware, '.$device->displayName.', '.$device->typeName.', '.$device->brand.', telemetría, raupulus')

@section('rs-title', $device->displayName.' - Hardware')
@section('rs-sitename', 'Api Raupulus')
@section('rs-description', 'Ficha técnica y estado en tiempo real del dispositivo '.$device->displayName)
@if($device->imageUrl)
    @section('rs-image', $device->imageUrl)
@endif
@section('rs-url', route('hardware.show', $device->slug))

@section('content')
    {{-- Hero --}}
    <section class="hero-gradient min-h-[30vh] flex items-center pt-20">
        <div class="max-w-7xl mx-auto px-6 text-white w-full">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    {{-- Migas de pan / Volver --}}
                    <div class="mb-3">
                        <a href="{{ route('hardware.index') }}"
                           class="inline-flex items-center gap-1 text-xs text-white/80 hover:text-white transition-colors">
                            <span class="material-symbols-outlined text-sm">arrow_back</span>
                            Volver al catálogo de hardware
                        </a>
                    </div>

                    <h1 class="text-3xl md:text-5xl font-bold tracking-tighter mb-2">
                        {{ $device->displayName }}
                    </h1>

                    <div class="flex flex-wrap items-center gap-3 mt-2">
                        @if($device->typeName)
                            <span class="bg-white/20 backdrop-blur-md px-3 py-1 rounded-full text-xs font-semibold">
                                {{ $device->typeName }}
                            </span>
                        @endif

                        @if($device->locationTypeLabel)
                            <span class="bg-white/10 backdrop-blur-md px-3 py-1 rounded-full text-xs">
                                {{ $device->locationTypeLabel }}
                            </span>
                        @endif

                        {{-- Estado de conexión --}}
                        @if($device->isOnline)
                            <span class="inline-flex items-center gap-1.5 bg-emerald-100 dark:bg-emerald-950 text-emerald-800 dark:text-emerald-200 px-3 py-1 rounded-full text-xs font-semibold border border-emerald-300 dark:border-emerald-800">
                                <span class="relative flex h-2 w-2">
                                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 dark:bg-emerald-400 opacity-75"></span>
                                    <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500 dark:bg-emerald-500"></span>
                                </span>
                                En línea
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1.5 bg-white/10 text-white/70 px-3 py-1 rounded-full text-xs font-medium">
                                <span class="h-2 w-2 rounded-full bg-white/40"></span>
                                Desconectado
                            </span>
                        @endif
                    </div>
                </div>

                {{-- Última conexión / Uptime --}}
                <div class="text-right text-white/80 text-xs hidden md:block">
                    @if($device->lastSeenFormatted)
                        <p class="mb-1">Última señal: <strong class="text-white">{{ $device->lastSeenFormatted }}</strong> ({{ $device->lastSeenDiff }})</p>
                    @endif
                    @if($device->uptimeFormatted)
                        <p>Tiempo en marcha: <strong class="text-white">{{ $device->uptimeFormatted }}</strong></p>
                    @endif
                </div>
            </div>
        </div>
    </section>

    {{-- Contenido principal --}}
    <div class="bg-surface py-12">
        <div class="max-w-7xl mx-auto px-6 space-y-10">

            {{-- 1. Ficha del Dispositivo --}}
            <section class="bg-surface-container-lowest rounded-xl shadow-lg border border-outline-variant/15 overflow-hidden">
                <div class="grid grid-cols-1 md:grid-cols-12 gap-8 p-6 md:p-8">

                    {{-- Imagen / Placeholder --}}
                    <div class="md:col-span-4 flex items-center justify-center">
                        @if($device->imageUrl)
                            <img src="{{ $device->imageUrl }}"
                                 alt="{{ $device->displayName }}"
                                 class="w-full h-64 md:h-72 object-cover rounded-xl shadow-md border border-outline-variant/15">
                        @else
                            <div class="w-full h-64 md:h-72 bg-surface-container rounded-xl flex flex-col items-center justify-center text-outline-variant">
                                <span class="material-symbols-outlined text-7xl mb-2">memory</span>
                                <span class="text-xs uppercase tracking-widest font-semibold">Sin imagen</span>
                            </div>
                        @endif
                    </div>

                    {{-- Especificaciones generales --}}
                    <div class="md:col-span-8 flex flex-col justify-between">
                        <div>
                            <h2 class="text-2xl font-bold text-on-surface mb-3">Información técnica</h2>

                            {{-- Descripción --}}
                            <div class="text-on-surface-variant text-sm mb-6 leading-relaxed">
                                @if(filled($device->description))
                                    <p>{{ $device->description }}</p>
                                @else
                                    <p class="italic text-on-surface-variant/70">Este dispositivo no dispone de descripción pública adicional.</p>
                                @endif
                            </div>

                            {{-- Grid de especificaciones --}}
                            <div class="grid grid-cols-2 sm:grid-cols-3 gap-4 pt-4 border-t border-outline-variant/15">
                                @if($device->brand)
                                    <div>
                                        <span class="block text-xs uppercase tracking-wider text-on-surface-variant font-medium">Marca</span>
                                        <span class="text-sm font-semibold text-on-surface">{{ $device->brand }}</span>
                                    </div>
                                @endif

                                @if($device->model)
                                    <div>
                                        <span class="block text-xs uppercase tracking-wider text-on-surface-variant font-medium">Modelo</span>
                                        <span class="text-sm font-semibold text-on-surface">{{ $device->model }}</span>
                                    </div>
                                @endif

                                @if($device->softwareVersion)
                                    <div>
                                        <span class="block text-xs uppercase tracking-wider text-on-surface-variant font-medium">Firmware / OS</span>
                                        <span class="text-sm font-semibold text-on-surface">v{{ $device->softwareVersion }}</span>
                                    </div>
                                @endif

                                @if($device->hardwareVersion)
                                    <div>
                                        <span class="block text-xs uppercase tracking-wider text-on-surface-variant font-medium">Versión HW</span>
                                        <span class="text-sm font-semibold text-on-surface">{{ $device->hardwareVersion }}</span>
                                    </div>
                                @endif

                                @if($device->batteryType)
                                    <div>
                                        <span class="block text-xs uppercase tracking-wider text-on-surface-variant font-medium">Tipo Batería</span>
                                        <span class="text-sm font-semibold text-on-surface">{{ $device->batteryType }}</span>
                                    </div>
                                @endif

                                @if($device->batteryNominalCapacity)
                                    <div>
                                        <span class="block text-xs uppercase tracking-wider text-on-surface-variant font-medium">Capacidad Batería</span>
                                        <span class="text-sm font-semibold text-on-surface">{{ $device->batteryNominalCapacity }}</span>
                                    </div>
                                @endif
                            </div>
                        </div>

                        {{-- Enlace del fabricante si existe --}}
                        @if($device->urlCompany)
                            <div class="pt-6 mt-6 border-t border-outline-variant/15">
                                <a href="{{ $device->urlCompany }}"
                                   target="_blank"
                                   rel="noopener noreferrer"
                                   class="inline-flex items-center gap-1.5 text-xs font-semibold text-primary-container hover:underline">
                                    <span class="material-symbols-outlined text-sm">open_in_new</span>
                                    Documentación / Web oficial del fabricante
                                </a>
                            </div>
                        @endif
                    </div>
                </div>
            </section>

            {{-- 2. Telemetría y Salud en tiempo real --}}
            @if($device->temp !== null || $device->cpu !== null || $device->ram !== null || $device->disk !== null || $device->voltage !== null || $device->batteryLevel !== null)
                <section class="space-y-4">
                    <h2 class="text-2xl font-bold text-on-surface">Telemetría en tiempo real</h2>

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">

                        {{-- Temperatura --}}
                        @if($device->temp !== null)
                            <div class="bg-surface-container-lowest p-5 rounded-xl shadow border border-outline-variant/15">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-xs uppercase tracking-wider text-on-surface-variant font-bold">Temperatura</span>
                                    <span class="material-symbols-outlined text-lg {{ $device->tempColor === 'emerald' ? 'text-emerald-600 dark:text-emerald-400' : ($device->tempColor === 'amber' ? 'text-amber-600 dark:text-amber-400' : 'text-rose-600 dark:text-rose-400') }}">thermostat</span>
                                </div>
                                <div class="text-3xl font-extrabold {{ $device->tempColor === 'emerald' ? 'text-emerald-600 dark:text-emerald-400' : ($device->tempColor === 'amber' ? 'text-amber-600 dark:text-amber-400' : 'text-rose-600 dark:text-rose-400') }}">
                                    {{ number_format($device->temp, 1) }} <span class="text-base font-medium">°C</span>
                                </div>
                                <span class="text-xs text-on-surface-variant mt-1 block">Temperatura del SoC / placa</span>
                            </div>
                        @endif

                        {{-- Uso de CPU --}}
                        @if($device->cpu !== null)
                            <div class="bg-surface-container-lowest p-5 rounded-xl shadow border border-outline-variant/15">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-xs uppercase tracking-wider text-on-surface-variant font-bold">Carga CPU</span>
                                    <span class="material-symbols-outlined text-lg text-sky-600 dark:text-sky-400">developer_board</span>
                                </div>
                                <div class="text-3xl font-extrabold text-on-surface">
                                    {{ number_format($device->cpu, 0) }} <span class="text-base font-medium">%</span>
                                </div>
                                <div class="w-full bg-surface-container rounded-full h-2 mt-3 overflow-hidden">
                                    <div class="bg-sky-600 dark:bg-sky-400 h-2 rounded-full" style="width: {{ min(100, max(0, $device->cpu)) }}%"></div>
                                </div>
                            </div>
                        @endif

                        {{-- Uso de Memoria RAM --}}
                        @if($device->ram !== null)
                            <div class="bg-surface-container-lowest p-5 rounded-xl shadow border border-outline-variant/15">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-xs uppercase tracking-wider text-on-surface-variant font-bold">Memoria RAM</span>
                                    <span class="material-symbols-outlined text-lg text-violet-600 dark:text-violet-400">memory</span>
                                </div>
                                <div class="text-3xl font-extrabold text-on-surface">
                                    {{ number_format($device->ram, 0) }} <span class="text-base font-medium">%</span>
                                </div>
                                <div class="w-full bg-surface-container rounded-full h-2 mt-3 overflow-hidden">
                                    <div class="bg-violet-600 dark:bg-violet-400 h-2 rounded-full" style="width: {{ min(100, max(0, $device->ram)) }}%"></div>
                                </div>
                            </div>
                        @endif

                        {{-- Almacenamiento / Disco --}}
                        @if($device->disk !== null)
                            <div class="bg-surface-container-lowest p-5 rounded-xl shadow border border-outline-variant/15">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-xs uppercase tracking-wider text-on-surface-variant font-bold">Almacenamiento</span>
                                    <span class="material-symbols-outlined text-lg text-indigo-600 dark:text-indigo-400">storage</span>
                                </div>
                                <div class="text-3xl font-extrabold text-on-surface">
                                    {{ number_format($device->disk, 0) }} <span class="text-base font-medium">%</span>
                                </div>
                                <div class="w-full bg-surface-container rounded-full h-2 mt-3 overflow-hidden">
                                    <div class="bg-indigo-600 dark:bg-indigo-400 h-2 rounded-full" style="width: {{ min(100, max(0, $device->disk)) }}%"></div>
                                </div>
                            </div>
                        @endif

                        {{-- Nivel de Batería --}}
                        @if($device->batteryLevel !== null)
                            <div class="bg-surface-container-lowest p-5 rounded-xl shadow border border-outline-variant/15">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-xs uppercase tracking-wider text-on-surface-variant font-bold">Batería</span>
                                    <span class="material-symbols-outlined text-lg text-emerald-600 dark:text-emerald-400">battery_charging_full</span>
                                </div>
                                <div class="text-3xl font-extrabold text-emerald-600 dark:text-emerald-400">
                                    {{ $device->batteryLevel }} <span class="text-base font-medium">%</span>
                                </div>
                                @if($device->batteryVoltage !== null)
                                    <span class="text-xs text-on-surface-variant mt-1 block">Tensión: {{ number_format($device->batteryVoltage, 2) }} V</span>
                                @endif
                            </div>
                        @endif

                        {{-- Tensión / Voltaje --}}
                        @if($device->voltage !== null)
                            <div class="bg-surface-container-lowest p-5 rounded-xl shadow border border-outline-variant/15">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-xs uppercase tracking-wider text-on-surface-variant font-bold">Alimentación</span>
                                    <span class="material-symbols-outlined text-lg text-amber-600 dark:text-amber-400">bolt</span>
                                </div>
                                <div class="text-3xl font-extrabold text-on-surface">
                                    {{ number_format($device->voltage, 2) }} <span class="text-base font-medium">V</span>
                                </div>
                                <span class="text-xs text-on-surface-variant mt-1 block">Voltaje en bus de alimentación</span>
                            </div>
                        @endif

                        {{-- Uptime --}}
                        @if($device->uptimeFormatted)
                            <div class="bg-surface-container-lowest p-5 rounded-xl shadow border border-outline-variant/15">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-xs uppercase tracking-wider text-on-surface-variant font-bold">Uptime</span>
                                    <span class="material-symbols-outlined text-lg text-teal-600 dark:text-teal-400">timer</span>
                                </div>
                                <div class="text-2xl font-bold text-on-surface">
                                    {{ $device->uptimeFormatted }}
                                </div>
                                <span class="text-xs text-on-surface-variant mt-1 block">Tiempo continuo en funcionamiento</span>
                            </div>
                        @endif

                    </div>
                </section>
            @endif

            {{-- 3. Componentes y Sensores acoplados --}}
            <section class="space-y-4">
                <h2 class="text-2xl font-bold text-on-surface">Componentes y sensores conectados</h2>

                @if(!empty($device->components))
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        @foreach($device->components as $component)
                            <div class="bg-surface-container-lowest p-5 rounded-xl shadow border border-outline-variant/15 flex flex-col justify-between">
                                <div>
                                    <div class="flex items-start justify-between gap-2 mb-2">
                                        <h3 class="font-bold text-on-surface text-base">
                                            {{ $component['name'] }}
                                        </h3>
                                        @if($component['available_type'])
                                            <span class="bg-surface-container px-2 py-0.5 rounded text-xs text-on-surface-variant font-medium uppercase tracking-wider">
                                                {{ $component['available_type'] }}
                                            </span>
                                        @endif
                                    </div>

                                    @if($component['brand'] || $component['model'])
                                        <p class="text-xs text-on-surface-variant font-medium mb-2">
                                            {{ trim(($component['brand'] ? $component['brand'].' ' : '').($component['model'] ?? '')) }}
                                        </p>
                                    @endif

                                    @if($component['description'])
                                        <p class="text-xs text-on-surface-variant leading-relaxed mb-3">
                                            {{ $component['description'] }}
                                        </p>
                                    @endif
                                </div>

                                <div class="pt-3 border-t border-outline-variant/15 flex items-center justify-between text-xs text-on-surface-variant">
                                    @if($component['quantity'])
                                        <span>Cantidad: <strong>{{ $component['quantity'] }}</strong></span>
                                    @endif

                                    @if($component['power'])
                                        <span>Consumo: <strong>{{ $component['power'] }} W</strong></span>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="bg-surface-container-lowest p-8 rounded-xl shadow border border-outline-variant/15 text-center text-on-surface-variant">
                        <span class="material-symbols-outlined text-4xl text-outline-variant mb-2 block">extension_off</span>
                        <p class="text-sm">Este dispositivo no tiene sensores ni componentes adicionales registrados.</p>
                    </div>
                @endif
            </section>

        </div>
    </div>
@endsection
