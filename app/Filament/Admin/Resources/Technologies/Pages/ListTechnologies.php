<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Technologies\Pages;

use App\Filament\Admin\Resources\Technologies\TechnologyResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTechnologies extends ListRecords
{
    protected static string $resource = TechnologyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
