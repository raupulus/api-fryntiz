@extends('layouts.app')

@section('title', 'Alertas GDACS | Api Raupulus')
@section('description', 'Histórico de alertas GDACS (terremotos, ciclones, inundaciones, volcanes, sequías e incendios) dentro del radio de Chipiona')
@section('keywords', 'GDACS, alertas, desastres, terremoto, ciclón, inundación, volcán, sequía, incendio, Chipiona, Api Raupulus')

@section('rs-title', 'Alertas GDACS cerca de Chipiona')
@section('rs-sitename', 'Api Raupulus')
@section('rs-description', 'Histórico de alertas GDACS dentro del radio de Chipiona')

@section('content')
    {{-- Hero --}}
    <section class="hero-gradient min-h-[30vh] flex items-center pt-20">
        <div class="max-w-7xl mx-auto px-6 text-white">
            <h1 class="text-4xl md:text-5xl font-bold tracking-tighter mb-4">Alertas GDACS</h1>
            <p class="text-lg text-white/80">
                Terremotos, ciclones, inundaciones, volcanes, sequías e incendios dentro del radio de Chipiona.
            </p>
        </div>
    </section>

    <section class="py-12 bg-surface">
        <div class="max-w-7xl mx-auto px-6">
            <div class="mb-6 flex items-center justify-between flex-wrap gap-4">
                <a href="{{ route('weather_station.index') }}"
                   class="inline-flex items-center gap-2 px-4 py-2 bg-surface-container rounded-lg text-on-surface hover:bg-surface-container-high transition-colors">
                    <span class="material-symbols-outlined text-sm">arrow_back</span>
                    Volver a Weather Station
                </a>
                <span class="text-xs text-on-surface-variant">Fuente: {{ config('gdacs.attribution') }}</span>
            </div>

            @if($events->count() > 0)
                <div class="flex flex-col gap-6">
                    @foreach($events as $event)
                        @include('gdacs.partials.event-card', ['event' => $event])
                    @endforeach
                </div>

                <div class="mt-8">
                    {{ $events->links() }}
                </div>
            @else
                <div class="text-center py-12 bg-surface-container-lowest rounded-xl shadow-lg">
                    <span class="material-symbols-outlined text-6xl text-on-surface-variant mb-4">public</span>
                    <p class="text-on-surface-variant text-lg">Todavía no se ha registrado ninguna alerta GDACS.</p>
                </div>
            @endif
        </div>
    </section>
@endsection
