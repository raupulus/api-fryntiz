<?php

namespace App\Filament\Admin\Resources\KeyCounter\Mice\Pages;

use App\Filament\Admin\Resources\KeyCounter\Mice\MouseResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditMouse extends EditRecord
{
    protected static string $resource = MouseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
