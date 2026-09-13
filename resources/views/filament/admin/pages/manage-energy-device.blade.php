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

    {{-- Quién es este aparato --}}
    <x-filament::section>
        <div class="flex flex-col gap-6 sm:flex-row sm:items-start">
            <div class="shrink-0">
                @if ($miniatura = $this->miniatura())
                    <img
                        src="{{ $miniatura }}"
                        alt="{{ $aparato->display_name }}"
                        class="h-32 w-32 rounded-xl object-cover ring-1 ring-gray-950/10 dark:ring-white/20"
                    >
                @else
                    <div class="flex h-32 w-32 items-center justify-center rounded-xl bg-gray-100 ring-1 ring-gray-950/10 dark:bg-white/5 dark:ring-white/20">
                        <x-filament::icon icon="heroicon-o-cpu-chip" class="h-10 w-10 text-gray-400" />
                    </div>
                @endif
            </div>

            <div class="min-w-0 flex-1">
                <h2 class="text-2xl font-bold tracking-tight text-gray-950 dark:text-white">
                    {{ $aparato->display_name }}
                </h2>

                @if ($senas !== [])
                    <dl class="mt-4 grid grid-cols-1 gap-x-8 gap-y-4 sm:grid-cols-2 lg:grid-cols-4">
                        @foreach ($senas as $etiqueta => $valor)
                            <div>
                                <dt class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                    {{ $etiqueta }}
                                </dt>
                                <dd class="mt-1 text-sm font-medium text-gray-950 dark:text-white">
                                    {{ $valor }}
                                </dd>
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

            <p class="text-sm text-gray-500 dark:text-gray-400">
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
