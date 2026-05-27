<x-filament-panels::page>
    <x-filament::section>
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-bold flex items-center gap-2 text-gray-900 dark:text-white">
                    <x-filament::icon icon="heroicon-o-cloud" class="h-6 w-6 text-sky-500" />
                    Panel de datos AEMET
                </h2>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    Monitorización y sincronización de las fuentes de datos de la Agencia Estatal de Meteorología.
                </p>
            </div>
            <div class="flex items-center gap-3 text-sm">
                <x-filament::badge color="success">
                    {{ collect($cards)->sum('count') }} registros totales
                </x-filament::badge>
            </div>
        </div>
    </x-filament::section>

    {{-- Grid de tarjetas --}}
    <div class="grid gap-8 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 pt-4">
        @foreach ($cards as $key => $card)
            <x-filament::section class="flex flex-col h-full border border-gray-200 dark:border-white/10 shadow-sm rounded-xl">
                <x-slot name="heading">
                    <span class="text-lg">{{ $card['label'] }}</span>
                </x-slot>

                <div class="flex flex-col flex-grow gap-5">
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        {{ $card['description'] }}
                    </p>

                    <div class="flex items-center justify-between">
                        <x-filament::badge color="info" icon="heroicon-m-circle-stack" size="lg">
                            {{ number_format($card['count']) }} registros
                        </x-filament::badge>

                        @if ($card['count'] > 0)
                            <x-filament::badge color="success">Activo</x-filament::badge>
                        @else
                            <x-filament::badge color="warning">Sin datos</x-filament::badge>
                        @endif
                    </div>

                    @if (! empty($card['missing']))
                        <div class="mt-2">
                            <x-filament::badge color="danger" icon="heroicon-m-exclamation-triangle" class="w-full justify-center">
                                Modelo no encontrado
                            </x-filament::badge>
                        </div>
                    @else
                        <div class="flex flex-col gap-4 mt-2">
                            {{-- Última actualización --}}
                            <div class="flex items-center justify-between text-sm text-gray-600 dark:text-gray-300 bg-gray-50 dark:bg-white/5 p-3 rounded-lg">
                                <span class="flex items-center gap-2 font-semibold">
                                    <x-filament::icon icon="heroicon-m-clock" class="h-5 w-5 text-gray-400" />
                                    Actualizado:
                                </span>
                                @if ($card['last'])
                                    <span>
                                        {{ \Carbon\Carbon::parse($card['last'])->format('d/m/Y H:i') }}
                                    </span>
                                @else
                                    <span class="italic">nunca</span>
                                @endif
                            </div>

                            {{-- Comando asociado --}}
                            <div class="flex items-center justify-between text-sm text-gray-600 dark:text-gray-300">
                                <span class="flex items-center gap-2 font-semibold">
                                    <x-filament::icon icon="heroicon-m-command-line" class="h-5 w-5 text-gray-400" />
                                    Cmd:
                                </span>
                                <code class="rounded bg-sky-100 dark:bg-sky-900/40 px-2 py-1 font-mono text-xs text-sky-800 dark:text-sky-300 truncate" title="{{ $card['command'] }}">
                                    {{ $card['command'] }}
                                </code>
                            </div>
                        </div>

                        {{-- Botón de sincronización --}}
                        <div class="pt-4 mt-auto border-t border-gray-100 dark:border-white/10">
                            <x-filament::button
                                wire:click="resync('{{ $card['command'] }}')"
                                wire:loading.attr="disabled"
                                wire:target="resync('{{ $card['command'] }}')"
                                icon="heroicon-m-arrow-path"
                                color="primary"
                                size="lg"
                                class="w-full bg-primary-600 text-white shadow-md hover:bg-primary-500"
                            >
                                <span wire:loading.remove wire:target="resync('{{ $card['command'] }}')">
                                    Sincronizar ahora
                                </span>
                                <span wire:loading wire:target="resync('{{ $card['command'] }}')">
                                    Ejecutando...
                                </span>
                            </x-filament::button>
                        </div>
                    @endif
                </div>
            </x-filament::section>
        @endforeach
    </div>
</x-filament-panels::page>
