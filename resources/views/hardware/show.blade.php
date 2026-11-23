@extends('layouts.app')

@use('App\Support\Format\Figures')

@section('title', $device->displayName.' | Hardware | Api Raupulus')
@section('description', 'Ficha técnica, especificaciones y telemetría de salud en tiempo real del dispositivo '.$device->displayName.' ('.$device->typeName.').')
@section('keywords', 'hardware, '.$device->displayName.', '.$device->typeName.', '.$device->brand.', telemetría, raupulus')

@section('rs-title', $device->displayName.' | Hardware | Api Raupulus')
@section('rs-sitename', 'Api Raupulus')
@section('rs-description', 'Ficha técnica y telemetría en tiempo real del dispositivo '.$device->displayName)
@if($device->imageUrl)
    @section('rs-image', $device->imageUrl)
@endif
@section('rs-url', route('hardware.show', $device->slug))

@push('json-ld')
    @php
        $hardwareSchema = [
            '@context' => 'https://schema.org',
            '@graph' => [
                [
                    '@type' => 'TechArticle',
                    'headline' => $device->displayName,
                    'description' => 'Ficha técnica y monitorización del dispositivo '.$device->displayName.' ('.$device->typeName.').',
                    'url' => route('hardware.show', $device->slug),
                    'author' => [
                        '@type' => 'Person',
                        'name' => 'Raúl Caro Pastorino',
                        'url' => 'https://raupulus.dev',
                    ],
                ],
                [
                    '@type' => 'BreadcrumbList',
                    'itemListElement' => [
                        [
                            '@type' => 'ListItem',
                            'position' => 1,
                            'name' => 'Portada',
                            'item' => route('home'),
                        ],
                        [
                            '@type' => 'ListItem',
                            'position' => 2,
                            'name' => 'Hardware',
                            'item' => route('hardware.index'),
                        ],
                        [
                            '@type' => 'ListItem',
                            'position' => 3,
                            'name' => $device->displayName,
                            'item' => route('hardware.show', $device->slug),
                        ],
                    ],
                ],
            ],
        ];
    @endphp
    <script type="application/ld+json">@json($hardwareSchema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG)</script>
@endpush

