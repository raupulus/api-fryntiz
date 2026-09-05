<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\CV\Curriculum\Pages;

use App\Filament\Admin\Resources\CV\Curriculum\CurriculumResource;
use App\Filament\Concerns\HasImageFileUpload;
use Filament\Resources\Pages\CreateRecord;

class CreateCurriculum extends CreateRecord
{
    use HasImageFileUpload;

    protected static string $resource = CurriculumResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return $this->resolveImageUpload($data, 'image_id', 'curriculum');
    }
}
