<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Printers\Pages;

use App\Filament\Admin\Resources\Printers\PrinterResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePrinter extends CreateRecord
{
    protected static string $resource = PrinterResource::class;
}