@section('content')
    {{-- Hero --}}
    <section class="hero-gradient min-h-[30svh] flex items-center pt-20 pb-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 text-on-primary w-full">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    {{-- Migas de pan / Volver --}}
                    <div class="mb-3">
                        <a href="{{ route('hardware.index') }}"
                           class="inline-flex items-center gap-1 text-xs text-on-primary/85 hover:text-on-primary transition-colors">
                            <span class="material-symbols-outlined text-sm" aria-hidden="true">arrow_back</span>
                            Volver al catálogo de hardware
                        </a>
                    </div>

                    <h1 class="text-3xl md:text-5xl font-bold tracking-tighter mb-2">
                        {{ $device->displayName }}
                    </h1>

                    <div class="flex flex-wrap items-center gap-3 mt-2">
                        @if($device->typeName)
                            <span class="bg-on-primary/20 backdrop-blur-md px-3 py-1 rounded-full text-xs font-semibold">
                                {{ $device->typeName }}
                            </span>
                        @endif

                        @if($device->locationTypeLabel)
                            <span class="bg-on-primary/10 backdrop-blur-md px-3 py-1 rounded-full text-xs">
                                {{ $device->locationTypeLabel }}
                            </span>
                        @endif

                        {{-- Estado de conexión --}}
                        @if($device->isOnline)
                            <span class="inline-flex items-center gap-1.5 bg-surface-container-lowest/95 text-success px-3 py-1 rounded-full text-xs font-semibold border border-success/40">
                                <span class="relative flex h-2 w-2" aria-hidden="true">
                                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-success opacity-75"></span>
                                    <span class="relative inline-flex rounded-full h-2 w-2 bg-success"></span>
                                </span>
                                En línea
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1.5 bg-on-primary/10 text-on-primary/80 px-3 py-1 rounded-full text-xs font-medium">
                                <span class="h-2 w-2 rounded-full bg-on-primary/50" aria-hidden="true"></span>
                                Desconectado
                            </span>
                        @endif
                    </div>
                </div>

                {{-- Última conexión / Uptime --}}
                <div class="text-right text-on-primary/85 text-xs hidden md:block">
                    @if($device->lastSeenDiff)
                        <p class="mb-1">Última señal: <strong class="text-on-primary">{{ $device->lastSeenDiff }}</strong></p>
                    @endif
                    @if($device->uptimeFormatted)
                        <p>Tiempo en marcha: <strong class="text-on-primary">{{ $device->uptimeFormatted }}</strong></p>
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
                                        <span class="text-sm font-semibold text-on-surface">{{ number_format($device->batteryNominalCapacity) }} mAh</span>
                                    </div>
                                @endif
                            </div>
                        </div>

                        {{-- Enlace del fabricante si existe --}}
                        @if($device->urlCompany && \App\Services\Content\ContentBlockValidator::isSafeUrl($device->urlCompany, false))
                            <div class="pt-6 mt-6 border-t border-outline-variant/15">
                                <a href="{{ $device->urlCompany }}"
                                   target="_blank"
                                   rel="noopener noreferrer"
                                   class="inline-flex items-center gap-1.5 text-xs font-semibold text-primary-container hover:underline"
                                   title="Sitio web oficial externo del fabricante">
                                    <span class="material-symbols-outlined text-sm" aria-hidden="true">open_in_new</span>
                                    Web oficial del fabricante <span class="text-xs text-on-surface-variant font-normal">(sitio externo)</span>
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
                                    <span class="material-symbols-outlined text-lg {{ $device->tempColor === 'emerald' ? 'text-success' : ($device->tempColor === 'amber' ? 'text-on-warning-container' : 'text-error') }}" aria-hidden="true">thermostat</span>
                                </div>
                                <div class="text-3xl font-extrabold {{ $device->tempColor === 'emerald' ? 'text-success' : ($device->tempColor === 'amber' ? 'text-on-warning-container' : 'text-error') }}">
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
                                    <span class="material-symbols-outlined text-lg text-primary" aria-hidden="true">developer_board</span>
                                </div>
                                <div class="text-3xl font-extrabold text-on-surface">
                                    {{ number_format($device->cpu, 0) }} <span class="text-base font-medium">%</span>
                                </div>
                                <div class="w-full bg-surface-container rounded-full h-2 mt-3 overflow-hidden">
                                    <div class="bg-primary-container h-2 rounded-full" style="width: {{ min(100, max(0, $device->cpu)) }}%"></div>
                                </div>
                            </div>
                        @endif

                        {{-- Uso de Memoria RAM --}}
                        @if($device->ram !== null)
                            <div class="bg-surface-container-lowest p-5 rounded-xl shadow border border-outline-variant/15">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-xs uppercase tracking-wider text-on-surface-variant font-bold">Memoria RAM</span>
                                    <span class="material-symbols-outlined text-lg text-tertiary" aria-hidden="true">memory</span>
                                </div>
                                <div class="text-3xl font-extrabold text-on-surface">
                                    {{ number_format($device->ram, 0) }} <span class="text-base font-medium">%</span>
                                </div>
                                <div class="w-full bg-surface-container rounded-full h-2 mt-3 overflow-hidden">
                                    <div class="bg-tertiary h-2 rounded-full" style="width: {{ min(100, max(0, $device->ram)) }}%"></div>
                                </div>
                            </div>
                        @endif

                        {{-- Almacenamiento / Disco --}}
                        @if($device->disk !== null)
                            <div class="bg-surface-container-lowest p-5 rounded-xl shadow border border-outline-variant/15">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-xs uppercase tracking-wider text-on-surface-variant font-bold">Almacenamiento</span>
                                    <span class="material-symbols-outlined text-lg text-secondary" aria-hidden="true">storage</span>
                                </div>
                                <div class="text-3xl font-extrabold text-on-surface">
                                    {{ number_format($device->disk, 0) }} <span class="text-base font-medium">%</span>
                                </div>
                                <div class="w-full bg-surface-container rounded-full h-2 mt-3 overflow-hidden">
                                    <div class="bg-secondary h-2 rounded-full" style="width: {{ min(100, max(0, $device->disk)) }}%"></div>
                                </div>
                            </div>
                        @endif

                        {{-- Nivel de Batería --}}
                        @if($device->batteryLevel !== null)
                            <div class="bg-surface-container-lowest p-5 rounded-xl shadow border border-outline-variant/15">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-xs uppercase tracking-wider text-on-surface-variant font-bold">Batería</span>
                                    <span class="material-symbols-outlined text-lg text-success" aria-hidden="true">battery_charging_full</span>
                                </div>
                                <div class="text-3xl font-extrabold text-success">
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
                                    <span class="material-symbols-outlined text-lg text-on-warning-container" aria-hidden="true">bolt</span>
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
                                    <span class="material-symbols-outlined text-lg text-primary" aria-hidden="true">timer</span>
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

            {{-- 3. Gráfica Energética (Últimos 7 días) --}}
            @if($energyData !== null)
                <section class="space-y-4">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                        <div>
                            <h2 class="text-2xl font-bold text-on-surface">Monitorización de energía (Últimos 7 días)</h2>
                            <p class="text-xs text-on-surface-variant">
                                {{ $energyData['has_generator'] ? 'Balance diario de generación solar y consumo eléctrico registrado en la última semana.' : 'Consumo eléctrico diario registrado en la última semana.' }}
                            </p>
                        </div>

                        {{-- Leyenda de colores --}}
                        <div class="flex flex-wrap items-center gap-3 text-xs text-on-surface-variant font-medium">
                            @if($energyData['has_generator'])
                                <div class="flex items-center gap-1.5">
                                    <span class="w-3 h-3 rounded-full bg-warning-container border border-on-warning-container/40"></span>
                                    <span>Generación</span>
                                </div>
                            @endif

                            @foreach($energyData['load_channels'] as $ch)
                                <div class="flex items-center gap-1.5">
                                    <span class="w-3 h-3 rounded-full {{ $ch['color_bar'] }}"></span>
                                    <span>{{ $ch['name'] }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    {{-- Tarjeta principal con KPIs y Gráfico --}}
                    <div class="bg-surface-container-lowest p-6 rounded-xl shadow border border-outline-variant/15 space-y-6">

                        {{-- KPIs de resumen del periodo --}}
                        <div class="grid grid-cols-1 sm:grid-cols-2 {{ $energyData['has_generator'] ? 'md:grid-cols-3' : (count($energyData['load_channels']) > 1 ? 'md:grid-cols-3' : 'md:grid-cols-2') }} gap-4">
                            {{-- Total Consumido --}}
                            <div class="bg-surface-container/60 p-4 rounded-lg border border-outline-variant/10">
                                <div class="flex items-center gap-2 mb-1">
                                    <span class="material-symbols-outlined text-base text-primary" aria-hidden="true">bolt</span>
                                    <span class="text-xs font-semibold uppercase tracking-wider text-on-surface-variant">Consumo Total</span>
                                </div>
                                <div class="text-2xl font-extrabold text-on-surface font-mono">
                                    {{ Figures::rounded($energyData['total_consumed_wh']) }} <span class="text-xs font-sans text-on-surface-variant font-normal">Wh</span>
                                </div>
                            </div>

                            @if($energyData['has_generator'])
                                {{-- Total Generado --}}
                                <div class="bg-surface-container/60 p-4 rounded-lg border border-outline-variant/10">
                                    <div class="flex items-center gap-2 mb-1">
                                        <span class="material-symbols-outlined text-base text-on-warning-container" aria-hidden="true">solar_power</span>
                                        <span class="text-xs font-semibold uppercase tracking-wider text-on-surface-variant">Generación Total</span>
                                    </div>
                                    <div class="text-2xl font-extrabold text-on-warning-container font-mono">
                                        {{ Figures::rounded($energyData['total_generated_wh']) }} <span class="text-xs font-sans text-on-surface-variant font-normal">Wh</span>
                                    </div>
                                </div>

                                {{-- Balance Neto --}}
                                @php
                                    $netBalance = $energyData['total_generated_wh'] - $energyData['total_consumed_wh'];
                                @endphp
                                <div class="bg-surface-container/60 p-4 rounded-lg border border-outline-variant/10">
                                    <div class="flex items-center gap-2 mb-1">
                                        <span class="material-symbols-outlined text-base text-primary" aria-hidden="true">balance</span>
                                        <span class="text-xs font-semibold uppercase tracking-wider text-on-surface-variant">Balance Neto</span>
                                    </div>
                                    <div class="text-2xl font-extrabold font-mono {{ $netBalance >= 0 ? 'text-success' : 'text-error' }}">
                                        {{ ($netBalance > 0 ? '+' : '').Figures::rounded($netBalance) }} <span class="text-xs font-sans text-on-surface-variant font-normal">Wh</span>
                                    </div>
                                </div>
                            @else
                                {{-- Media diaria --}}
                                <div class="bg-surface-container/60 p-4 rounded-lg border border-outline-variant/10">
                                    <div class="flex items-center gap-2 mb-1">
                                        <span class="material-symbols-outlined text-base text-primary" aria-hidden="true">trending_up</span>
                                        <span class="text-xs font-semibold uppercase tracking-wider text-on-surface-variant">Media Diaria</span>
                                    </div>
                                    <div class="text-2xl font-extrabold text-on-surface font-mono">
                                        {{ Figures::rounded($energyData['avg_consumed_wh']) }} <span class="text-xs font-sans text-on-surface-variant font-normal">Wh/día</span>
                                    </div>
                                </div>

                                {{-- Desglose si hay múltiples canales --}}
                                @if(count($energyData['load_channels']) > 1)
                                    <div class="bg-surface-container/60 p-4 rounded-lg border border-outline-variant/10">
                                        <div class="flex items-center gap-2 mb-1">
                                            <span class="material-symbols-outlined text-base text-tertiary" aria-hidden="true">tune</span>
                                            <span class="text-xs font-semibold uppercase tracking-wider text-on-surface-variant">Canales activos</span>
                                        </div>
                                        <div class="text-sm font-semibold text-on-surface">
                                            {{ count($energyData['load_channels']) }} canales monitorizados
                                        </div>
                                    </div>
                                @endif
                            @endif
                        </div>

                        {{-- Área gráfica de barras --}}
                        <div class="pt-6 overflow-x-auto" x-data="{ activeDay: null }">
                            <div class="h-64 sm:h-72 min-w-[420px] w-full grid grid-cols-7 gap-2 sm:gap-4 items-end pb-10 border-b border-outline-variant/20 pt-14">
                                @foreach($energyData['days'] as $dayIdx => $day)
                                    <div class="group relative flex flex-col items-center justify-end h-full">
                                        <button type="button"
                                                @click="activeDay = activeDay === {{ $dayIdx }} ? null : {{ $dayIdx }}"
                                                @blur="if (activeDay === {{ $dayIdx }}) activeDay = null"
                                                aria-label="Detalle energía {{ $day['date'] }}: consumo {{ Figures::rounded($day['consumed_wh']) }} Wh"
                                                class="w-full h-full flex items-end justify-center gap-1 sm:gap-1.5 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary rounded-t">
                                            {{-- Tooltip flotante interactivo --}}
                                            <span :class="activeDay === {{ $dayIdx }} ? 'opacity-100' : 'opacity-0 group-hover:opacity-100'"
                                                  class="absolute -top-14 z-20 transition-opacity pointer-events-none bg-surface-container-highest text-on-surface text-xs font-semibold py-1.5 px-2.5 rounded-lg shadow-xl border border-outline-variant/30 whitespace-nowrap text-center">
                                                <span class="block text-on-surface-variant font-normal">{{ $day['date'] }}</span>
                                                @if($energyData['has_generator'])
                                                    <span class="block text-on-warning-container">Gen: {{ Figures::rounded($day['generated_wh']) }} Wh</span>
                                                @endif
                                                @if(count($day['channels']) > 1)
                                                    @foreach($day['channels'] as $ch)
                                                        <span class="block {{ $ch['color_text'] }}">{{ $ch['name'] }}: {{ Figures::rounded($ch['wh']) }} Wh</span>
                                                    @endforeach
                                                @endif
                                                <span class="block text-on-surface font-bold">Consumo: {{ Figures::rounded($day['consumed_wh']) }} Wh</span>
                                            </span>

                                            {{-- Barra Generación si aplica --}}
                                            @if($energyData['has_generator'])
                                                <span style="height: {{ $day['generated_pct'] }}%"
                                                      class="block w-full max-w-[18px] sm:max-w-[24px] bg-on-warning-container rounded-t-sm transition-all duration-300 hover:brightness-110"
                                                      title="Generado: {{ Figures::rounded($day['generated_wh']) }} Wh">
                                                </span>
                                            @endif

                                            {{-- Barras de consumo --}}
                                            @if(count($day['channels']) > 1)
                                                @foreach($day['channels'] as $ch)
                                                    <span style="height: {{ $ch['pct'] }}%"
                                                          class="block w-full max-w-[14px] sm:max-w-[18px] {{ $ch['color_bar'] }} rounded-t-sm transition-all duration-300 hover:brightness-110"
                                                          title="{{ $ch['name'] }}: {{ Figures::rounded($ch['wh']) }} Wh">
                                                    </span>
                                                @endforeach
                                            @else
                                                <span style="height: {{ $day['consumed_pct'] }}%"
                                                      class="block w-full max-w-[24px] sm:max-w-[32px] bg-primary-container rounded-t-sm transition-all duration-300 hover:brightness-110"
                                                      title="Consumo: {{ Figures::rounded($day['consumed_wh']) }} Wh">
                                                </span>
                                            @endif
                                        </button>

                                        {{-- Etiquetas del eje inferior --}}
                                        <div class="absolute -bottom-9 text-center w-full">
                                            <span class="block text-xs font-semibold text-on-surface leading-tight">{{ $day['label'] }}</span>
                                            <span class="block text-xs text-on-surface-variant font-mono leading-tight">{{ Figures::rounded($day['consumed_wh']) }} Wh</span>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                    </div>
                </section>
            @endif

            {{-- 4. Componentes y Sensores acoplados --}}
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
                        <span class="material-symbols-outlined text-4xl text-outline-variant mb-2 block" aria-hidden="true">extension_off</span>
                        <p class="text-sm">Este dispositivo no tiene sensores ni componentes adicionales registrados.</p>
                    </div>
                @endif
            </section>

            {{-- 5. Galerías de Imágenes con Visor Zoom Interactivo --}}
            @if(!empty($device->galleries))
                <section x-data="hardwareGalleryLightbox()"
                         @keydown.window.escape="if(isOpen) close()"
                         @keydown.window.arrow-right="if(isOpen) next()"
                         @keydown.window.arrow-left="if(isOpen) prev()"
                         @keydown.window.equal="if(isOpen) zoomIn()"
                         @keydown.window.plus="if(isOpen) zoomIn()"
                         @keydown.window.minus="if(isOpen) zoomOut()"
                         @keydown.window.digit0="if(isOpen) resetZoom()"
                         class="space-y-6">

                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <div>
                            <h2 class="text-2xl font-bold text-on-surface">Galerías de imágenes</h2>
                            <p class="text-xs text-on-surface-variant">
                                Fotografía de estudio e imágenes de detalle en alta resolución con zoom interactivo.
                            </p>
                        </div>
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-surface-container border border-outline-variant/20 text-on-surface-variant self-start sm:self-auto">
                            <span class="material-symbols-outlined text-sm text-primary-container" aria-hidden="true">photo_library</span>
                            {{ count($device->galleries) }} {{ count($device->galleries) === 1 ? 'galería' : 'galerías' }}
                        </span>
                    </div>

                    @foreach($device->galleries as $gIdx => $gallery)
                        <div class="bg-surface-container-lowest p-6 rounded-xl shadow border border-outline-variant/15 space-y-5">
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-outline-variant/15 pb-4">
                                <div>
                                    <h3 class="font-bold text-lg text-on-surface flex items-center gap-2">
                                        {{ $gallery['name'] }}
                                    </h3>
                                    @if(filled($gallery['description']))
                                        <p class="text-xs text-on-surface-variant mt-1 leading-relaxed">
                                            {{ $gallery['description'] }}
                                        </p>
                                    @endif
                                </div>
                                <div class="flex items-center gap-2 self-start sm:self-auto">
                                    <span class="bg-surface-container px-2.5 py-0.5 rounded-full text-xs text-on-surface-variant font-medium">
                                        {{ count($gallery['images']) }} {{ count($gallery['images']) === 1 ? 'foto' : 'fotos' }}
                                    </span>
                                    <span class="bg-surface-container px-2.5 py-0.5 rounded-full text-xs text-on-surface-variant font-medium">
                                        {{ $gallery['aspect_ratio'] }}
                                    </span>
                                </div>
                            </div>

                            {{-- Grid de fotografías --}}
                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                                @foreach($gallery['images'] as $iIdx => $img)
                                    <figure class="flex flex-col">
                                        <button type="button"
                                                @click="open({{ $gIdx }}, {{ $iIdx }})"
                                                aria-label="Ampliar fotografía {{ $img['caption'] ?: ($img['title'] ?: '#'.($iIdx + 1)) }}"
                                                class="group w-full text-left aspect-video relative rounded-xl overflow-hidden bg-surface-container border border-outline-variant/20 shadow-sm transition-all duration-300 hover:shadow-lg hover:border-primary-container/40 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary cursor-pointer">
                                            <img src="{{ $img['thumbnail_url'] }}"
                                                 alt="{{ $img['alt'] }}"
                                                 loading="lazy"
                                                 decoding="async"
                                                 class="w-full h-full object-cover transition-transform duration-500 ease-out group-hover:scale-105">

                                            {{-- Overlay en hover --}}
                                            <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/20 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex flex-col justify-between p-3.5">
                                                <div class="flex justify-end">
                                                    <span class="w-8 h-8 rounded-full bg-black/60 backdrop-blur-md border border-white/20 flex items-center justify-center text-white shadow">
                                                        <span class="material-symbols-outlined text-base" aria-hidden="true">zoom_in</span>
                                                    </span>
                                                </div>
                                                <span class="text-xs font-semibold text-white truncate drop-shadow">
                                                    {{ $img['caption'] ?: $img['title'] }}
                                                </span>
                                            </div>
                                        </button>

                                        @if(filled($img['caption']))
                                            <figcaption class="mt-2 text-xs text-on-surface-variant font-medium line-clamp-2 leading-snug"
                                                        title="{{ $img['caption'] }}">
                                                {{ $img['caption'] }}
                                            </figcaption>
                                        @endif
                                    </figure>
                                @endforeach
                            </div>
                        </div>
                    @endforeach

                    {{-- Visor Lightbox con Zoom (Teleport al body) --}}
                    <template x-teleport="body">
                        <div x-ref="lightboxDialog"
                             x-show="isOpen"
                             x-cloak
                             @keydown.window="handleKeydown($event)"
                             role="dialog"
                             aria-modal="true"
                             aria-label="Visor de galería de imágenes"
                             x-transition:enter="transition ease-out duration-200"
                             x-transition:enter-start="opacity-0"
                             x-transition:enter-end="opacity-100"
                             x-transition:leave="transition ease-in duration-150"
                             x-transition:leave-start="opacity-100"
                             x-transition:leave-end="opacity-0"
                             class="fixed inset-0 z-50 bg-black/95 backdrop-blur-md flex flex-col justify-between select-none">

                            {{-- Barra Superior --}}
                            <div class="flex items-center justify-between px-4 py-3 bg-black/50 border-b border-white/10 text-white z-20">
                                <div class="flex items-center gap-3 min-w-0">
                                    <span class="font-bold text-sm truncate" x-text="currentGallery?.name"></span>
                                    <span class="text-xs bg-white/20 px-2 py-0.5 rounded-full font-mono shrink-0"
                                          x-text="(activeImageIdx + 1) + ' / ' + totalImages"></span>
                                    <span class="text-xs text-white/70 hidden md:inline truncate"
                                          x-text="currentImage?.caption || currentImage?.title"></span>
                                </div>

                                {{-- Controles de Zoom y Acciones --}}
                                <div class="flex items-center gap-1.5 shrink-0">
                                    {{-- Nivel de zoom --}}
                                    <span class="text-xs font-mono px-2 py-1 bg-white/10 rounded-md text-white/80"
                                          x-text="Math.round(scale * 100) + '%'"></span>

                                    {{-- Alejar --}}
                                    <button type="button"
                                            @click="zoomOut()"
                                            :disabled="scale <= 1.0"
                                            aria-label="Alejar imagen"
                                            class="w-10 h-10 rounded-lg flex items-center justify-center hover:bg-white/20 disabled:opacity-30 disabled:hover:bg-transparent transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white"
                                            title="Alejar (-)">
                                        <span class="material-symbols-outlined text-xl" aria-hidden="true">zoom_out</span>
                                    </button>

                                    {{-- Acercar --}}
                                    <button type="button"
                                            @click="zoomIn()"
                                            :disabled="scale >= 3.0"
                                            aria-label="Acercar imagen"
                                            class="w-10 h-10 rounded-lg flex items-center justify-center hover:bg-white/20 disabled:opacity-30 disabled:hover:bg-transparent transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white"
                                            title="Acercar (+)">
                                        <span class="material-symbols-outlined text-xl" aria-hidden="true">zoom_in</span>
                                    </button>

                                    {{-- Reset Zoom --}}
                                    <button type="button"
                                            @click="resetZoom()"
                                            :disabled="scale === 1.0 && panX === 0 && panY === 0"
                                            aria-label="Restablecer zoom"
                                            class="w-10 h-10 rounded-lg flex items-center justify-center hover:bg-white/20 disabled:opacity-30 disabled:hover:bg-transparent transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white"
                                            title="Restablecer zoom (0)">
                                        <span class="material-symbols-outlined text-xl" aria-hidden="true">restart_alt</span>
                                    </button>

                                    <div class="h-5 w-px bg-white/20 mx-1"></div>

                                    {{-- Cerrar --}}
                                    <button type="button"
                                            x-ref="closeButton"
                                            @click="close()"
                                            aria-label="Cerrar visor de imágenes"
                                            class="w-10 h-10 rounded-lg flex items-center justify-center hover:bg-white/20 transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white"
                                            title="Cerrar (Esc)">
                                        <span class="material-symbols-outlined text-2xl" aria-hidden="true">close</span>
                                    </button>
                                </div>
                            </div>

                            {{-- Área Central del Visor --}}
                            <div class="relative flex-1 flex items-center justify-center overflow-hidden p-2 sm:p-4"
                                 @wheel.prevent="handleWheel($event)">

                                {{-- Botón Anterior (Lateral izquierdo) --}}
                                <button type="button"
                                        x-show="totalImages > 1"
                                        @click.stop="prev()"
                                        aria-label="Fotografía anterior"
                                        class="absolute z-40 w-14 h-14 sm:w-16 sm:h-16 rounded-full bg-black/75 hover:bg-black/95 active:scale-90 text-white border-2 border-white/30 backdrop-blur-md shadow-2xl flex items-center justify-center cursor-pointer transition-all duration-200 hover:scale-110 group focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white"
                                        style="top: 50%; transform: translateY(-50%); left: clamp(0.75rem, 2.5vw, 2.5rem);"
                                        title="Foto anterior (←)">
                                    <span class="material-symbols-outlined text-4xl sm:text-5xl text-white group-hover:scale-110 transition-transform" aria-hidden="true">chevron_left</span>
                                </button>

                                {{-- Contenedor de la Imagen con Zoom y Paneo --}}
                                <div class="w-full h-full flex items-center justify-center overflow-hidden relative"
                                     @mousedown="startDrag($event)"
                                     @mousemove.window="onDrag($event)"
                                     @mouseup.window="endDrag()"
                                     @touchstart.passive="startDrag($event)"
                                     @touchmove.window.passive="onDrag($event)"
                                     @touchend.window="endDrag()">
                                    <p x-show="loadingImage" class="absolute text-white/80 text-sm" role="status" aria-live="polite">Cargando imagen…</p>
                                    <p x-show="imageError" class="absolute text-error-container bg-black/80 px-4 py-2 rounded-lg text-sm" role="alert">No se pudo cargar la imagen.</p>
                                    <img :src="currentImage?.url"
                                         :alt="currentImage?.alt || 'Fotografía de galería'"
                                         x-show="!imageError"
                                         x-on:load="loadingImage = false; imageError = false"
                                         x-on:error="loadingImage = false; imageError = true"
                                         @dblclick="toggleZoom()"
                                         :style="'transform: translate(' + panX + 'px, ' + panY + 'px) scale(' + scale + '); cursor: ' + (scale > 1.0 ? (isDragging ? 'grabbing' : 'grab') : 'zoom-in') + '; transition: ' + (isDragging ? 'none' : 'transform 0.2s ease-out')"
                                         class="max-w-full max-h-full object-contain select-none shadow-2xl rounded-sm"
                                         draggable="false">
                                </div>

                                {{-- Botón Siguiente (Lateral derecho) --}}
                                <button type="button"
                                        x-show="totalImages > 1"
                                        @click.stop="next()"
                                        aria-label="Fotografía siguiente"
                                        class="absolute z-40 w-14 h-14 sm:w-16 sm:h-16 rounded-full bg-black/75 hover:bg-black/95 active:scale-90 text-white border-2 border-white/30 backdrop-blur-md shadow-2xl flex items-center justify-center cursor-pointer transition-all duration-200 hover:scale-110 group focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white"
                                        style="top: 50%; transform: translateY(-50%); right: clamp(0.75rem, 2.5vw, 2.5rem);"
                                        title="Foto siguiente (→)">
                                    <span class="material-symbols-outlined text-4xl sm:text-5xl text-white group-hover:scale-110 transition-transform" aria-hidden="true">chevron_right</span>
                                </button>
                            </div>

                            {{-- Barra Inferior: Pie de foto y Carrusel de Miniaturas --}}
                            <div class="px-4 py-3 bg-black/60 border-t border-white/10 text-white z-20 space-y-2">
                                <div class="text-center" x-show="currentImage?.caption">
                                    <p class="text-xs sm:text-sm text-white/90 font-medium max-w-3xl mx-auto drop-shadow"
                                       x-text="currentImage?.caption"></p>
                                </div>

                                {{-- Tira de miniaturas --}}
                                <div x-show="totalImages > 1" class="flex items-center justify-center gap-2 overflow-x-auto py-1">
                                    <template x-for="(img, idx) in currentImages" :key="img.id || idx">
                                        <button type="button"
                                                @click="selectImage(idx)"
                                                :class="idx === activeImageIdx
                                                    ? 'ring-2 ring-primary-container scale-105 opacity-100'
                                                    : 'opacity-50 hover:opacity-100 border border-white/20'"
                                                class="w-16 sm:w-20 aspect-video rounded overflow-hidden shrink-0 transition-all duration-200">
                                            <img :src="img.thumbnail_url"
                                                 :alt="img.alt"
                                                 class="w-full h-full object-cover">
                                        </button>
                                    </template>
                                </div>
                            </div>

                        </div>
                    </template>

                    @push('scripts')
                    <script>
                        function hardwareGalleryLightbox() {
                            return {
                                isOpen: false,
                                loadingImage: false,
                                imageError: false,
                                previousFocus: null,
                                galleries: @json($device->galleries),
                                activeGalleryIdx: 0,
                                activeImageIdx: 0,
                                scale: 1,
                                panX: 0,
                                panY: 0,
                                isDragging: false,
                                startX: 0,
                                startY: 0,

                                get currentGallery() {
                                    return this.galleries[this.activeGalleryIdx] || null;
                                },
                                get currentImages() {
                                    return this.currentGallery?.images || [];
                                },
                                get currentImage() {
                                    return this.currentImages[this.activeImageIdx] || null;
                                },
                                get totalImages() {
                                    return this.currentImages.length;
                                },

                                open(gIdx, iIdx) {
                                    this.previousFocus = document.activeElement;
                                    this.activeGalleryIdx = gIdx;
                                    this.activeImageIdx = iIdx;
                                    this.loadingImage = true;
                                    this.imageError = false;
                                    this.resetZoom();
                                    this.isOpen = true;
                                    document.body.classList.add('overflow-hidden');
                                    this.$nextTick(() => this.$refs.closeButton?.focus());
                                },
                                close() {
                                    this.isOpen = false;
                                    this.resetZoom();
                                    document.body.classList.remove('overflow-hidden');
                                    this.$nextTick(() => {
                                        if (this.previousFocus && typeof this.previousFocus.focus === 'function') {
                                            this.previousFocus.focus();
                                        }
                                    });
                                },
                                handleKeydown(event) {
                                    if (!this.isOpen) return;
                                    if (event.key === 'Escape') { this.close(); return; }
                                    if (event.key === 'Tab' && this.$refs.lightboxDialog) {
                                        const focusables = [...this.$refs.lightboxDialog.querySelectorAll('button:not([disabled])')];
                                        if (focusables.length) {
                                            const first = focusables[0];
                                            const last = focusables[focusables.length - 1];
                                            if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last.focus(); }
                                            else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first.focus(); }
                                        }
                                        return;
                                    }
                                    if (this.scale > 1.0 && ['ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown'].includes(event.key)) {
                                        event.preventDefault();
                                        const step = 40;
                                        if (event.key === 'ArrowLeft') this.panX += step;
                                        if (event.key === 'ArrowRight') this.panX -= step;
                                        if (event.key === 'ArrowUp') this.panY += step;
                                        if (event.key === 'ArrowDown') this.panY -= step;
                                        return;
                                    }
                                    if (event.key === 'ArrowRight') this.next();
                                    if (event.key === 'ArrowLeft') this.prev();
                                },
                                next() {
                                    if (this.totalImages <= 1) return;
                                    this.activeImageIdx = (this.activeImageIdx + 1) % this.totalImages;
                                    this.loadingImage = true;
                                    this.imageError = false;
                                    this.resetZoom();
                                },
                                prev() {
                                    if (this.totalImages <= 1) return;
                                    this.activeImageIdx = (this.activeImageIdx - 1 + this.totalImages) % this.totalImages;
                                    this.loadingImage = true;
                                    this.imageError = false;
                                    this.resetZoom();
                                },
                                selectImage(idx) {
                                    this.activeImageIdx = idx;
                                    this.loadingImage = true;
                                    this.imageError = false;
                                    this.resetZoom();
                                },
                                zoomIn() {
                                    if (this.scale < 3.0) {
                                        this.scale = Math.min(3.0, +(this.scale + 0.5).toFixed(1));
                                    }
                                },
                                zoomOut() {
                                    if (this.scale > 1.0) {
                                        this.scale = Math.max(1.0, +(this.scale - 0.5).toFixed(1));
                                        if (this.scale === 1.0) {
                                            this.panX = 0;
                                            this.panY = 0;
                                        }
                                    }
                                },
                                toggleZoom() {
                                    if (this.scale === 1.0) {
                                        this.scale = 2.0;
                                    } else {
                                        this.resetZoom();
                                    }
                                },
                                resetZoom() {
                                    this.scale = 1.0;
                                    this.panX = 0;
                                    this.panY = 0;
                                    this.isDragging = false;
                                },
                                handleWheel(e) {
                                    if (e.deltaY < 0) {
                                        this.zoomIn();
                                    } else {
                                        this.zoomOut();
                                    }
                                },
                                startDrag(e) {
                                    if (this.scale <= 1.0) return;
                                    this.isDragging = true;
                                    const clientX = e.touches ? e.touches[0].clientX : e.clientX;
                                    const clientY = e.touches ? e.touches[0].clientY : e.clientY;
                                    this.startX = clientX - this.panX;
                                    this.startY = clientY - this.panY;
                                },
                                onDrag(e) {
                                    if (!this.isDragging) return;
                                    const clientX = e.touches ? e.touches[0].clientX : e.clientX;
                                    const clientY = e.touches ? e.touches[0].clientY : e.clientY;
                                    this.panX = clientX - this.startX;
                                    this.panY = clientY - this.startY;
                                },
                                endDrag() {
                                    this.isDragging = false;
                                },
                            };
                        }
                    </script>
                    @endpush

                </section>
            @endif

        </div>
    </div>
@endsection
