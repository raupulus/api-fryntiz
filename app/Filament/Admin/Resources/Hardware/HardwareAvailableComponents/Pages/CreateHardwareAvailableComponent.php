<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Hardware\HardwareAvailableComponents\Pages;

use App\Filament\Admin\Resources\Hardware\HardwareAvailableComponents\HardwareAvailableComponentResource;
use Filament\Resources\Pages\CreateRecord;

class CreateHardwareAvailableComponent extends CreateRecord
{
    protected static string $resource = HardwareAvailableComponentResource::class;
}
