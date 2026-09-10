<?php

declare(strict_types=1);

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

    protected function mutateFormDataBeforeSave(array $data): array
    {
        return $this->resolveImageUpload($data, 'image_id', 'hardware-devices');
    }
}
