<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Hardware\HardwareDevices\Pages;

use App\Filament\Admin\Resources\Hardware\HardwareDevices\HardwareDeviceResource;
use App\Filament\Concerns\HasImageFileUpload;
use Filament\Resources\Pages\CreateRecord;

class CreateHardwareDevice extends CreateRecord
{
    use HasImageFileUpload;

    protected static string $resource = HardwareDeviceResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return $this->resolveImageUpload($data, 'image_id', 'hardware-devices');
    }
}
