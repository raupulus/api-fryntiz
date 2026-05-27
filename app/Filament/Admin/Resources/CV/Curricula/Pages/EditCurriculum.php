<?php

namespace App\Filament\Admin\Resources\CV\Curricula\Pages;

use App\Filament\Admin\Resources\CV\Curricula\CurriculumResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCurriculum extends EditRecord
{
    protected static string $resource = CurriculumResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
