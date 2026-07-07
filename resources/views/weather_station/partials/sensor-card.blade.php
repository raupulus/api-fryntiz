{{-- Tarjeta de sensor con valor principal/secundario del último registro. Espera $section. --}}
<a href="{{ $section['url'] }}" class="cursor-pointer bg-surface-container-lowest rounded-xl p-6 shadow-lg hover:shadow-xl transition-shadow group">
    <div class="flex items-center justify-between mb-4">
        <div class="w-12 h-12 bg-surface-container rounded-lg flex items-center justify-center">
            <span class="material-symbols-outlined text-on-tertiary-container">{{ $section['icon'] }}</span>
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
