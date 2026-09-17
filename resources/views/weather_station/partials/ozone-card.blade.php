{{-- Ozono total diario (AEMET, Unidades Dobson). Espera $ozone (AEMETOzoneTotal|null). --}}
@php
    // Clasificación aproximada por Unidades Dobson: referencia orientativa
    // (columna total media global ~300 UD), no un umbral oficial de la OMM.
    $ozoneLevel = null;
    if ($ozone) {
        $ozoneLevel = match (true) {
            $ozone->ozone_value < 250 => ['label' => 'Bajo', 'class' => 'text-amber-600 dark:text-amber-400'],
            $ozone->ozone_value > 350 => ['label' => 'Alto', 'class' => 'text-orange-600 dark:text-orange-400'],
            default => ['label' => 'Normal', 'class' => 'text-emerald-600 dark:text-emerald-400'],
        };
    }
@endphp
<div class="bg-surface-container-lowest rounded-xl p-6 shadow-lg">
    <div class="flex items-center justify-between mb-4">
        <div class="w-12 h-12 bg-surface-container rounded-lg flex items-center justify-center">
            <span class="material-symbols-outlined text-on-tertiary-container">air</span>
        </div>
        @if($ozone)
            <div class="text-right">
                <div class="text-xl font-bold text-on-surface">{{ $ozone->ozone_value }} UD</div>
                <div class="text-xs {{ $ozoneLevel['class'] }}">{{ $ozoneLevel['label'] }}</div>
            </div>
        @endif
    </div>
    <h3 class="text-lg font-bold text-on-surface mb-2">Ozono</h3>
    @if($ozone)
        <span class="text-on-surface-variant text-xs uppercase tracking-widest">
            El Arenosillo · {{ $ozone->measured_on->translatedFormat('d/m/Y') }}
        </span>
    @else
        <span class="text-on-surface-variant text-xs uppercase tracking-widest">Sin dato disponible</span>
    @endif
</div>
