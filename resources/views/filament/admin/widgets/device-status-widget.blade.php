<x-filament-widgets::widget>
    <x-filament::section class="dev-status__section">
        <x-slot name="heading">
            <div class="dev-status__heading">
                <x-filament::icon icon="heroicon-o-cpu-chip" class="dev-status__heading-icon" />
                <span>Estado de dispositivos</span>
            </div>
        </x-slot>

        @php
            $allowedTones = ['orange', 'amber', 'sky', 'indigo', 'emerald', 'green'];
        @endphp

        @if ($devices->isEmpty())
            <p class="dev-status__empty">
                Ningún dispositivo ha reportado métricas de estado todavía.
            </p>
        @else
            <div class="dev-status__list">
                @foreach ($devices as $device)
                    <div class="dev-status__row">
                        <div class="dev-status__info">
                            <span class="dev-status__name">{{ $device['name'] }}</span>
                            @if ($device['last_seen_at'])
                                <span class="dev-status__seen">Última vez: {{ $device['last_seen_at']->diffForHumans() }}</span>
                            @endif
                        </div>

                        <div class="dev-status__metrics">
                            @foreach ($device['metrics'] as $metric)
                                @php
                                    $tone = in_array($metric['color'] ?? '', $allowedTones, true) ? $metric['color'] : 'sky';
                                @endphp
                                <div class="dev-status__chip dev-status__chip--{{ $tone }}" title="{{ $metric['label'] }}">
                                    <x-filament::icon :icon="$metric['icon']" class="dev-status__chip-icon" />
                                    <span class="dev-status__chip-value">{{ $metric['value'] }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
