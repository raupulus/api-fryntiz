<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Hardware\EnergySystems\Pages;

use App\Filament\Admin\Resources\Hardware\EnergySystems\EnergySystemResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditEnergySystem extends EditRecord
{
    protected static string $resource = EnergySystemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
