{{--
    La ficha energética de un aparato.

    Arriba quién es —foto y nombre—, debajo una pestaña por cada fila de
    `hardware_energy` y, dentro, su configuración y sus tres tablas de
    telemetría. Todo sin salir de la página.
--}}
<x-filament-panels::page>
    @php
        $aparato = $this->aparato();
        $elementos = $this->elementos();
        $activo = $this->elementoActivo();
    @endphp

    {{-- Quién es este aparato --}}
    <x-filament::section>
        <div class="fi-ta-content flex items-center gap-4">
            @if ($miniatura = $this->miniatura())
                <img
                    src="{{ $miniatura }}"
                    alt="{{ $aparato->display_name }}"
                    class="h-20 w-20 shrink-0 rounded-lg object-cover ring-1 ring-gray-950/10 dark:ring-white/20"
                >
            @else
                <div class="flex h-20 w-20 shrink-0 items-center justify-center rounded-lg bg-gray-100 ring-1 ring-gray-950/10 dark:bg-white/5 dark:ring-white/20">
                    <x-filament::icon icon="heroicon-o-cpu-chip" class="h-8 w-8 text-gray-400" />
                </div>
            @endif

            <div class="min-w-0">
                <h2 class="text-xl font-bold tracking-tight text-gray-950 dark:text-white">
                    {{ $aparato->display_name }}
                </h2>

                <dl class="mt-1 flex flex-wrap gap-x-4 gap-y-1 text-sm text-gray-500 dark:text-gray-400">
                    @foreach ($this->senasDelAparato() as $etiqueta => $valor)
                        <div class="flex gap-1">
                            <dt class="font-medium">{{ $etiqueta }}:</dt>
                            <dd>{{ $valor }}</dd>
                        </div>
                    @endforeach
                </dl>
            </div>
        </div>
    </x-filament::section>

    @if ($elementos->isEmpty())
        <x-filament::section>
            <x-slot name="heading">Este aparato no mide energía todavía</x-slot>

            <p class="text-sm text-gray-500 dark:text-gray-400">
                Dale de alta su primer papel con los botones de arriba: generador
                si produce, batería si almacena y consumo si gasta. Puede tener
                los tres a la vez.
            </p>
        </x-filament::section>
    @else
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
