<?php

namespace App\Filament\Admin\Resources\CV\CurriculumAvailableRepositoryTypes\Pages;

use App\Filament\Admin\Resources\CV\CurriculumAvailableRepositoryTypes\CurriculumAvailableRepositoryTypeResource;
use App\Filament\Concerns\HasImageFileUpload;
use Filament\Resources\Pages\CreateRecord;

class CreateCurriculumAvailableRepositoryType extends CreateRecord
{
    use HasImageFileUpload;

    protected static string $resource = CurriculumAvailableRepositoryTypeResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return $this->resolveImageUpload($data, 'image_id', 'cv-repository-types');
    }
}
