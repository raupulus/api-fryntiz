<x-filament-panels::page>
    <form wire:submit="save">
        {{ $this->form }}

        <div class="fp-form-actions">
            <x-filament::button type="submit" wire:loading.attr="disabled" wire:target="save">
                Guardar
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>
