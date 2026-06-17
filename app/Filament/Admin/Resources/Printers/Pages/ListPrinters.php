<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Printers\Pages;

use App\Filament\Admin\Resources\Printers\PrinterResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPrinters extends ListRecords
{
    protected static string $resource = PrinterResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
