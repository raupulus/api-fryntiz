<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Energy\EnergyDevices\Pages;

use App\Filament\Admin\Resources\Energy\EnergyDevices\EnergyDeviceResource;
use App\Filament\Admin\Resources\Hardware\HardwareEnergies\HardwareEnergyForm;
use App\Models\Hardware\HardwareDevice;
use App\Models\Hardware\HardwareEnergy;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Url;

/**
 * **Todo lo de un aparato en una sola página.**
 *
 * Arriba, quién es: su foto y su nombre, para saber sobre qué se está tocando.
 * Debajo, **una pestaña por cada fila de `hardware_energy`** —no una por papel—,
 * y dentro de cada una su configuración editable y sus tres tablas de
 * telemetría: lecturas, resúmenes diarios e histórico.
 *
 * Es una página propia y no una `EditRecord` de Filament porque lo que se edita
 * no es el aparato, sino **el elemento que esté seleccionado**. El aparato sólo
 * se enseña, y se edita donde vive: en Hardware.
 *
 * Los cuadros de telemetría son los mismos relation managers que usa la ficha
 * de un elemento; se montan aquí colgando del elemento activo. Tenerlos
 * duplicados sería la forma de que acaben diciendo cosas distintas.
 */
class ManageEnergyDevice extends Page
{
    use InteractsWithRecord;

    protected static string $resource = EnergyDeviceResource::class;

    protected string $view = 'filament.admin.pages.manage-energy-device';

