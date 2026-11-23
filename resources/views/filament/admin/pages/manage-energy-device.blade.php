{{--
    La ficha energética de un aparato.

    Arriba quién es —foto a la izquierda, sus datos a la derecha—; luego una
    gráfica por papel con la última semana; y debajo una pestaña por cada fila
    de `hardware_energy` con su configuración y sus tres tablas de telemetría.
    Todo sin salir de la página.
--}}
<x-filament-panels::page>
    @php
        $device = $this->device();
        $elements = $this->elements();
        $active = $this->activeElement();
        $details = $this->deviceDetails();
    @endphp

    {{--
        Quién es este aparato. Los estilos `ed-device__…` están en
        resources/css/filament/admin/panel.css: en el panel no hay utilidades de
        Tailwind, y los estilos propios van ahí y no en un <style> suelto.
    --}}
    <x-filament::section>
        <div class="ed-device">
            @if ($thumbnail = $this->thumbnail())
                <img src="{{ $thumbnail }}" alt="{{ $device->display_name }}" class="ed-device__photo">
            @else
                <div class="ed-device__photo">
                    <x-filament::icon icon="heroicon-o-cpu-chip" class="ed-device__icon" />
                </div>
            @endif

            <div class="ed-device__details">
                <h2 class="ed-device__name">{{ $device->display_name }}</h2>

                @if ($details !== [])
                    <dl class="ed-device__specs">
                        @foreach ($details as $label => $value)
                            <div class="ed-device__spec">
                                <dt class="ed-device__label">{{ $label }}</dt>
                                <dd class="ed-device__value">{{ $value }}</dd>
                            </div>
                        @endforeach
                    </dl>
                @endif
            </div>
        </div>
    </x-filament::section>

    @if ($elements->isEmpty())
        <x-filament::section>
            <x-slot name="heading">Este aparato no mide energía todavía</x-slot>

            <p class="ed-empty">
                Dale de alta su primer papel con los botones de arriba: generador
                si produce, batería si almacena y consumo si gasta. Puede tener
                los tres a la vez.
            </p>
        </x-filament::section>
    @else
        {{-- Cómo ha ido cada papel esta semana --}}
        @foreach ($elements->pluck('role')->unique() as $role)
            @livewire(
                \App\Filament\Admin\Widgets\EnergyRoleTrendChart::class,
                ['deviceId' => $device->getKey(), 'role' => $role],
                key('chart-' . $role . '-' . $device->getKey())
            )
        @endforeach

        {{-- Una pestaña por fila de hardware_energy --}}
        <x-filament::tabs>
            @foreach ($elements as $element)
                <x-filament::tabs.item
                    :active="$active?->getKey() === $element->getKey()"
                    :badge="$element->is_active ? null : 'inactivo'"
                    badge-color="danger"
                    wire:click="$set('elementId', {{ $element->getKey() }})"
                    wire:key="tab-{{ $element->getKey() }}"
                >
                    {{ $this->labelFor($element) }}
                </x-filament::tabs.item>
            @endforeach
        </x-filament::tabs>

        @if ($active)
            {{-- Lo que mide este canal, y cómo --}}
            <x-filament::section
                :heading="'Configuración · ' . $this->labelFor($active)"
                description="Lo que define este canal. Los papeles del mismo aparato no comparten nada de esto: cada uno mide una cosa, a su tensión."
                wire:key="configuration-{{ $active->getKey() }}"
            >
                <form wire:submit="save">
                    {{ $this->configuration }}

                    <div class="ed-actions">
                        <x-filament::button type="submit">
                            Guardar configuración
                        </x-filament::button>
                    </div>
                </form>
            </x-filament::section>

            {{-- Y lo que ha ido midiendo --}}
            @livewire(
                \App\Filament\Admin\Resources\Hardware\HardwareEnergies\RelationManagers\ReadingsRelationManager::class,
                ['ownerRecord' => $active, 'pageClass' => static::class],
                key('readings-' . $active->getKey())
            )

            @livewire(
                \App\Filament\Admin\Resources\Hardware\HardwareEnergies\RelationManagers\TodayRelationManager::class,
                ['ownerRecord' => $active, 'pageClass' => static::class],
                key('today-' . $active->getKey())
            )

            @livewire(
                \App\Filament\Admin\Resources\Hardware\HardwareEnergies\RelationManagers\HistoricalRelationManager::class,
                ['ownerRecord' => $active, 'pageClass' => static::class],
                key('historical-' . $active->getKey())
            )
        @endif
    @endif
</x-filament-panels::page>
