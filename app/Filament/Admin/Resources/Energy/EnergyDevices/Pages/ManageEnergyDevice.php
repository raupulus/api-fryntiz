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
    #[Url(as: 'element')]
    public ?int $elementId = null;

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);

        $this->elementId = $this->resolveValidElement($this->elementId);

        $this->fillConfigurationForm();
    }

    public function getTitle(): string
    {
        return $this->device()->display_name;
    }

    public function getBreadcrumb(): string
    {
        return $this->device()->display_name;
    }

    // ────────────────────────────── El aparato ──────────────────────────

    public function device(): HardwareDevice
    {
        /** @var HardwareDevice $device */
        $device = $this->getRecord();

        return $device;
    }

    /**
     * Los cuatro datos que hacen falta para saber dónde estás. No se editan
     * aquí: el aparato se gestiona en Hardware.
     *
     * @return array<string, string>
     */
    public function deviceDetails(): array
    {
        $device = $this->device();

        return array_filter([
            'Tipo' => (string) $device->getRelationValue('type')?->name,
            'Marca y modelo' => trim((string) $device->brand.' '.(string) $device->model),
            'Zona' => (string) $device->zone,
            'Última señal' => $device->last_seen_at?->diffForHumans() ?? '',
        ], static fn (string $value): bool => $value !== '');
    }

    // ───────────────────────────── Los elementos ────────────────────────

    /**
     * Una fila de `hardware_energy` es una pestaña.
     *
     * @return Collection<int, HardwareEnergy>
     */
    public function elements(): Collection
    {
        /** @var Collection<int, HardwareEnergy> $elements */
        $elements = $this->device()
            ->hardwareEnergy()
            ->with('monitorized')
            // El orden en que la energía atraviesa la instalación: entra por el
            // generador, se guarda en la batería y sale por los consumos.
            ->orderByRaw("CASE role WHEN 'generator' THEN 0 WHEN 'battery' THEN 1 ELSE 2 END")
            ->orderBy('sensor_position')
            ->get();

        return $elements;
    }

    public function activeElement(): ?HardwareEnergy
    {
        if ($this->elementId === null) {
            return null;
        }

        return $this->elements()->firstWhere('id', $this->elementId);
    }

    /**
     * Cómo se llama cada pestaña: el papel y, si hay varios consumos, el canal.
     * Con un solo consumo el canal sobra y sólo mete ruido.
     */
    public function labelFor(HardwareEnergy $element): string
    {
        $label = HardwareEnergy::ROLE_LABELS[$element->role] ?? (string) $element->role;

        $hasMultiple = $this->elements()->where('role', $element->role)->count() > 1;

        return $hasMultiple
            ? $label.' · canal '.$element->sensor_position
            : $label;
    }

    public function colorFor(HardwareEnergy $element): string
    {
        return match ($element->role) {
            HardwareEnergy::ROLE_GENERATOR => 'success',
            HardwareEnergy::ROLE_BATTERY => 'warning',
            default => 'info',
        };
    }

    /**
     * Cambiar de pestaña recarga el formulario con lo del elemento nuevo.
     */
    public function updatedElementId(): void
    {
        $this->elementId = $this->resolveValidElement($this->elementId);

        $this->fillConfigurationForm();
    }

    /**
     * El primero si el que viene no vale: una URL a mano, o el elemento que se
     * acaba de borrar.
     */
    private function resolveValidElement(?int $candidate): ?int
    {
        $elements = $this->elements();

        if ($candidate !== null && $elements->contains('id', $candidate)) {
            return $candidate;
        }

        return $elements->first()?->id;
    }

    // ───────────────────────── La configuración ─────────────────────────

    public function configuration(Schema $schema): Schema
    {
        $element = $this->activeElement();

        return HardwareEnergyForm::forSameMeter($schema)
            ->model($element ?? HardwareEnergy::class)
            ->statePath('data')
            ->disabled($element === null);
    }

    private function fillConfigurationForm(): void
    {
        // El esquema se cachea atado al modelo del elemento que estuviera
        // activo, así que al cambiar de pestaña hay que tirarlo y rehacerlo: si
        // no, se guardaría sobre el elemento anterior.
        $this->cacheSchema('configuration', null);

        $this->getSchema('configuration')?->fill($this->activeElement()?->attributesToArray() ?? []);
    }

    /**
     * La miniatura del aparato, si la tiene. **No se edita aquí**: está para
     * reconocer el cacharro de un vistazo.
     */
    public function thumbnail(): ?string
    {
        return $this->device()->getRelationValue('image')?->thumbnail('small');
    }

    public function save(): void
    {
        $element = $this->activeElement();

        if ($element === null) {
            return;
        }

        $state = $this->getSchema('configuration')?->getState() ?? [];

        $element->update($state);

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
        $actions = [];

        foreach (HardwareEnergy::ROLES as $role) {
            $actions[] = Action::make('create_'.$role)
                ->label('Añadir '.mb_strtolower(HardwareEnergy::ROLE_LABELS[$role]))
                ->icon(match ($role) {
                    HardwareEnergy::ROLE_GENERATOR => 'heroicon-o-sun',
                    HardwareEnergy::ROLE_BATTERY => 'heroicon-o-battery-50',
                    default => 'heroicon-o-bolt',
                })
                ->color($this->colorFor(new HardwareEnergy(['role' => $role])))
                // El esqueleto del modal cuelga de la página, cuyo registro es el
                // aparato; sin decirle que el modelo es el elemento, los selects con
                // `relationship()` buscan la relación en `HardwareDevice` y revientan.
                ->schema(fn (Schema $schema): Schema => HardwareEnergyForm::forADevice($schema)
                    ->model(HardwareEnergy::class))
                ->modalHeading(HardwareEnergy::ROLE_LABELS[$role].' de este aparato')
                ->modalSubmitActionLabel('Crear')
                ->visible(fn (): bool => $this->canAddRole($role))
                ->action(function (array $data) use ($role): void {
                    /** @var HardwareEnergy $element */
                    $element = $this->device()->hardwareEnergy()->create([
                        ...$data,
                        'role' => $role,
                        // Lo que se da de alta aquí se mide a sí mismo. Para
                        // medir OTRO aparato está Elementos de energía.
                        'hardware_device_monitorized_id' => $data['hardware_device_monitorized_id']
                            ?? $this->device()->getKey(),
                    ]);

                    $this->elementId = (int) $element->getKey();
                    $this->fillConfigurationForm();
                });
        }

        return $actions;
    }

    private function canAddRole(string $role): bool
    {
        $limit = HardwareEnergy::LIMIT_PER_ROLE[$role] ?? null;

        if ($limit === null) {
            return true;
        }

        return $this->elements()->where('role', $role)->count() < $limit;
    }
}
