<?php

namespace App\Filament\Admin\Resources\KeyCounter\Keyboards\Pages;

use App\Filament\Admin\Resources\KeyCounter\Keyboards\KeyboardResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditKeyboard extends EditRecord
{
    protected static string $resource = KeyboardResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
