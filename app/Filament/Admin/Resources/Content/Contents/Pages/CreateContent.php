<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Content\Contents\Pages;

use App\Filament\Admin\Resources\Content\Contents\ContentResource;
use Filament\Resources\Pages\CreateRecord;

class CreateContent extends CreateRecord
{
    protected static string $resource = ContentResource::class;
}
