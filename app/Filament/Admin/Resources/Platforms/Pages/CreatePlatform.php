<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Platforms\Pages;

use App\Filament\Admin\Resources\Platforms\PlatformResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePlatform extends CreateRecord
{
    protected static string $resource = PlatformResource::class;
}
