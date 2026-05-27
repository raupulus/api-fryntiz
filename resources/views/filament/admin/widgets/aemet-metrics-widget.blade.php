<x-filament-widgets::widget>
    <x-filament::section class="h-full">
        <x-slot name="heading">
            <div style="display: flex; align-items: center; gap: 8px; font-weight: 600; font-size: 15px;" class="text-zinc-900 dark:text-white">
                <x-filament::icon icon="heroicon-o-cloud" style="width: 20px; height: 20px; color: #0ea5e9; flex-shrink: 0;" />
                <span>AEMET — Datos Principales</span>
            </div>
        </x-slot>

        <!-- CSS Stylesheet for hover transitions and responsive column counts -->
        <style>
            .aemet-grid {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 14px;
                margin-top: 10px;
            }
            @media (max-width: 640px) {
                .aemet-grid {
                    grid-template-columns: 1fr !important;
                }
            }
            .aemet-card {
                display: flex;
                align-items: center;
                gap: 12px;
                padding: 12px;
                border-radius: 12px;
                transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            }
            .aemet-card:hover {
                transform: translateY(-2px);
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
                border-color: rgba(14, 165, 233, 0.35) !important;
            }
            .dark .aemet-card:hover {
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
                border-color: rgba(56, 189, 248, 0.35) !important;
            }
        </style>

        <div class="aemet-grid" style="display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 14px; margin-top: 10px;">
            <!-- Temperatura -->
            <div class="aemet-card bg-gray-50 dark:bg-white/5 border border-gray-100 dark:border-white/10"
                 style="display: flex; align-items: center; gap: 12px; padding: 12px; border-radius: 12px; cursor: default;">
                <div class="bg-orange-100 text-orange-600 dark:bg-orange-500/10 dark:text-orange-400"
                     style="display: flex; align-items: center; justify-content: center; width: 40px; height: 40px; border-radius: 10px; flex-shrink: 0;">
                    <x-filament::icon icon="heroicon-o-fire" style="width: 20px; height: 20px;" />
                </div>
                <div style="display: flex; flex-direction: column; gap: 2px;">
                    <span class="text-gray-500 dark:text-zinc-400" style="font-size: 11px; font-weight: 500; line-height: 1.25;">Temperatura</span>
                    <span class="text-gray-800 dark:text-white" style="font-size: 14px; font-weight: 700; line-height: 1.25;">{{ number_format($temperature, 1) }} °C</span>
                </div>
            </div>

            <!-- Viento Máximo -->
            <div class="aemet-card bg-gray-50 dark:bg-white/5 border border-gray-100 dark:border-white/10"
                 style="display: flex; align-items: center; gap: 12px; padding: 12px; border-radius: 12px; cursor: default;">
                <div class="bg-sky-100 text-sky-600 dark:bg-sky-500/10 dark:text-sky-400"
                     style="display: flex; align-items: center; justify-content: center; width: 40px; height: 40px; border-radius: 10px; flex-shrink: 0;">
                    <x-filament::icon icon="heroicon-o-paper-airplane" style="width: 20px; height: 20px;" />
                </div>
                <div style="display: flex; flex-direction: column; gap: 2px;">
                    <span class="text-gray-500 dark:text-zinc-400" style="font-size: 11px; font-weight: 500; line-height: 1.25;">Viento Máximo</span>
                    <span class="text-gray-800 dark:text-white" style="font-size: 14px; font-weight: 700; line-height: 1.25;">{{ number_format($wind_max, 1) }} m/s</span>
                </div>
            </div>

            <!-- Dirección del Viento -->
            <div class="aemet-card bg-gray-50 dark:bg-white/5 border border-gray-100 dark:border-white/10"
                 style="display: flex; align-items: center; gap: 12px; padding: 12px; border-radius: 12px; cursor: default;">
                <div class="bg-teal-100 text-teal-600 dark:bg-teal-500/10 dark:text-teal-400"
                     style="display: flex; align-items: center; justify-content: center; width: 40px; height: 40px; border-radius: 10px; flex-shrink: 0;">
                    <x-filament::icon icon="heroicon-o-arrow-up-right" style="width: 20px; height: 20px;" />
                </div>
                <div style="display: flex; flex-direction: column; gap: 2px;">
                    <span class="text-gray-500 dark:text-zinc-400" style="font-size: 11px; font-weight: 500; line-height: 1.25;">Dirección Viento</span>
                    <span class="text-gray-800 dark:text-white" style="font-size: 14px; font-weight: 700; line-height: 1.25;">{{ $wind_direction }}</span>
                </div>
            </div>

            <!-- Radiación Solar -->
            <div class="aemet-card bg-gray-50 dark:bg-white/5 border border-gray-100 dark:border-white/10"
                 style="display: flex; align-items: center; gap: 12px; padding: 12px; border-radius: 12px; cursor: default;">
                <div class="bg-amber-100 text-amber-600 dark:bg-amber-500/10 dark:text-amber-400"
                     style="display: flex; align-items: center; justify-content: center; width: 40px; height: 40px; border-radius: 10px; flex-shrink: 0;">
                    <x-filament::icon icon="heroicon-o-sun" style="width: 20px; height: 20px;" />
                </div>
                <div style="display: flex; flex-direction: column; gap: 2px;">
                    <span class="text-gray-500 dark:text-zinc-400" style="font-size: 11px; font-weight: 500; line-height: 1.25;">Radiación Solar</span>
                    <span class="text-gray-800 dark:text-white" style="font-size: 14px; font-weight: 700; line-height: 1.25;">{{ number_format($solar_radiation, 1) }} W/m²</span>
                </div>
            </div>

            <!-- Humedad -->
            <div class="aemet-card bg-gray-50 dark:bg-white/5 border border-gray-100 dark:border-white/10"
                 style="display: flex; align-items: center; gap: 12px; padding: 12px; border-radius: 12px; cursor: default;">
                <div class="bg-blue-100 text-blue-600 dark:bg-blue-500/10 dark:text-blue-400"
                     style="display: flex; align-items: center; justify-content: center; width: 40px; height: 40px; border-radius: 10px; flex-shrink: 0;">
                    <x-filament::icon icon="heroicon-o-beaker" style="width: 20px; height: 20px;" />
                </div>
                <div style="display: flex; flex-direction: column; gap: 2px;">
                    <span class="text-gray-500 dark:text-zinc-400" style="font-size: 11px; font-weight: 500; line-height: 1.25;">Humedad</span>
                    <span class="text-gray-800 dark:text-white" style="font-size: 14px; font-weight: 700; line-height: 1.25;">{{ $humidity }} %</span>
                </div>
            </div>

            <!-- Probabilidad de Lluvia -->
            <div class="aemet-card bg-gray-50 dark:bg-white/5 border border-gray-100 dark:border-white/10"
                 style="display: flex; align-items: center; gap: 12px; padding: 12px; border-radius: 12px; cursor: default;">
                <div class="bg-indigo-100 text-indigo-600 dark:bg-indigo-500/10 dark:text-indigo-400"
                     style="display: flex; align-items: center; justify-content: center; width: 40px; height: 40px; border-radius: 10px; flex-shrink: 0;">
                    <x-filament::icon icon="heroicon-o-cloud" style="width: 20px; height: 20px;" />
                </div>
                <div style="display: flex; flex-direction: column; gap: 2px;">
                    <span class="text-gray-500 dark:text-zinc-400" style="font-size: 11px; font-weight: 500; line-height: 1.25;">Probabilidad Lluvia</span>
                    <span class="text-gray-800 dark:text-white" style="font-size: 14px; font-weight: 700; line-height: 1.25;">{{ $rain_prob }} %</span>
                </div>
            </div>

            <!-- Ozono -->
            <div class="aemet-card bg-gray-50 dark:bg-white/5 border border-gray-100 dark:border-white/10"
                 style="display: flex; align-items: center; gap: 12px; padding: 12px; border-radius: 12px; cursor: default;">
                <div class="bg-emerald-100 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-400"
                     style="display: flex; align-items: center; justify-content: center; width: 40px; height: 40px; border-radius: 10px; flex-shrink: 0;">
                    <x-filament::icon icon="heroicon-o-sparkles" style="width: 20px; height: 20px;" />
                </div>
                <div style="display: flex; flex-direction: column; gap: 2px;">
                    <span class="text-gray-500 dark:text-zinc-400" style="font-size: 11px; font-weight: 500; line-height: 1.25;">Ozono (O₃)</span>
                    <span class="text-gray-800 dark:text-white" style="font-size: 14px; font-weight: 700; line-height: 1.25;">{{ number_format($ozone, 1) }} µg/m³</span>
                </div>
            </div>

            <!-- Polen -->
            <div class="aemet-card bg-gray-50 dark:bg-white/5 border border-gray-100 dark:border-white/10"
                 style="display: flex; align-items: center; gap: 12px; padding: 12px; border-radius: 12px; cursor: default;">
                <div class="bg-rose-100 text-rose-600 dark:bg-rose-500/10 dark:text-rose-400"
                     style="display: flex; align-items: center; justify-content: center; width: 40px; height: 40px; border-radius: 10px; flex-shrink: 0;">
                    <x-filament::icon icon="heroicon-o-eye-slash" style="width: 20px; height: 20px;" />
                </div>
                <div style="display: flex; flex-direction: column; gap: 2px;">
                    <span class="text-gray-500 dark:text-zinc-400" style="font-size: 11px; font-weight: 500; line-height: 1.25;">Polen</span>
                    <span class="text-gray-800 dark:text-white" style="font-size: 14px; font-weight: 700; line-height: 1.25;">{{ $pollen }}</span>
                </div>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
