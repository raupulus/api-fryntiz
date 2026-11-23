{{-- Aviso AEMET vigente de mayor gravedad en Cádiz. Bloque a todo el ancho. Espera $aemetAlert (AEMETAdverseEvents|null). --}}
@php
    $severityStyles = [
        'Moderate' => ['container' => 'bg-warning-container text-on-warning-container', 'icon' => 'text-on-warning-container'],
        'Severe' => ['container' => 'bg-warning-container text-on-warning-container border border-error/40', 'icon' => 'text-error'],
        'Extreme' => ['container' => 'bg-error-container text-on-error-container', 'icon' => 'text-on-error-container'],
    ];

    $style = $aemetAlert ? ($severityStyles[$aemetAlert->severity] ?? $severityStyles['Moderate']) : null;
@endphp

@if($aemetAlert)
    <div class="w-full rounded-xl p-6 shadow-lg flex flex-wrap sm:flex-nowrap items-center gap-4 {{ $style['container'] }}">
        <span class="material-symbols-outlined text-3xl shrink-0 {{ $style['icon'] }}" aria-hidden="true">warning</span>
        <div class="flex-1 min-w-0">
            <h3 class="text-lg font-bold">Aviso AEMET — {{ $aemetAlert->level ? ucfirst($aemetAlert->level) : $aemetAlert->severity }}</h3>
            <p class="text-sm">{{ $aemetAlert->event ?? $aemetAlert->event_code }}</p>
        </div>
        <p class="text-xs opacity-90 sm:text-right shrink-0">
            {{ $aemetAlert->name }}
            @if($aemetAlert->effective_at)
                <br><time datetime="{{ $aemetAlert->effective_at->toIso8601String() }}">{{ $aemetAlert->effective_at->translatedFormat('d/m H:i') }}</time>
            @endif
        </p>
    </div>
@else
    <div class="w-full bg-surface-container-lowest rounded-xl p-6 shadow-lg flex items-center gap-4">
        <span class="material-symbols-outlined text-3xl shrink-0 text-success" aria-hidden="true">check_circle</span>
        <div>
            <h3 class="text-lg font-bold text-on-surface">Sin avisos AEMET</h3>
            <p class="text-xs text-on-surface-variant">No hay avisos vigentes en la provincia de Cádiz</p>
        </div>
    </div>
@endif
