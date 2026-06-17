<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\KeyCounter\Mice\Pages;

use App\Filament\Admin\Resources\KeyCounter\Mice\MouseResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMice extends ListRecords
{
    protected static string $resource = MouseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
