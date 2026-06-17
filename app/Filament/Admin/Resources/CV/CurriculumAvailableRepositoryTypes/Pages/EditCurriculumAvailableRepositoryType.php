<?php

namespace App\Filament\Admin\Resources\CV\CurriculumAvailableRepositoryTypes\Pages;

use App\Filament\Admin\Resources\CV\CurriculumAvailableRepositoryTypes\CurriculumAvailableRepositoryTypeResource;
use App\Filament\Concerns\HasImageFileUpload;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCurriculumAvailableRepositoryType extends EditRecord
{
    use HasImageFileUpload;

    protected static string $resource = CurriculumAvailableRepositoryTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        unset($data['image_id']);

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        return $this->resolveImageUpload($data, 'image_id', 'cv-repository-types');
    }
}
