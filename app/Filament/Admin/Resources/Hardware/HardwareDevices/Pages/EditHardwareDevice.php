<?php

namespace App\Filament\Admin\Resources\Hardware\HardwareDevices\Pages;

use App\Filament\Admin\Resources\Hardware\HardwareDevices\HardwareDeviceResource;
use App\Filament\Concerns\HasImageFileUpload;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditHardwareDevice extends EditRecord
{
    use HasImageFileUpload;

    protected static string $resource = HardwareDeviceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // El campo de subida no puede representar el id de la FK existente.
        // Se deja vacío; si no se sube nada nuevo, se conserva el valor actual.
        unset($data['image_id']);

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        return $this->resolveImageUpload($data, 'image_id', 'hardware-devices');
    }
}
