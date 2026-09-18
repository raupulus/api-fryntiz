<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Referred\ReferredPlatforms\Pages;

use App\Filament\Admin\Resources\Referred\ReferredPlatforms\ReferredPlatformResource;
use Filament\Resources\Pages\CreateRecord;

class CreateReferredPlatform extends CreateRecord
{
    protected static string $resource = ReferredPlatformResource::class;
}
