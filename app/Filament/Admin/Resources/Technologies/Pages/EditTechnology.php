<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Technologies\Pages;

use App\Filament\Admin\Resources\Technologies\TechnologyResource;
use App\Filament\Concerns\HasImageFileUpload;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditTechnology extends EditRecord
{
    use HasImageFileUpload;

    protected static string $resource = TechnologyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    /**
     * Convierte la imagen subida en un registro de `files` y guarda su id (N232).
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        return $this->resolveImageUpload($data, 'image_id', 'technologies');
    }
}
