<?php

namespace App\Filament\Admin\Resources\FileTypes\Pages;

use App\Filament\Admin\Resources\FileTypes\FileTypeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListFileTypes extends ListRecords
{
    protected static string $resource = FileTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
