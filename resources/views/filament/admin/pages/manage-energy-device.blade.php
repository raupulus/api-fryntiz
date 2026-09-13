{{--
    La ficha energética de un aparato.

    Arriba quién es —foto a la izquierda, sus datos a la derecha—; luego una
    gráfica por papel con la última semana; y debajo una pestaña por cada fila
    de `hardware_energy` con su configuración y sus tres tablas de telemetría.
    Todo sin salir de la página.
--}}
<x-filament-panels::page>
    @php
        $aparato = $this->aparato();
        $elementos = $this->elementos();
        $activo = $this->elementoActivo();
        $senas = $this->senasDelAparato();
    @endphp

    {{--
        Quién es este aparato. Los estilos `ed-aparato__…` están en
        resources/css/filament/admin/panel.css: en el panel no hay utilidades de
        Tailwind, y los estilos propios van ahí y no en un <style> suelto.
    --}}
    <x-filament::section>
        <div class="ed-aparato">
            @if ($miniatura = $this->miniatura())
                <img src="{{ $miniatura }}" alt="{{ $aparato->display_name }}" class="ed-aparato__foto">
            @else
                <div class="ed-aparato__foto">
                    <x-filament::icon icon="heroicon-o-cpu-chip" class="ed-aparato__icono" />
                </div>
            @endif

            <div class="ed-aparato__datos">
                <h2 class="ed-aparato__nombre">{{ $aparato->display_name }}</h2>

                @if ($senas !== [])
                    <dl class="ed-aparato__senas">
                        @foreach ($senas as $etiqueta => $valor)
                            <div class="ed-aparato__sena">
                                <dt class="ed-aparato__etiqueta">{{ $etiqueta }}</dt>
                                <dd class="ed-aparato__valor">{{ $valor }}</dd>
                            </div>
                        @endforeach
                    </dl>
                @endif
            </div>
        </div>
    </x-filament::section>

    @if ($elementos->isEmpty())
        <x-filament::section>
            <x-slot name="heading">Este aparato no mide energía todavía</x-slot>

            <p class="ed-vacio">
                Dale de alta su primer papel con los botones de arriba: generador
                si produce, batería si almacena y consumo si gasta. Puede tener
                los tres a la vez.
            </p>
        </x-filament::section>
    @else
        {{-- Cómo ha ido cada papel esta semana --}}
        @foreach ($elementos->pluck('role')->unique() as $papel)
            @livewire(
                \App\Filament\Admin\Widgets\EnergyRoleTrendChart::class,
                ['deviceId' => $aparato->getKey(), 'papel' => $papel],
                key('grafica-' . $papel . '-' . $aparato->getKey())
            )
        @endforeach

        {{-- Una pestaña por fila de hardware_energy --}}
        <x-filament::tabs>
            @foreach ($elementos as $elemento)
                <x-filament::tabs.item
                    :active="$activo?->getKey() === $elemento->getKey()"
                    :badge="$elemento->is_active ? null : 'inactivo'"
                    badge-color="danger"
                    wire:click="$set('elementoId', {{ $elemento->getKey() }})"
                    wire:key="pestana-{{ $elemento->getKey() }}"
                >
                    {{ $this->etiquetaDe($elemento) }}
                </x-filament::tabs.item>
            @endforeach
        </x-filament::tabs>

        @if ($activo)
            {{-- Lo que mide este canal, y cómo --}}
            <x-filament::section
                :heading="'Configuración · ' . $this->etiquetaDe($activo)"
                description="Lo que define este canal. Los papeles del mismo aparato no comparten nada de esto: cada uno mide una cosa, a su tensión."
                wire:key="configuracion-{{ $activo->getKey() }}"
            >
                <form wire:submit="guardar">
                    {{ $this->configuracion }}

                    <div class="mt-6">
                        <x-filament::button type="submit">
                            Guardar configuración
                        </x-filament::button>
                    </div>
                </form>
            </x-filament::section>

            {{-- Y lo que ha ido midiendo --}}
            @livewire(
                \App\Filament\Admin\Resources\Hardware\HardwareEnergies\RelationManagers\ReadingsRelationManager::class,
                ['ownerRecord' => $activo, 'pageClass' => static::class],
                key('lecturas-' . $activo->getKey())
            )

            @livewire(
                \App\Filament\Admin\Resources\Hardware\HardwareEnergies\RelationManagers\TodayRelationManager::class,
                ['ownerRecord' => $activo, 'pageClass' => static::class],
                key('diarios-' . $activo->getKey())
            )

            @livewire(
                \App\Filament\Admin\Resources\Hardware\HardwareEnergies\RelationManagers\HistoricalRelationManager::class,
                ['ownerRecord' => $activo, 'pageClass' => static::class],
                key('historico-' . $activo->getKey())
            )
        @endif
    @endif
</x-filament-panels::page>
