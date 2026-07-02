@extends('layouts.app')

@section('title', 'Energy | Api Raupulus')
@section('description', 'Monitorización de generación y consumo de energía solar')
@section('keywords', 'energy, energía solar, consumo, generación, Raúl Caro Pastorino, raupulus')

@section('rs-title', 'Energy - Generación y consumo de energía')
@section('rs-sitename', 'Api Raupulus')
@section('rs-description', 'Monitorización de generación y consumo de energía solar')
@section('rs-image', asset('images/energy/energy.png'))

@section('content')
    {{-- Hero --}}
    <section class="hero-gradient min-h-[40vh] flex items-center pt-20">
        <div class="max-w-7xl mx-auto px-6 text-white">
            <h1 class="text-4xl md:text-6xl font-bold tracking-tighter mb-4">Energy</h1>
            <p class="text-xl text-white/80">Generación y consumo de energía</p>
        </div>
    </section>

    {{-- Imagen de portada --}}
    <section class="pt-8 bg-surface">
        <div class="max-w-5xl mx-auto px-6">
            <div class="rounded-xl overflow-hidden shadow-lg h-56 md:h-72">
                <img src="{{ asset('images/energy/energy.png') }}"
                     alt="Energy"
                     class="w-full h-full object-cover">
            </div>
        </div>
    </section>

    {{-- Estadísticas en tiempo real --}}
    <section class="py-12 bg-surface">
        <div class="max-w-5xl mx-auto px-6">
            <h2 class="text-3xl font-bold text-on-surface mb-6 text-center">Ahora mismo</h2>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
                @foreach($currentStats as $stat)
                    <div class="bg-surface-container-lowest rounded-xl shadow-lg p-4 text-center">
                        <div class="flex justify-center mb-2">
                            <div class="w-10 h-10 bg-amber-100 dark:bg-amber-800/60 rounded-lg flex items-center justify-center">
                                <img src="{{ $stat['image'] }}" alt="{{ $stat['title'] }}" class="w-6 h-6">
                            </div>
                        </div>
                        <h4 class="text-xs uppercase text-on-surface-variant leading-tight mb-1">{{ $stat['title'] }}</h4>
                        <h3 class="text-2xl text-on-surface font-semibold">{{ $stat['value'] }} <span class="text-sm text-on-surface-variant">{{ $stat['unit'] }}</span></h3>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Estadísticas de hoy --}}
    <section class="py-12 bg-surface-container-low">
        <div class="max-w-5xl mx-auto px-6">
            <h2 class="text-3xl font-bold text-on-surface mb-6 text-center">Hoy</h2>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
                @foreach($todayStats as $stat)
                    <div class="bg-surface-container-lowest rounded-xl shadow-lg p-4 text-center">
                        <div class="flex justify-center mb-2">
                            <div class="w-10 h-10 bg-emerald-100 dark:bg-emerald-800/60 rounded-lg flex items-center justify-center">
                                <img src="{{ $stat['image'] }}" alt="{{ $stat['title'] }}" class="w-6 h-6">
                            </div>
                        </div>
                        <h4 class="text-xs uppercase text-on-surface-variant leading-tight mb-1">{{ $stat['title'] }}</h4>
                        <h3 class="text-2xl text-on-surface font-semibold">{{ $stat['value'] }} <span class="text-sm text-on-surface-variant">{{ $stat['unit'] }}</span></h3>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Estadísticas históricas --}}
    <section class="py-12 bg-surface">
        <div class="max-w-5xl mx-auto px-6">
            <h2 class="text-3xl font-bold text-on-surface mb-6 text-center">Histórico</h2>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
                @foreach($historicalStats as $stat)
                    <div class="bg-surface-container-lowest rounded-xl shadow-lg p-4 text-center">
                        <div class="flex justify-center mb-2">
                            <div class="w-10 h-10 bg-indigo-100 dark:bg-indigo-800/60 rounded-lg flex items-center justify-center">
                                <img src="{{ $stat['image'] }}" alt="{{ $stat['title'] }}" class="w-6 h-6">
                            </div>
                        </div>
                        <h4 class="text-xs uppercase text-on-surface-variant leading-tight mb-1">{{ $stat['title'] }}</h4>
                        <h3 class="text-2xl text-on-surface font-semibold">{{ $stat['value'] }} <span class="text-sm text-on-surface-variant">{{ $stat['unit'] }}</span></h3>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Dispositivos --}}
    <section class="py-12 bg-surface-container-low">
        <div class="max-w-7xl mx-auto px-6">
            <h2 class="text-3xl font-bold text-on-surface mb-6">Dispositivos</h2>
            @if($hardwares->count() > 0)
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach($hardwares as $hw)
                        @php
                            $stats = $devicesStats[$hw->id];
                            $maxBar = max($stats->generated_now, $stats->generated_today, $stats->consumed_now, $stats->consumed_today, 1);
                        @endphp
                        <div class="bg-surface-container-lowest rounded-xl shadow-lg overflow-hidden">
                            <img src="{{ $hw->urlThumbnail('normal') }}"
                                 alt="{{ $hw->name }}"
                                 class="w-full h-40 object-cover">

                            <div class="p-6">
                                <h3 class="text-lg font-bold text-on-surface mb-1">{{ $hw->name ?? 'Dispositivo #'.$hw->id }}</h3>
                                <p class="text-xs text-on-surface-variant uppercase tracking-wide mb-3">
                                    {{ $stats->days_operating }} días operando
                                    @if($hw->software_version)
                                        · v{{ $hw->software_version }}
                                    @endif
                                </p>

                                <p class="text-on-surface-variant text-sm mb-4">{{ $hw->description ? Str::limit($hw->description, 120) : 'Sin descripción' }}</p>

                                {{-- Resumen rápido --}}
                                <div class="grid grid-cols-3 gap-2 text-center mb-5">
                                    <div class="bg-surface-container-low rounded-lg py-2">
                                        <span class="material-symbols-outlined text-amber-600 dark:text-amber-400 text-lg block">solar_power</span>
                                        <span class="text-xs font-semibold text-on-surface">{{ $stats->generated_historical_kw }} kW</span>
                                    </div>
                                    <div class="bg-surface-container-low rounded-lg py-2">
                                        <span class="material-symbols-outlined text-sky-600 dark:text-sky-400 text-lg block">bolt</span>
                                        <span class="text-xs font-semibold text-on-surface">{{ $stats->consumed_historical_kw }} kW</span>
                                    </div>
                                    <div class="bg-surface-container-low rounded-lg py-2">
                                        <span class="material-symbols-outlined text-emerald-600 dark:text-emerald-400 text-lg block">battery_charging_full</span>
                                        <span class="text-xs font-semibold text-on-surface">{{ $stats->battery_percentage }}%</span>
                                    </div>
                                </div>

                                {{-- Comparativa Ahora / Hoy --}}
                                <div class="space-y-3">
                                    <div>
                                        <div class="flex justify-between text-xs text-on-surface-variant mb-1">
                                            <span>Generando ahora</span>
                                            <span class="font-semibold text-on-surface">{{ $stats->generated_now }} W</span>
                                        </div>
                                        <div class="h-1.5 rounded-full bg-surface-container-low overflow-hidden">
                                            <div class="h-full rounded-full bg-amber-500" style="width: {{ min(100, $stats->generated_now / $maxBar * 100) }}%"></div>
                                        </div>
                                    </div>
                                    <div>
                                        <div class="flex justify-between text-xs text-on-surface-variant mb-1">
                                            <span>Generado hoy</span>
                                            <span class="font-semibold text-on-surface">{{ $stats->generated_today }} W</span>
                                        </div>
                                        <div class="h-1.5 rounded-full bg-surface-container-low overflow-hidden">
                                            <div class="h-full rounded-full bg-amber-500/60" style="width: {{ min(100, $stats->generated_today / $maxBar * 100) }}%"></div>
                                        </div>
                                    </div>
                                    <div>
                                        <div class="flex justify-between text-xs text-on-surface-variant mb-1">
                                            <span>Consumiendo ahora</span>
                                            <span class="font-semibold text-on-surface">{{ $stats->consumed_now }} W</span>
                                        </div>
                                        <div class="h-1.5 rounded-full bg-surface-container-low overflow-hidden">
                                            <div class="h-full rounded-full bg-sky-500" style="width: {{ min(100, $stats->consumed_now / $maxBar * 100) }}%"></div>
                                        </div>
                                    </div>
                                    <div>
                                        <div class="flex justify-between text-xs text-on-surface-variant mb-1">
                                            <span>Consumido hoy</span>
                                            <span class="font-semibold text-on-surface">{{ $stats->consumed_today }} W</span>
                                        </div>
                                        <div class="h-1.5 rounded-full bg-surface-container-low overflow-hidden">
                                            <div class="h-full rounded-full bg-sky-500/60" style="width: {{ min(100, $stats->consumed_today / $maxBar * 100) }}%"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="bg-surface-container rounded-xl p-12 text-center">
                    <span class="material-symbols-outlined text-6xl text-on-surface-variant mb-4">solar_power</span>
                    <p class="text-on-surface-variant text-lg">No hay dispositivos de energía registrados.</p>
                </div>
            @endif
        </div>
    </section>
@endsection
