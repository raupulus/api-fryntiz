<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Referred\ReferredPlatforms\Pages;

use App\Filament\Admin\Resources\Referred\ReferredPlatforms\ReferredPlatformResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListReferredPlatforms extends ListRecords
{
    protected static string $resource = ReferredPlatformResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
