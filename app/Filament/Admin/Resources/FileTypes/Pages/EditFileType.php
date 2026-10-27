<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\FileTypes\Pages;

use App\Filament\Admin\Resources\FileTypes\FileTypeResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Enums\Width;

class EditFileType extends EditRecord
{
    protected static string $resource = FileTypeResource::class;

    public function getMaxContentWidth(): Width|string|null
    {
        return Width::Full;
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
