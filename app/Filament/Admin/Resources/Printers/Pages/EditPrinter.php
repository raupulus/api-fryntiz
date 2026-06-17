<?php

namespace App\Filament\Admin\Resources\Printers\Pages;

use App\Filament\Admin\Resources\Printers\PrinterResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPrinter extends EditRecord
{
    protected static string $resource = PrinterResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
