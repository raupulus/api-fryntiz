{{-- Tarjeta horizontal de un evento GDACS. Espera $event (GdacsEvent). --}}
@php
    $styles = [
        'Green' => ['container' => 'bg-emerald-50 dark:bg-emerald-950/30 text-emerald-950 dark:text-emerald-100', 'icon' => 'text-emerald-600 dark:text-emerald-400'],
        'Orange' => ['container' => 'bg-orange-50 dark:bg-orange-950/30 text-orange-950 dark:text-orange-100', 'icon' => 'text-orange-600 dark:text-orange-400'],
        'Red' => ['container' => 'bg-error-container/40 dark:bg-error-container/20 text-on-surface', 'icon' => 'text-error'],
    ];

    $style = $styles[$event->alert_level->value] ?? $styles['Green'];

    $displayTimezone = config('app.display_timezone', 'Europe/Madrid');
@endphp

<div class="w-full rounded-xl p-6 shadow-lg {{ $style['container'] }}">
    <div class="flex flex-wrap items-center justify-between gap-3 mb-3">
        <div class="flex items-center gap-3">
            <span class="material-symbols-outlined text-2xl {{ $style['icon'] }}">public</span>
            <div>
                <h3 class="text-lg font-bold">{{ $event->name ?? $event->event_type->label() }}</h3>
                <span class="text-xs uppercase tracking-widest opacity-80">{{ $event->event_type->label() }}</span>
            </div>
        </div>
        <span class="text-xs font-bold uppercase tracking-widest px-3 py-1 rounded-full bg-surface-container-lowest/60">
            {{ $event->alert_level->label() }}{{ $event->is_current ? ' · Vigente' : ' · Cerrada' }}
        </span>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-1 text-sm">
        <p>
            <strong>Distancia a Chipiona:</strong> {{ number_format($event->distance_km, 1) }} km
        </p>
        <p>
            <strong>Inicio:</strong> {{ $event->from_date->timezone($displayTimezone)->translatedFormat('d/m/Y H:i') }} (hora de Madrid)
        </p>
        @if($event->to_date)
            <p>
                <strong>Fin / última fecha conocida:</strong> {{ $event->to_date->timezone($displayTimezone)->translatedFormat('d/m/Y H:i') }} (hora de Madrid)
            </p>
        @endif
        <p>
            <strong>Última actualización:</strong> {{ $event->last_modified_at->timezone($displayTimezone)->translatedFormat('d/m/Y H:i') }} (hora de Madrid)
        </p>
        @if($event->severity_text)
            <p class="md:col-span-2">
                <strong>Gravedad:</strong> {{ $event->severity_text }}
                @if($event->severity_value !== null)
                    ({{ $event->severity_value }}{{ $event->severity_unit ? ' '.$event->severity_unit : '' }})
                @endif
            </p>
        @endif
        @if($event->affected_population !== null)
            <p>
                <strong>Población afectada estimada:</strong> {{ number_format($event->affected_population) }}
            </p>
        @endif
    </div>

    @if($event->report_url)
        <a href="{{ $event->report_url }}" target="_blank" rel="noopener"
           class="inline-flex items-center gap-1 mt-4 text-xs font-bold uppercase tracking-widest underline underline-offset-2">
            Ver informe completo
            <span class="material-symbols-outlined text-sm">open_in_new</span>
        </a>
    @endif
</div>
