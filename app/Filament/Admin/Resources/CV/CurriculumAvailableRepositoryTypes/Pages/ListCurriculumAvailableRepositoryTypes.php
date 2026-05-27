<?php

namespace App\Filament\Admin\Resources\CV\CurriculumAvailableRepositoryTypes\Pages;

use App\Filament\Admin\Resources\CV\CurriculumAvailableRepositoryTypes\CurriculumAvailableRepositoryTypeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCurriculumAvailableRepositoryTypes extends ListRecords
{
    protected static string $resource = CurriculumAvailableRepositoryTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
