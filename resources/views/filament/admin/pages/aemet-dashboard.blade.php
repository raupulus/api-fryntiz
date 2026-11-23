<x-filament-panels::page>
    <x-filament::section>
        <div class="aemet-dash__header">
            <div class="aemet-dash__header-copy">
                <h2 class="aemet-dash__title">
                    <x-filament::icon icon="heroicon-o-cloud" class="aemet-dash__title-icon" />
                    <span>Panel de datos AEMET</span>
                </h2>
                <p class="aemet-dash__subtitle">
                    Monitorización y sincronización de las fuentes de datos de la Agencia Estatal de Meteorología.
                </p>
            </div>
            <div class="aemet-dash__header-meta">
                <x-filament::badge color="success">
                    {{ number_format((int) collect($cards)->sum('count')) }} registros totales
                </x-filament::badge>
            </div>
        </div>
    </x-filament::section>

    {{-- La clave de AEMET es un JWT que caduca a los ~100 días, y su caducidad
         NO da error: la API responde 200 con el cuerpo vacío. Si no se avisa
         aquí, la integración se queda muda y no se entera nadie. --}}
    @if (($apiKey['status'] ?? 'ok') !== 'ok')
        @php
            $isCriticalKey = ($apiKey['status'] === 'expired' || $apiKey['status'] === 'no_key');
        @endphp
        <x-filament::section>
            <div class="aemet-dash__alert">
                <x-filament::icon
                    icon="{{ $isCriticalKey ? 'heroicon-o-exclamation-triangle' : 'heroicon-o-clock' }}"
                    class="aemet-dash__alert-icon {{ $isCriticalKey ? 'aemet-dash__alert-icon--danger' : 'aemet-dash__alert-icon--warning' }}"
                />
                <div class="aemet-dash__alert-body">
                    <h3 class="aemet-dash__alert-title">
                        Clave de AEMET: {{ str_replace('_', ' ', $apiKey['status']) }}
                    </h3>
                    <p class="aemet-dash__alert-text">
                        {{ $apiKey['message'] }}
                    </p>
                </div>
            </div>
        </x-filament::section>
    @endif

    {{-- Grid de tarjetas --}}
    <div class="aemet-dash__grid">
        @foreach ($cards as $key => $card)
            <x-filament::section class="aemet-dash__section">
                <x-slot name="heading">
                    <span class="aemet-dash__card-heading">{{ $card['label'] }}</span>
                </x-slot>

                <div class="aemet-dash__card-body">
                    <p class="aemet-dash__card-desc">
                        {{ $card['description'] }}
                    </p>

                    <div class="aemet-dash__card-badges">
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
                        <div class="aemet-dash__card-missing">
                            <x-filament::badge color="danger" icon="heroicon-m-exclamation-triangle">
                                Modelo no encontrado
                            </x-filament::badge>
                        </div>
                    @else
                        <div class="aemet-dash__card-meta">
                            {{-- Última actualización --}}
                            <div class="aemet-dash__meta-row aemet-dash__meta-row--highlight">
                                <span class="aemet-dash__meta-label">
                                    <x-filament::icon icon="heroicon-m-clock" class="aemet-dash__meta-icon" />
                                    Actualizado:
                                </span>
                                @if ($card['last'])
                                    <span class="aemet-dash__meta-value">
                                        {{ \Carbon\Carbon::parse($card['last'])
                                            ->timezone(config('app.display_timezone', 'Europe/Madrid'))
                                            ->format('d/m/Y H:i') }}
                                    </span>
                                @else
                                    <span class="aemet-dash__meta-empty">nunca</span>
                                @endif
                            </div>

                            {{-- Comando asociado --}}
                            <div class="aemet-dash__meta-row">
                                <span class="aemet-dash__meta-label">
                                    <x-filament::icon icon="heroicon-m-command-line" class="aemet-dash__meta-icon" />
                                    Cmd:
                                </span>
                                <code class="aemet-dash__cmd" title="{{ $card['command'] }}">
                                    {{ $card['command'] }}
                                </code>
                            </div>
                        </div>

                        {{-- Botón de sincronización --}}
                        <div class="aemet-dash__card-footer">
                            <x-filament::button
                                wire:click="resync('{{ $card['command'] }}')"
                                wire:loading.attr="disabled"
                                wire:target="resync('{{ $card['command'] }}')"
                                icon="heroicon-m-arrow-path"
                                color="primary"
                                size="lg"
                                class="aemet-dash__sync-btn"
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

    {{-- Atribución obligatoria. No es cortesía: la nota legal de AEMET exige
         citarla como fuente y conservar sus metadatos, y la Ley 18/2015 trae
         régimen sancionador. Los textos son los literales oficiales. --}}
    <x-filament::section>
        <div class="aemet-dash__attribution">
            <p class="aemet-dash__attribution-lead">{{ $attribution['short'] }}</p>
            <p>{{ $attribution['copyright'] }}</p>
            <p>
                <a href="{{ $attribution['legal_notice'] }}" target="_blank" rel="noopener noreferrer" class="aemet-dash__attribution-link">
                    Nota legal de AEMET
                </a>
            </p>
        </div>
    </x-filament::section>
</x-filament-panels::page>
