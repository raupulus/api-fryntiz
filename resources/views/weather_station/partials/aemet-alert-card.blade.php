{{-- Aviso AEMET vigente de mayor gravedad en Cádiz. Bloque a todo el ancho. Espera $aemetAlert (AEMETAdverseEvents|null). --}}
@php
    $severityStyles = [
        'Moderate' => ['container' => 'bg-amber-50 dark:bg-amber-950/30 text-amber-950 dark:text-amber-100', 'icon' => 'text-amber-600 dark:text-amber-400'],
        'Severe' => ['container' => 'bg-orange-50 dark:bg-orange-950/30 text-orange-950 dark:text-orange-100', 'icon' => 'text-orange-600 dark:text-orange-400'],
        'Extreme' => ['container' => 'bg-error-container/40 dark:bg-error-container/20 text-on-surface', 'icon' => 'text-error'],
    ];

    $style = $aemetAlert ? ($severityStyles[$aemetAlert->severity] ?? $severityStyles['Moderate']) : null;
@endphp

@if($aemetAlert)
    <div class="w-full rounded-xl p-6 shadow-lg flex items-center gap-4 {{ $style['container'] }}">
        <span class="material-symbols-outlined text-3xl shrink-0 {{ $style['icon'] }}">warning</span>
        <div class="flex-1 min-w-0">
            <h3 class="text-lg font-bold">Aviso AEMET — {{ $aemetAlert->level ? ucfirst($aemetAlert->level) : $aemetAlert->severity }}</h3>
            <p class="text-sm">{{ $aemetAlert->event ?? $aemetAlert->event_code }}</p>
        </div>
        <p class="text-xs opacity-80 text-right shrink-0">
            {{ $aemetAlert->name }}
            @if($aemetAlert->effective_at)
                <br>{{ $aemetAlert->effective_at->translatedFormat('d/m H:i') }}
            @endif
        </p>
    </div>
@else
    <div class="w-full bg-surface-container-lowest rounded-xl p-6 shadow-lg flex items-center gap-4">
        <span class="material-symbols-outlined text-3xl shrink-0 text-emerald-600 dark:text-emerald-400">check_circle</span>
        <div>
            <h3 class="text-lg font-bold text-on-surface">Sin avisos AEMET</h3>
            <p class="text-xs text-on-surface-variant">No hay avisos vigentes en la provincia de Cádiz</p>
        </div>
    </div>
@endif
