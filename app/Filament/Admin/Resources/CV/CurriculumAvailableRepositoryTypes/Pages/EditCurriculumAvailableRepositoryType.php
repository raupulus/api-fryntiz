<?php

namespace App\Filament\Admin\Resources\CV\CurriculumAvailableRepositoryTypes\Pages;

use App\Filament\Admin\Resources\CV\CurriculumAvailableRepositoryTypes\CurriculumAvailableRepositoryTypeResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCurriculumAvailableRepositoryType extends EditRecord
{
    protected static string $resource = CurriculumAvailableRepositoryTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
