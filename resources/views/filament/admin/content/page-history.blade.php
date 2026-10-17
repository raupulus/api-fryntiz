{{-- Historial de una página (G5), dentro del modal «Historial». Clases cpe-history* en panel.css. --}}
<div class="cpe-history">
    @forelse ($versions as $version)
        {{-- El texto se pide al pulsar «Ver», no al abrir el historial. --}}
        <div
            class="cpe-history__item"
            wire:key="version-{{ $version->id }}"
            x-data="{
                open: false,
                text: null,
                async toggle() {
                    this.open = ! this.open;

                    if (this.open && this.text === null) {
                        this.text = await $wire.versionContent({{ $version->id }});
                    }
                },
            }"
        >
            <div class="cpe-history__head">
                <div class="cpe-history__meta">
                    <strong>{{ $version->created_at?->timezone(config('app.display_timezone', 'Europe/Madrid'))->format('d/m/Y H:i') }}</strong>
                    <span>{{ $version->user->name ?? 'Sistema' }}</span>
                    <x-filament::badge color="gray">{{ $version->format->label() }}</x-filament::badge>
                    <span class="cpe-history__reason">{{ $version->reason->label() }}</span>
                </div>
                <div class="cpe-history__actions">
                    <x-filament::link tag="button" x-on:click="toggle()" x-text="open ? 'Ocultar' : 'Ver'">Ver</x-filament::link>
                    @if (($canRestoreHtml ?? false) || $version->format !== \App\Enums\ContentPageFormatEnum::Html)
                        <x-filament::button size="sm" color="warning" wire:click="loadVersion({{ $version->id }})">Recuperar</x-filament::button>
                    @else
                        <span class="cpe-history__locked">Sólo un administrador</span>
                    @endif
                </div>
            </div>
            <pre class="cpe-history__content" x-show="open" x-cloak x-text="text ?? 'Cargando…'"></pre>
        </div>
    @empty
        <p class="cpe-history__empty">Todavía no hay versiones: se guarda una cada vez que un guardado cambia el contenido.</p>
    @endforelse
</div>
