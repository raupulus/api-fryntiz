{{--
    Última alerta GDACS vigente cerca de Chipiona. Espera $gdacsLatestActive (GdacsEvent|null).

    A propósito con colores sólidos y saturados (no los pasteles suaves que
    usan el resto de tarjetas de la página): es un aviso/CTA que debe
    distinguirse a simple vista, no una tarjeta informativa más.
--}}
@php
    $gdacsStyles = [
        'Green' => 'bg-emerald-600 text-white',
        'Orange' => 'bg-orange-600 text-white',
        'Red' => 'bg-red-600 text-white',
    ];

    $gdacsStyle = $gdacsLatestActive ? ($gdacsStyles[$gdacsLatestActive->alert_level->value] ?? $gdacsStyles['Green']) : null;
@endphp

<section class="pt-12 bg-surface-container-low">
    <div class="max-w-7xl mx-auto px-6">
        @if($gdacsLatestActive)
            <a href="{{ route('weather_station.gdacs.index') }}"
               class="block rounded-xl p-6 shadow-lg hover:shadow-xl hover:scale-[1.01] transition-all {{ $gdacsStyle }}">
                <div class="flex items-center gap-3 mb-2">
                    <span class="material-symbols-outlined text-2xl">public</span>
                    <h3 class="text-lg font-bold">Última alerta GDACS cerca de Chipiona</h3>
                </div>
                <p class="text-sm">
                    <strong>{{ $gdacsLatestActive->event_type->label() }}</strong> — {{ $gdacsLatestActive->name ?? $gdacsLatestActive->event_type->label() }}
                    · Nivel {{ $gdacsLatestActive->alert_level->label() }}
                    · A {{ number_format($gdacsLatestActive->distance_km, 1) }} km de Chipiona
                </p>
                <span class="text-xs font-bold uppercase tracking-widest">Ver todas las alertas →</span>
                <span class="text-xs opacity-80 block mt-2">Fuente: {{ config('gdacs.attribution') }}</span>
            </a>
        @else
            <a href="{{ route('weather_station.gdacs.index') }}"
               class="hero-gradient block rounded-xl p-5 shadow-lg text-white hover:shadow-xl hover:scale-[1.01] transition-all flex items-center gap-3">
                <span class="material-symbols-outlined text-2xl">public</span>
                <span class="font-bold text-sm">No hay alertas GDACS vigentes actualmente — ver anteriores →</span>
            </a>
        @endif
    </div>
</section>
