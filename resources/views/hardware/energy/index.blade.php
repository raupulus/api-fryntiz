@extends('layouts.app')

@use('App\Support\Format\Figures')

@section('title', 'Energía Solar y Consumo | Api Raupulus')
@section('description', 'Monitorización en tiempo real de generación solar fotovoltaica, estado de baterías y consumo energético registrada mediante dispositivos IoT.')
@section('keywords', 'energy, energía solar, consumo eléctrico, generación fotovoltaica, baterías, raupulus')

@section('rs-title', 'Energía Solar y Consumo | Api Raupulus')
@section('rs-sitename', 'Api Raupulus')
@section('rs-description', 'Monitorización en tiempo real de generación solar fotovoltaica y consumo energético doméstico.')
@section('rs-image', asset('images/energy/energy.webp'))
@section('rs-image-alt', 'Panel de monitorización de energía solar fotovoltaica')

@section('content')
    {{-- Hero --}}
    <x-page-hero image="images/heroes/energy.webp" min-height="min-h-[40svh]">
        <h1 class="text-4xl md:text-6xl font-bold tracking-tighter mb-4">Energy</h1>
        <p class="text-xl text-on-hero/90">Generación y consumo de energía</p>
    </x-page-hero>

    {{-- Estadísticas en tiempo real --}}
    <section class="py-12 bg-surface">
        <div class="max-w-5xl mx-auto px-4 sm:px-6">
            <h2 class="text-3xl font-bold text-on-surface mb-6 text-center">Ahora mismo</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4 mb-8">
                @foreach($currentStats as $stat)
                    <div class="bg-surface-container-lowest rounded-xl shadow-lg p-4 text-center">
                        <div class="flex justify-center mb-2">
                            <div class="w-10 h-10 bg-warning-container dark:bg-white/95 rounded-lg flex items-center justify-center shadow-sm">
                                <img src="{{ $stat['image'] }}" alt="" aria-hidden="true" class="w-6 h-6">
                            </div>
                        </div>
                        <p class="text-xs uppercase text-on-surface-variant leading-tight mb-1">{{ $stat['title'] }}</p>
                        <p class="text-2xl text-on-surface font-semibold">{{ Figures::rounded($stat['value']) }} <span class="text-sm text-on-surface-variant">{{ $stat['unit'] }}</span></p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Estadísticas de hoy --}}
    <section class="py-12 bg-surface-container-low">
        <div class="max-w-5xl mx-auto px-4 sm:px-6">
            <h2 class="text-3xl font-bold text-on-surface mb-6 text-center">Hoy</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4 mb-8">
                @foreach($todayStats as $stat)
                    <div class="bg-surface-container-lowest rounded-xl shadow-lg p-4 text-center">
                        <div class="flex justify-center mb-2">
                            <div class="w-10 h-10 bg-info-container dark:bg-white/95 rounded-lg flex items-center justify-center shadow-sm">
                                <img src="{{ $stat['image'] }}" alt="" aria-hidden="true" class="w-6 h-6">
                            </div>
                        </div>
                        <p class="text-xs uppercase text-on-surface-variant leading-tight mb-1">{{ $stat['title'] }}</p>
                        <p class="text-2xl text-on-surface font-semibold">{{ Figures::rounded($stat['value']) }} <span class="text-sm text-on-surface-variant">{{ $stat['unit'] }}</span></p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Estadísticas históricas --}}
    <section class="py-12 bg-surface">
        <div class="max-w-5xl mx-auto px-4 sm:px-6">
            <h2 class="text-3xl font-bold text-on-surface mb-6 text-center">Histórico</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4 mb-8">
                @foreach($historicalStats as $stat)
                    <div class="bg-surface-container-lowest rounded-xl shadow-lg p-4 text-center">
                        <div class="flex justify-center mb-2">
                            <div class="w-10 h-10 bg-primary-container/15 dark:bg-white/95 rounded-lg flex items-center justify-center shadow-sm">
                                <img src="{{ $stat['image'] }}" alt="" aria-hidden="true" class="w-6 h-6">
                            </div>
                        </div>
                        <p class="text-xs uppercase text-on-surface-variant leading-tight mb-1">{{ $stat['title'] }}</p>
                        <p class="text-2xl text-on-surface font-semibold">{{ Figures::rounded($stat['value']) }} <span class="text-sm text-on-surface-variant">{{ $stat['unit'] }}</span></p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Dispositivos --}}
    <section class="py-12 bg-surface-container-low">
        <div class="max-w-7xl mx-auto px-4 sm:px-6">
            <h2 class="text-3xl font-bold text-on-surface mb-6">Dispositivos</h2>
            @if($hardwareItems->count() > 0)
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach($hardwareItems as $hw)
                        @php
                            $stats = $devicesStats[$hw->id];
                            $maxBar = max($stats->generated_now, $stats->generated_today, $stats->consumed_now, $stats->consumed_today, 1);
                        @endphp
                        <div class="bg-surface-container-lowest rounded-xl shadow-lg overflow-hidden">
                            <img src="{{ $hw->urlThumbnail('normal') }}"
                                 alt="{{ $hw->name }}"
                                 loading="lazy"
                                 class="w-full h-40 object-cover">

                            <div class="p-6">
                                <h3 class="text-lg font-bold text-on-surface mb-1 break-words">
                                    @if($hw->is_public && $hw->slug)
                                        <a href="{{ route('hardware.show', $hw->slug) }}" class="hover:text-primary hover:underline underline-offset-2 transition-colors">
                                            {{ $hw->name ?? 'Dispositivo #'.$hw->id }}
                                        </a>
                                    @else
                                        {{ $hw->name ?? 'Dispositivo #'.$hw->id }}
                                    @endif
                                </h3>
                                <p class="text-xs text-on-surface-variant uppercase tracking-wide mb-3">
                                    {{ $stats->days_operating }} días operando
                                    @if($hw->software_version)
                                        · v{{ $hw->software_version }}
                                    @endif
                                </p>

                                <p class="text-on-surface-variant text-sm mb-4">{{ $hw->description ? Str::limit($hw->description, 120) : 'Sin descripción' }}</p>

                                {{-- Resumen rápido --}}
                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-2 text-center mb-5">
                                    @if($stats->has_generator)
                                        <div class="bg-surface-container-low rounded-lg py-2 px-1">
                                            <span class="material-symbols-outlined text-on-warning-container text-lg block" aria-hidden="true">solar_power</span>
                                            <span class="text-xs font-semibold text-on-surface">{{ Figures::rounded($stats->generated_historical_kwh) }} kWh</span>
                                        </div>
                                        <div class="bg-surface-container-low rounded-lg py-2 px-1">
                                            <span class="material-symbols-outlined text-primary text-lg block" aria-hidden="true">bolt</span>
                                            <span class="text-xs font-semibold text-on-surface">{{ Figures::rounded($stats->consumed_historical_kwh) }} kWh</span>
                                        </div>
                                        <div class="bg-surface-container-low rounded-lg py-2 px-1">
                                            <span class="material-symbols-outlined text-success text-lg block" aria-hidden="true">battery_charging_full</span>
                                            <span class="text-xs font-semibold text-on-surface">{{ Figures::rounded($stats->battery_percentage) }}%</span>
                                        </div>
                                    @else
                                        {{-- Sin generador: "Generado" y la batería de la instalación no dicen
                                        nada de este aparato. En su lugar, lo que reporta de sí mismo. --}}
                                        @foreach($stats->own_status_badges as $badge)
                                            <div class="bg-surface-container-low rounded-lg py-2 px-1">
                                                <span class="material-symbols-outlined {{ $badge['color'] }} text-lg block" aria-hidden="true">{{ $badge['icon'] }}</span>
                                                <span class="text-xs font-semibold text-on-surface">{{ Figures::rounded($badge['value']) }}{{ $badge['unit'] }}</span>
                                            </div>
                                        @endforeach
                                    @endif
                                </div>

                                {{-- Comparativa Ahora / Hoy --}}
                                <div class="space-y-3">
                                    @if($stats->has_generator)
                                        <div>
                                            <div class="flex justify-between text-xs text-on-surface-variant mb-1">
                                                <span>Generando ahora</span>
                                                <span class="font-semibold text-on-surface">{{ Figures::rounded($stats->generated_now) }} W</span>
                                            </div>
                                            <div class="h-1.5 rounded-full bg-surface-container-low overflow-hidden">
                                                <div class="h-full rounded-full bg-tertiary" style="width: {{ min(100, $stats->generated_now / $maxBar * 100) }}%"></div>
                                            </div>
                                        </div>
                                        <div>
                                            <div class="flex justify-between text-xs text-on-surface-variant mb-1">
                                                <span>Generado hoy</span>
                                                <span class="font-semibold text-on-surface">{{ Figures::rounded($stats->generated_today) }} Wh</span>
                                            </div>
                                            <div class="h-1.5 rounded-full bg-surface-container-low overflow-hidden">
                                                <div class="h-full rounded-full bg-tertiary/60" style="width: {{ min(100, $stats->generated_today / $maxBar * 100) }}%"></div>
                                            </div>
                                        </div>
                                    @endif

                                    {{-- Un bloque por cada consumo activo: una nevera y un router en
                                    canales distintos del mismo monitor no son la misma cifra. --}}
                                    @foreach($stats->loads as $loadChannel)
                                        @if(count($stats->loads) > 1)
                                            <p class="text-xs font-semibold text-on-surface-variant uppercase tracking-wide pt-1">{{ $loadChannel->label }}</p>
                                        @endif
                                        <div>
                                            <div class="flex justify-between text-xs text-on-surface-variant mb-1">
                                                <span>Consumiendo ahora</span>
                                                <span class="font-semibold text-on-surface">{{ Figures::rounded($loadChannel->now) }} W</span>
                                            </div>
                                            <div class="h-1.5 rounded-full bg-surface-container-low overflow-hidden">
                                                <div class="h-full rounded-full bg-primary-container" style="width: {{ min(100, $loadChannel->now / $maxBar * 100) }}%"></div>
                                            </div>
                                        </div>
                                        <div>
                                            <div class="flex justify-between text-xs text-on-surface-variant mb-1">
                                                <span>Consumido hoy</span>
                                                <span class="font-semibold text-on-surface">{{ Figures::rounded($loadChannel->today) }} Wh</span>
                                            </div>
                                            <div class="h-1.5 rounded-full bg-surface-container-low overflow-hidden">
                                                <div class="h-full rounded-full bg-primary-container/60" style="width: {{ min(100, $loadChannel->today / $maxBar * 100) }}%"></div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="bg-surface-container rounded-xl p-12 text-center">
                    <span class="material-symbols-outlined text-6xl text-on-surface-variant mb-4" aria-hidden="true">solar_power</span>
                    <p class="text-on-surface-variant text-lg">No hay dispositivos de energía registrados.</p>
                </div>
            @endif
        </div>
    </section>
@endsection
