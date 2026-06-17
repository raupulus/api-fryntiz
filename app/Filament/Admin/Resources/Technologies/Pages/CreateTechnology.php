<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Technologies\Pages;

use App\Filament\Admin\Resources\Technologies\TechnologyResource;
use Filament\Resources\Pages\CreateRecord;

class CreateTechnology extends CreateRecord
{
    protected static string $resource = TechnologyResource::class;
}
