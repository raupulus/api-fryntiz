<x-filament-widgets::widget>
    <x-filament::section class="aemet-metrics__section">
        <x-slot name="heading">
            <div class="aemet-metrics__heading">
                <x-filament::icon icon="heroicon-o-cloud" class="aemet-metrics__heading-icon" />
                <span>AEMET — Datos Principales</span>
            </div>
        </x-slot>

        @php
            $items = [
                ['label' => 'Temperatura', 'value' => number_format($temperature, 1).' °C', 'icon' => 'heroicon-o-fire', 'tone' => 'orange'],
                ['label' => 'Viento Máximo', 'value' => number_format($wind_max, 1).' m/s', 'icon' => 'heroicon-o-paper-airplane', 'tone' => 'sky'],
                ['label' => 'Dirección Viento', 'value' => $wind_direction, 'icon' => 'heroicon-o-arrow-up-right', 'tone' => 'teal'],
                ['label' => 'Radiación Solar', 'value' => number_format($solar_radiation, 1).' W/m²', 'icon' => 'heroicon-o-sun', 'tone' => 'amber'],
                ['label' => 'Humedad', 'value' => $humidity.' %', 'icon' => 'heroicon-o-beaker', 'tone' => 'blue'],
                ['label' => 'Probabilidad Lluvia', 'value' => $rain_prob.' %', 'icon' => 'heroicon-o-cloud', 'tone' => 'indigo'],
                ['label' => 'Ozono (O₃)', 'value' => number_format($ozone, 1).' µg/m³', 'icon' => 'heroicon-o-sparkles', 'tone' => 'emerald'],
                ['label' => 'Polen', 'value' => $pollen, 'icon' => 'heroicon-o-eye-slash', 'tone' => 'rose'],
            ];
        @endphp

        <div class="aemet-metrics__grid">
            @foreach ($items as $item)
                <div class="aemet-metrics__card">
                    <div class="aemet-metrics__icon-box aemet-metrics__icon-box--{{ $item['tone'] }}">
                        <x-filament::icon :icon="$item['icon']" class="aemet-metrics__icon" />
                    </div>
                    <div class="aemet-metrics__content">
                        <span class="aemet-metrics__label">{{ $item['label'] }}</span>
                        <span class="aemet-metrics__value">{{ $item['value'] }}</span>
                    </div>
                </div>
            @endforeach
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
