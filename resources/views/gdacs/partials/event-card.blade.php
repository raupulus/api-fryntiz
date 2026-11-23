{{-- Tarjeta horizontal de un evento GDACS. Espera $event (GdacsEvent). --}}
@php
    $styles = [
        'Green' => ['container' => 'bg-surface-container-lowest text-on-surface border border-success/30', 'icon' => 'text-success'],
        'Orange' => ['container' => 'bg-warning-container text-on-warning-container', 'icon' => 'text-on-warning-container'],
        'Red' => ['container' => 'bg-error-container text-on-error-container', 'icon' => 'text-on-error-container'],
    ];

    $style = $styles[$event->alert_level->value] ?? $styles['Green'];

    $displayTimezone = config('app.display_timezone', 'Europe/Madrid');
@endphp

<div class="w-full rounded-xl p-6 shadow-lg {{ $style['container'] }}">
    <div class="flex flex-wrap items-center justify-between gap-3 mb-3">
        <div class="flex items-center gap-3">
            <span class="material-symbols-outlined text-2xl {{ $style['icon'] }}" aria-hidden="true">public</span>
            <div>
                <h2 class="text-lg font-bold">{{ $event->name ?? $event->event_type->label() }}</h2>
                <span class="text-xs uppercase tracking-widest opacity-85">{{ $event->event_type->label() }}</span>
            </div>
        </div>
        <span class="text-xs font-bold uppercase tracking-widest px-3 py-1 rounded-full bg-surface-container-lowest/80 text-on-surface">
            {{ $event->alert_level->label() }}{{ $event->is_current ? ' · Vigente' : ' · Cerrada' }}
        </span>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-1 text-sm">
        <p>
            <strong>Distancia a Chipiona:</strong> {{ number_format($event->distance_km, 1) }} km
        </p>
        <p>
            <strong>Inicio:</strong> <time datetime="{{ $event->from_date->toIso8601String() }}">{{ $event->from_date->timezone($displayTimezone)->translatedFormat('d/m/Y H:i') }}</time> (hora de Madrid)
        </p>
        @if($event->to_date)
            <p>
                <strong>Fin / última fecha conocida:</strong> <time datetime="{{ $event->to_date->toIso8601String() }}">{{ $event->to_date->timezone($displayTimezone)->translatedFormat('d/m/Y H:i') }}</time> (hora de Madrid)
            </p>
        @endif
        <p>
            <strong>Última actualización:</strong> <time datetime="{{ $event->last_modified_at->toIso8601String() }}">{{ $event->last_modified_at->timezone($displayTimezone)->translatedFormat('d/m/Y H:i') }}</time> (hora de Madrid)
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

    @if($event->report_url && \App\Services\Content\ContentBlockValidator::isSafeUrl($event->report_url, false))
        <a href="{{ $event->report_url }}" target="_blank" rel="noopener noreferrer"
           class="inline-flex items-center gap-1 mt-4 text-xs font-bold uppercase tracking-widest underline underline-offset-2">
            Ver informe completo
            <span class="material-symbols-outlined text-sm" aria-hidden="true">open_in_new</span>
        </a>
    @endif
</div>
