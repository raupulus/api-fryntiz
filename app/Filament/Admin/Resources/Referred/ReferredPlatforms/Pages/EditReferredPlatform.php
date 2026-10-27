<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Referred\ReferredPlatforms\Pages;

use App\Filament\Admin\Resources\Referred\ReferredPlatforms\ReferredPlatformResource;
use App\Filament\Concerns\HasImageFileUpload;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditReferredPlatform extends EditRecord
{
    use HasImageFileUpload;

    protected static string $resource = ReferredPlatformResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        return $this->resolveImageUpload($data, 'image_id', 'referred_platforms', webpOriginal: true);
    }
}
