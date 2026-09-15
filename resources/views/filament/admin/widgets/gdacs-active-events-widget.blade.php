<x-filament-widgets::widget>
    <div class="gd-active">
        @foreach ($events as $event)
            <div class="gd-active__card gd-active__card--{{ strtolower($event->alert_level->value) }}">
                <div class="gd-active__head">
                    <span class="gd-active__type">{{ $event->event_type->label() }}</span>
                    <x-filament::badge :color="$event->alert_level->color()">
                        {{ $event->alert_level->label() }}
                    </x-filament::badge>
                </div>

                <p class="gd-active__title">{{ $event->name ?? $event->event_type->label() }}</p>

                <p class="gd-active__meta">
                    A {{ number_format($event->distance_km, 1) }} km · desde {{ $event->from_date->translatedFormat('d/m/Y') }}
                </p>

                @if ($event->severity_text)
                    <p class="gd-active__meta">{{ $event->severity_text }}</p>
                @endif
            </div>
        @endforeach
    </div>
</x-filament-widgets::widget>
