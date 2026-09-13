<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Energy\EnergyDevices\Pages;

use App\Filament\Admin\Resources\Energy\EnergyDevices\EnergyDeviceResource;
use App\Filament\Admin\Resources\Hardware\HardwareDevices\HardwareDeviceResource;
use Filament\Actions\Action;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

/**
 * La ficha energética de un aparato: sus papeles, todos juntos.
 *
 * Lo que se gestiona aquí son los papeles, no el aparato. Para lo demás —su
 * nombre, su tipo, sus tokens— hay un botón que lleva a su ficha de Hardware,
 * que es donde vive.
 */
class ViewEnergyDevice extends ViewRecord
{
    protected static string $resource = EnergyDeviceResource::class;

    public function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('El aparato')
                ->columns(3)
                ->schema([
                    TextEntry::make('name')->label('Nombre'),
                    TextEntry::make('type.name')->label('Tipo')->placeholder('sin tipo'),
                    TextEntry::make('hardwareEnergy_count')
                        ->label('Papeles dados de alta')
                        ->state(fn ($record): int => $record->hardwareEnergy()->count()),
                ]),
        ]);
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
}
