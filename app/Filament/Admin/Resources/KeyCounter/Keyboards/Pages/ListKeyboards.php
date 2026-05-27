<?php

namespace App\Filament\Admin\Resources\KeyCounter\Keyboards\Pages;

use App\Filament\Admin\Resources\KeyCounter\Keyboards\KeyboardResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListKeyboards extends ListRecords
{
    protected static string $resource = KeyboardResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
