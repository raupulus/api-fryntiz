<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Referred\ReferredPlatforms\Pages;

use App\Filament\Admin\Resources\Referred\ReferredPlatforms\ReferredPlatformResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditReferredPlatform extends EditRecord
{
    protected static string $resource = ReferredPlatformResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
