<x-filament-panels::page>
    <x-filament::section
        heading="Mantenimiento desde la intranet"
        description="Ejecuta tareas seguras de sincronización, diagnóstico y limpieza sin abrir una terminal SSH. Solo accesible para SuperAdmin."
    >
        <div class="sys-ops__grid">
            @foreach ($operations as $operation)
                <x-filament::section :heading="$operation['label']">
                    <div class="sys-ops__card">
                        <p class="sys-ops__desc">{{ $operation['description'] }}</p>

                        <code class="sys-ops__cmd">php artisan {{ $operation['command'] }}</code>

                        <div class="sys-ops__footer">
                            <x-filament::button
                                wire:click="runOperation('{{ $operation['command'] }}')"
                                wire:loading.attr="disabled"
                                wire:target="runOperation('{{ $operation['command'] }}')"
                                icon="heroicon-m-play"
                                :color="$operation['color']"
                            >
                                <span wire:loading.remove wire:target="runOperation('{{ $operation['command'] }}')">
                                    Ejecutar
                                </span>
                                <span wire:loading wire:target="runOperation('{{ $operation['command'] }}')">
                                    Ejecutando...
                                </span>
                            </x-filament::button>
                        </div>
                    </div>
                </x-filament::section>
            @endforeach
        </div>
    </x-filament::section>

    @if ($lastCommand !== null)
        <x-filament::section
            :heading="'Última salida · ' . $lastCommand"
            :description="'Código de salida: ' . $lastExitCode"
        >
            <pre class="sys-ops__output">{{ $lastOutput }}</pre>
        </x-filament::section>
    @endif
</x-filament-panels::page>