    /**
     * Qué elemento se está mirando.
     *
     * Va en la URL para que una pestaña del navegador reabierta caiga donde
     * estaba, y para poder pasar un enlace a un canal concreto.
     */
    #[Url(as: 'elemento')]
    public ?int $elementoId = null;

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);

        $this->elementoId = $this->elementoValido($this->elementoId);

        $this->rellenaFormulario();
    }

    public function getTitle(): string
    {
        return $this->aparato()->display_name;
    }

    public function getBreadcrumb(): string
    {
        return $this->aparato()->display_name;
    }

    // ────────────────────────────── El aparato ──────────────────────────

    public function aparato(): HardwareDevice
    {
        /** @var HardwareDevice $aparato */
        $aparato = $this->getRecord();

        return $aparato;
    }

    /**
     * Los cuatro datos que hacen falta para saber dónde estás. No se editan
     * aquí: el aparato se gestiona en Hardware.
     *
     * @return array<string, string>
     */
    public function senasDelAparato(): array
    {
        $aparato = $this->aparato();

        return array_filter([
            'Tipo' => (string) $aparato->getRelationValue('type')?->name,
            'Marca y modelo' => trim((string) $aparato->brand.' '.(string) $aparato->model),
            'Zona' => (string) $aparato->zone,
            'Última señal' => $aparato->last_seen_at?->diffForHumans() ?? '',
        ], static fn (string $valor): bool => $valor !== '');
    }

    // ───────────────────────────── Los elementos ────────────────────────

    /**
     * Una fila de `hardware_energy` es una pestaña.
     *
     * @return Collection<int, HardwareEnergy>
     */
    public function elementos(): Collection
    {
        /** @var Collection<int, HardwareEnergy> $elementos */
        $elementos = $this->aparato()
            ->hardwareEnergy()
            ->with('monitorized')
            // El orden en que la energía atraviesa la instalación: entra por el
            // generador, se guarda en la batería y sale por los consumos.
            ->orderByRaw("CASE role WHEN 'generator' THEN 0 WHEN 'battery' THEN 1 ELSE 2 END")
            ->orderBy('sensor_position')
            ->get();

        return $elementos;
    }

    public function elementoActivo(): ?HardwareEnergy
    {
        if ($this->elementoId === null) {
            return null;
        }

        return $this->elementos()->firstWhere('id', $this->elementoId);
    }

    /**
     * Cómo se llama cada pestaña: el papel y, si hay varios consumos, el canal.
     * Con un solo consumo el canal sobra y sólo mete ruido.
     */
    public function etiquetaDe(HardwareEnergy $elemento): string
    {
        $etiqueta = HardwareEnergy::ROLE_LABELS[$elemento->role] ?? (string) $elemento->role;

        $hayVarios = $this->elementos()->where('role', $elemento->role)->count() > 1;

        return $hayVarios
            ? $etiqueta.' · canal '.$elemento->sensor_position
            : $etiqueta;
    }

    public function colorDe(HardwareEnergy $elemento): string
    {
        return match ($elemento->role) {
            HardwareEnergy::ROLE_GENERATOR => 'success',
            HardwareEnergy::ROLE_BATTERY => 'warning',
            default => 'info',
        };
    }

    /**
     * Cambiar de pestaña recarga el formulario con lo del elemento nuevo.
     */
    public function updatedElementoId(): void
    {
        $this->elementoId = $this->elementoValido($this->elementoId);

        $this->rellenaFormulario();
    }

    /**
     * El primero si el que viene no vale: una URL a mano, o el elemento que se
     * acaba de borrar.
     */
    private function elementoValido(?int $candidato): ?int
    {
        $elementos = $this->elementos();

        if ($candidato !== null && $elementos->contains('id', $candidato)) {
            return $candidato;
        }

        return $elementos->first()?->id;
    }

    // ───────────────────────── La configuración ─────────────────────────

    public function configuracion(Schema $schema): Schema
    {
        $elemento = $this->elementoActivo();

        return HardwareEnergyForm::forSameMeter($schema)
            ->model($elemento ?? HardwareEnergy::class)
            ->statePath('data')
            ->disabled($elemento === null);
    }

    private function rellenaFormulario(): void
    {
        // El esquema se cachea atado al modelo del elemento que estuviera
        // activo, así que al cambiar de pestaña hay que tirarlo y rehacerlo: si
        // no, se guardaría sobre el elemento anterior.
        $this->cacheSchema('configuracion', null);

        $this->getSchema('configuracion')?->fill($this->elementoActivo()?->attributesToArray() ?? []);
    }

    /**
     * La miniatura del aparato, si la tiene. **No se edita aquí**: está para
     * reconocer el cacharro de un vistazo.
     */
    public function miniatura(): ?string
    {
        return $this->aparato()->getRelationValue('image')?->thumbnail('small');
    }

    public function guardar(): void
    {
        $elemento = $this->elementoActivo();

        if ($elemento === null) {
            return;
        }

        $datos = $this->getSchema('configuracion')?->getState() ?? [];

        $elemento->update($datos);

        Notification::make()
            ->title('Configuración guardada')
            ->success()
            ->send();
    }

    // ──────────────────────────── Dar de alta ───────────────────────────

    /**
     * Un botón por papel que aún quepa.
     *
     * De generador y de batería hay **uno** por aparato; de consumo, tantos
     * como canales mida el medidor. Es el requisito con el que se diseñó el
     * módulo y vive en {@see HardwareEnergy::LIMIT_PER_ROLE}.
     *
     * @return array<Action>
     */
    protected function getHeaderActions(): array
    {
        $acciones = [];

        foreach (HardwareEnergy::ROLES as $papel) {
            $acciones[] = Action::make('crear_'.$papel)
                ->label('Añadir '.mb_strtolower(HardwareEnergy::ROLE_LABELS[$papel]))
                ->icon(match ($papel) {
                    HardwareEnergy::ROLE_GENERATOR => 'heroicon-o-sun',
                    HardwareEnergy::ROLE_BATTERY => 'heroicon-o-battery-50',
                    default => 'heroicon-o-bolt',
                })
                ->color($this->colorDe(new HardwareEnergy(['role' => $papel])))
                // El esqueleto del modal cuelga de la página, cuyo registro es el
                // aparato; sin decirle que el modelo es el elemento, los selects con
                // `relationship()` buscan la relación en `HardwareDevice` y revientan.
                ->schema(fn (Schema $schema): Schema => HardwareEnergyForm::forADevice($schema)
                    ->model(HardwareEnergy::class))
                ->modalHeading(HardwareEnergy::ROLE_LABELS[$papel].' de este aparato')
                ->modalSubmitActionLabel('Crear')
                ->visible(fn (): bool => $this->cabeOtro($papel))
                ->action(function (array $data) use ($papel): void {
                    /** @var HardwareEnergy $elemento */
                    $elemento = $this->aparato()->hardwareEnergy()->create([
                        ...$data,
                        'role' => $papel,
                        // Lo que se da de alta aquí se mide a sí mismo. Para
                        // medir OTRO aparato está Elementos de energía.
                        'hardware_device_monitorized_id' => $data['hardware_device_monitorized_id']
                            ?? $this->aparato()->getKey(),
                    ]);

                    $this->elementoId = (int) $elemento->getKey();
                    $this->rellenaFormulario();
                });
        }

        return $acciones;
    }

    private function cabeOtro(string $papel): bool
    {
        $limite = HardwareEnergy::LIMIT_PER_ROLE[$papel] ?? null;

        if ($limite === null) {
            return true;
        }

        return $this->elementos()->where('role', $papel)->count() < $limite;
    }
}
