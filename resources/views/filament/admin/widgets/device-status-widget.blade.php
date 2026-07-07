<x-filament-widgets::widget>
    <x-filament::section class="h-full">
        <x-slot name="heading">
            <div style="display: flex; align-items: center; gap: 8px; font-weight: 600; font-size: 15px;" class="text-zinc-900 dark:text-white">
                <x-filament::icon icon="heroicon-o-cpu-chip" style="width: 20px; height: 20px; color: #0ea5e9; flex-shrink: 0;" />
                <span>Estado de dispositivos</span>
            </div>
        </x-slot>

        @php
            // Mapa de colores a clases fijas (evita el purgado de Tailwind con clases dinámicas).
            $chipColors = [
                'orange' => 'bg-orange-100 text-orange-600 dark:bg-orange-500/10 dark:text-orange-400',
                'amber' => 'bg-amber-100 text-amber-600 dark:bg-amber-500/10 dark:text-amber-400',
                'sky' => 'bg-sky-100 text-sky-600 dark:bg-sky-500/10 dark:text-sky-400',
                'indigo' => 'bg-indigo-100 text-indigo-600 dark:bg-indigo-500/10 dark:text-indigo-400',
                'emerald' => 'bg-emerald-100 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-400',
                'green' => 'bg-green-100 text-green-600 dark:bg-green-500/10 dark:text-green-400',
            ];
        @endphp

        @if ($devices->isEmpty())
            <p class="text-gray-500 dark:text-zinc-400" style="font-size: 13px; margin-top: 8px;">
                Ningún dispositivo ha reportado métricas de estado todavía.
            </p>
        @else
            <div style="display: flex; flex-direction: column; gap: 10px; margin-top: 8px;">
                @foreach ($devices as $device)
                    <div class="bg-gray-50 dark:bg-white/5 border border-gray-100 dark:border-white/10"
                         style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px; padding: 12px 14px; border-radius: 12px;">
                        <div style="display: flex; flex-direction: column; gap: 2px; min-width: 160px;">
                            <span class="text-gray-800 dark:text-white" style="font-size: 14px; font-weight: 700; line-height: 1.25;">{{ $device['name'] }}</span>
                            @if ($device['last_seen_at'])
                                <span class="text-gray-500 dark:text-zinc-400" style="font-size: 11px; line-height: 1.25;">Última vez: {{ $device['last_seen_at']->diffForHumans() }}</span>
                            @endif
                        </div>

                        <div style="display: flex; align-items: center; flex-wrap: wrap; gap: 8px; justify-content: flex-end;">
                            @foreach ($device['metrics'] as $metric)
                                <div class="{{ $chipColors[$metric['color']] ?? $chipColors['sky'] }}"
                                     title="{{ $metric['label'] }}"
                                     style="display: flex; align-items: center; gap: 6px; padding: 6px 10px; border-radius: 9999px;">
                                    <x-filament::icon :icon="$metric['icon']" style="width: 16px; height: 16px; flex-shrink: 0;" />
                                    <span style="font-size: 13px; font-weight: 700; line-height: 1;">{{ $metric['value'] }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
