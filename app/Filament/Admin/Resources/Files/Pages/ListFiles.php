<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Files\Pages;

use App\Filament\Admin\Resources\Files\FileResource;
use Filament\Resources\Pages\ListRecords;

class ListFiles extends ListRecords
{
    protected static string $resource = FileResource::class;
}
