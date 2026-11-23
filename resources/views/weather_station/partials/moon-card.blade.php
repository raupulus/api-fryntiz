{{--
    Sol y luna del día. Espera $moon (array de MoonPhase::forDate()) y $sun
    (array{sunrise: ?Carbon, sunset: ?Carbon} de WeatherStationController::todaySunTimes()).
--}}
<div class="bg-surface-container-lowest rounded-xl p-6 shadow-lg">
    <div class="flex items-center justify-between mb-4">
        <div class="w-12 h-12 bg-surface-container rounded-lg flex items-center justify-center text-2xl">
            {{ $moon['emoji'] }}
        </div>
        <div class="text-right">
            <div class="text-xl font-bold text-on-surface">{{ $moon['illumination'] }}%</div>
            <div class="text-xs text-on-surface-variant">iluminada</div>
        </div>
    </div>
    <h3 class="text-lg font-bold text-on-surface mb-2">Sol y Luna</h3>
    <span class="text-on-surface-variant text-xs uppercase tracking-widest block">{{ $moon['phase'] }}</span>

    @if($sun['sunrise'] || $sun['sunset'])
        <div class="flex items-center gap-4 mt-3 pt-3 border-t border-outline-variant/15">
            @if($sun['sunrise'])
                <div class="flex items-center gap-1.5">
                    <span class="material-symbols-outlined text-base text-primary" aria-hidden="true">wb_twilight</span>
                    <time datetime="{{ $sun['sunrise']->toIso8601String() }}" class="text-xs text-on-surface-variant">{{ $sun['sunrise']->translatedFormat('H:i') }}</time>
                </div>
            @endif
            @if($sun['sunset'])
                <div class="flex items-center gap-1.5">
                    <span class="material-symbols-outlined text-base text-tertiary" aria-hidden="true">bedtime</span>
                    <time datetime="{{ $sun['sunset']->toIso8601String() }}" class="text-xs text-on-surface-variant">{{ $sun['sunset']->translatedFormat('H:i') }}</time>
                </div>
            @endif
        </div>
    @endif
</div>
