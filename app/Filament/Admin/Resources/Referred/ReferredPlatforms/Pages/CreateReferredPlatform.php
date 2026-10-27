<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Referred\ReferredPlatforms\Pages;

use App\Filament\Admin\Resources\Referred\ReferredPlatforms\ReferredPlatformResource;
use App\Filament\Concerns\HasImageFileUpload;
use Filament\Resources\Pages\CreateRecord;

class CreateReferredPlatform extends CreateRecord
{
    use HasImageFileUpload;

    protected static string $resource = ReferredPlatformResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return $this->resolveImageUpload($data, 'image_id', 'referred_platforms', webpOriginal: true);
    }
}
