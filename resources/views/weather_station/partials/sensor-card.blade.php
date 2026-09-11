{{-- Tarjeta de sensor con valor principal/secundario del último registro. Espera $section. --}}
<a href="{{ $section['url'] }}" class="cursor-pointer bg-surface-container-lowest rounded-xl p-6 shadow-lg hover:shadow-xl transition-shadow group">
    <div class="flex items-center justify-between mb-4">
        <div class="w-12 h-12 bg-surface-container rounded-lg flex items-center justify-center">
            @if(($section['rotate'] ?? null) !== null)
                {{-- "navigation" es una flecha que por defecto apunta al Norte (0º);
                     se rota en sentido horario con los mismos grados de brújula
                     que devuelve el sensor (0 = N, 90 = E, 180 = S, 270 = O). --}}
                <span
                    class="material-symbols-outlined text-on-tertiary-container inline-block"
                    style="transform: rotate({{ $section['rotate'] }}deg)"
                    title="{{ $section['rotate'] }}º"
                >navigation</span>
            @else
                <span class="material-symbols-outlined text-on-tertiary-container">{{ $section['icon'] }}</span>
            @endif
        </div>
        @if($section['primary'])
            <div class="text-right">
                <div class="text-xl font-bold text-on-surface">{{ $section['primary'] }}</div>
                @if($section['secondary'])
                    <div class="text-xs text-on-surface-variant">{{ $section['secondary'] }}</div>
                @endif
            </div>
        @endif
    </div>
    <h3 class="text-lg font-bold text-on-surface mb-2">{{ $section['title'] }}</h3>
    <span class="text-primary-container font-bold text-xs uppercase tracking-widest group-hover:underline">Ver datos →</span>
</a>
