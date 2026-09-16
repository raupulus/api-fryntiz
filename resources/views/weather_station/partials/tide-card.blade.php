{{--
    Próxima marea (Open-Meteo) + estado de la mar (AEMET).
    Espera $nextTide (OpenMeteoMarineTide|null), $seaState (string|null) y
    $seaStateUpdatedAt (Carbon|null, fecha de elaboración del dato AEMET).
--}}
<div class="bg-surface-container-lowest rounded-xl p-6 shadow-lg">
    <div class="flex items-center justify-between mb-4">
        <div class="w-12 h-12 bg-surface-container rounded-lg flex items-center justify-center">
            <span class="material-symbols-outlined text-on-tertiary-container">waves</span>
        </div>
        @if($nextTide)
            <div class="text-right">
                <div class="text-xl font-bold text-on-surface">{{ number_format($nextTide->height_m, 2) }} m</div>
                <div class="text-xs text-on-surface-variant">{{ $nextTide->happens_at->translatedFormat('H:i') }}</div>
            </div>
        @endif
    </div>
    <h3 class="text-lg font-bold text-on-surface mb-2">Marea</h3>
    @if($nextTide)
        <span class="text-on-surface-variant text-xs uppercase tracking-widest block">
            Próxima {{ $nextTide->type->label() }} — {{ $nextTide->happens_at->translatedFormat('d/m H:i') }}
        </span>
    @else
        <span class="text-on-surface-variant text-xs uppercase tracking-widest block">Sin predicción disponible</span>
    @endif
    @if($seaState)
        <span class="text-on-surface-variant text-xs uppercase tracking-widest block mt-1">
            Estado de la mar: {{ $seaState }}
            @if($seaStateUpdatedAt)
                ({{ $seaStateUpdatedAt->translatedFormat('d/m H:i') }})
            @endif
        </span>
    @endif
</div>
