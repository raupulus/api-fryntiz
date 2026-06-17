<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\FileTypes\Pages;

use App\Filament\Admin\Resources\FileTypes\FileTypeResource;
use Filament\Resources\Pages\CreateRecord;

class CreateFileType extends CreateRecord
{
    protected static string $resource = FileTypeResource::class;
}
