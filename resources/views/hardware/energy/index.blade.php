@extends('layouts.app')

@section('title', 'Energy | Api Raupulus')
@section('description', 'Monitorización de generación y consumo de energía solar')
@section('keywords', 'energy, energía solar, consumo, generación, Raúl Caro Pastorino, raupulus')

@section('rs-title', 'Energy - Generación y consumo de energía')
@section('rs-sitename', 'Api Raupulus')
@section('rs-description', 'Monitorización de generación y consumo de energía solar')

@section('content')
    {{-- Hero --}}
    <section class="hero-gradient min-h-[40vh] flex items-center pt-20">
        <div class="max-w-7xl mx-auto px-6 text-white">
            <h1 class="text-4xl md:text-6xl font-bold tracking-tighter mb-4">Energy</h1>
            <p class="text-xl text-white/80">Generación y consumo de energía</p>
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
                            <div class="w-10 h-10 bg-amber-100 dark:bg-amber-900/30 rounded-lg flex items-center justify-center">
                                <span class="material-symbols-outlined text-amber-600 dark:text-amber-400">{{ $stat['icon'] ?? 'bolt' }}</span>
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
                            <div class="w-10 h-10 bg-emerald-100 dark:bg-emerald-900/30 rounded-lg flex items-center justify-center">
                                <span class="material-symbols-outlined text-emerald-600 dark:text-emerald-400">{{ $stat['icon'] ?? 'electric_meter' }}</span>
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
                            <div class="w-10 h-10 bg-indigo-100 dark:bg-indigo-900/30 rounded-lg flex items-center justify-center">
                                <span class="material-symbols-outlined text-indigo-600 dark:text-indigo-400">{{ $stat['icon'] ?? 'energy_savings_leaf' }}</span>
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
                        <div class="bg-surface-container-lowest rounded-xl p-6 shadow-lg">
                            <div class="flex items-center gap-3 mb-4">
                                <div class="w-10 h-10 bg-amber-100 dark:bg-amber-900/30 rounded-lg flex items-center justify-center">
                                    <span class="material-symbols-outlined text-amber-600 dark:text-amber-400">solar_power</span>
                                </div>
                                <h3 class="text-lg font-bold text-on-surface">{{ $hw->name ?? 'Dispositivo #'.$hw->id }}</h3>
                            </div>
                            <p class="text-on-surface-variant text-sm">{{ $hw->description ?? 'Sin descripción' }}</p>
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
