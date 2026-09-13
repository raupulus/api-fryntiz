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
        Quién es este aparato.

        CSS llano y no utilidades de Tailwind: el panel carga el `app.css`
        precompilado de Filament y no registra el tema del proyecto
        (`viteTheme`), así que clases como `sm:flex-row` o `lg:grid-cols-4`
        sencillamente no existen en el navegador y todo sale apilado. Es el
        mismo tropiezo que tuvo el campo de YouTube (commit 3fdcf2f).
    --}}
    <style>
        .ed-aparato { display: flex; flex-wrap: wrap; align-items: flex-start; gap: 1.5rem; }
        .ed-aparato__foto { flex: 0 0 auto; width: 8rem; height: 8rem; border-radius: 0.75rem; object-fit: cover; box-shadow: 0 0 0 1px rgba(3, 7, 18, 0.1); background: #f3f4f6; display: flex; align-items: center; justify-content: center; }
        .ed-aparato__datos { flex: 1 1 18rem; min-width: 0; }
        .ed-aparato__nombre { margin: 0; font-size: 1.5rem; line-height: 2rem; font-weight: 700; letter-spacing: -0.025em; color: #030712; }
        .ed-aparato__senas { margin: 1rem 0 0; display: flex; flex-wrap: wrap; gap: 1rem 2.5rem; }
        .ed-aparato__sena { margin: 0; min-width: 9rem; }
        .ed-aparato__etiqueta { font-size: 0.75rem; line-height: 1rem; font-weight: 500; text-transform: uppercase; letter-spacing: 0.05em; color: #6b7280; }
        .ed-aparato__valor { margin: 0.25rem 0 0; font-size: 0.875rem; line-height: 1.25rem; font-weight: 500; color: #030712; }
        .ed-vacio { font-size: 0.875rem; color: #6b7280; }
        .dark .ed-aparato__foto { box-shadow: 0 0 0 1px rgba(255, 255, 255, 0.2); background: rgba(255, 255, 255, 0.05); }
        .dark .ed-aparato__nombre, .dark .ed-aparato__valor { color: #ffffff; }
        .dark .ed-aparato__etiqueta, .dark .ed-vacio { color: #9ca3af; }
    </style>

    <x-filament::section>
        <div class="ed-aparato">
            @if ($miniatura = $this->miniatura())
                <img src="{{ $miniatura }}" alt="{{ $aparato->display_name }}" class="ed-aparato__foto">
            @else
                <div class="ed-aparato__foto">
                    <x-filament::icon icon="heroicon-o-cpu-chip" style="width: 2.5rem; height: 2.5rem; color: #9ca3af;" />
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
