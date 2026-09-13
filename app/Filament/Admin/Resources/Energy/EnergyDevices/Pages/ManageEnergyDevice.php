<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Energy\EnergyDevices\Pages;

use App\Filament\Admin\Resources\Energy\EnergyDevices\EnergyDeviceResource;
use App\Filament\Admin\Resources\Hardware\HardwareDevices\HardwareDeviceResource;
use App\Models\Hardware\HardwareDevice;
use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Schema;

/**
 * La ficha energética de un aparato: **todo lo suyo en una pantalla**.
 *
 * Arriba, sus datos y su configuración, editables. Debajo, una pestaña por
 * papel —generador, batería, consumos— con los elementos de cada uno.
 *
 * **Por qué es una página de edición y no de sólo lectura.** Filament pone los
 * relation managers en sólo lectura cuando cuelgan de una `ViewRecord`
 * (`hasReadOnlyRelationManagersOnResourceViewPagesByDefault`), así que la
 * versión anterior de esta pantalla no dejaba dar de alta ni un consumo más:
 * los botones simplemente no se pintaban. Y aquí se viene precisamente a
 * configurar.
 *
 * **El título es el nombre del aparato.** Antes ponía «Ver Aparato» y una
 * sección llamada «El aparato»: volver a una pestaña abierta no decía sobre qué
 * cacharro estabas tocando.
 *
 * El formulario es el mismo de Hardware ({@see HardwareDeviceResource::form()}),
 * no una copia: dos formularios del mismo modelo acaban diciendo cosas
 * distintas.
 */
class ManageEnergyDevice extends EditRecord
{
    protected static string $resource = EnergyDeviceResource::class;

    public function getTitle(): string
    {
        /** @var HardwareDevice $aparato */
        $aparato = $this->getRecord();

        return $aparato->display_name;
    }

    public function getSubheading(): ?string
    {
        /** @var HardwareDevice $aparato */
        $aparato = $this->getRecord();

        $partes = array_filter([
            $aparato->getRelationValue('type')?->name,
            $aparato->brand,
            $aparato->model,
            $aparato->zone,
        ]);

        return $partes === [] ? null : implode(' · ', $partes);
    }

    public function form(Schema $schema): Schema
    {
        return HardwareDeviceResource::form($schema);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('ficha')
                ->label('Ficha en Hardware')
                ->icon('heroicon-o-cpu-chip')
                ->color('gray')
                ->url(fn (): string => HardwareDeviceResource::getUrl('edit', ['record' => $this->getRecord()])),
        ];
    }

    /**
     * Tras guardar se vuelve a la misma ficha: se está configurando este
     * aparato, no dando de alta uno y pasando al siguiente.
     */
    protected function getRedirectUrl(): ?string
    {
        return null;
    }
}
