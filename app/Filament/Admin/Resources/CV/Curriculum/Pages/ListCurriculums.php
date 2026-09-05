<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\CV\Curriculum\Pages;

use App\Filament\Admin\Resources\CV\Curriculum\CurriculumResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCurriculums extends ListRecords
{
    protected static string $resource = CurriculumResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
