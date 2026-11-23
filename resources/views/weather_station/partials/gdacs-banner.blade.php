{{--
    Última alerta GDACS vigente cerca de Chipiona. Espera $gdacsLatestActive (GdacsEvent|null).

    A propósito con colores sólidos y saturados (no los pasteles suaves que
    usan el resto de tarjetas de la página): es un aviso/CTA que debe
    distinguirse a simple vista, no una tarjeta informativa más.
--}}
@php
    $gdacsStyles = [
        'Green' => 'bg-success text-on-success',
        'Orange' => 'bg-warning-container text-on-warning-container',
        'Red' => 'bg-error text-on-error',
    ];

    $gdacsStyle = $gdacsLatestActive ? ($gdacsStyles[$gdacsLatestActive->alert_level->value] ?? $gdacsStyles['Green']) : null;
@endphp

<section class="pt-12 bg-surface-container-low">
    <div class="max-w-7xl mx-auto px-4 sm:px-6">
        @if($gdacsLatestActive)
            <a href="{{ route('weather_station.gdacs.index') }}"
               class="block rounded-xl p-6 shadow-lg hover:shadow-xl hover:scale-[1.01] transition-all {{ $gdacsStyle }}">
                <div class="flex items-center gap-3 mb-2">
                    <span class="material-symbols-outlined text-2xl" aria-hidden="true">public</span>
                    <h2 class="text-lg font-bold">Última alerta GDACS cerca de Chipiona</h2>
                </div>
                <p class="text-sm">
                    <strong>{{ $gdacsLatestActive->event_type->label() }}</strong> — {{ $gdacsLatestActive->name ?? $gdacsLatestActive->event_type->label() }}
                    · Nivel {{ $gdacsLatestActive->alert_level->label() }}
                    · A {{ number_format($gdacsLatestActive->distance_km, 1) }} km de Chipiona
                </p>
                <span class="text-xs font-bold uppercase tracking-widest">Ver todas las alertas →</span>
                <span class="text-xs opacity-85 block mt-2">Fuente: {{ config('gdacs.attribution') }}</span>
            </a>
        @else
            <a href="{{ route('weather_station.gdacs.index') }}"
               class="hero-gradient rounded-xl p-5 shadow-lg text-on-primary hover:shadow-xl hover:scale-[1.01] transition-all flex items-center gap-3">
                <span class="material-symbols-outlined text-2xl" aria-hidden="true">public</span>
                <span class="font-bold text-sm">No hay alertas GDACS vigentes actualmente — ver anteriores →</span>
            </a>
        @endif
    </div>
</section>
