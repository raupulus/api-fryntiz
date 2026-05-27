<?php

namespace App\Filament\Admin\Resources\CV\Curricula\Pages;

use App\Filament\Admin\Resources\CV\Curricula\CurriculumResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCurricula extends ListRecords
{
    protected static string $resource = CurriculumResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
