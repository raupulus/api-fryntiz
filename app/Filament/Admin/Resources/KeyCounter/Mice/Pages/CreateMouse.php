<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\KeyCounter\Mice\Pages;

use App\Filament\Admin\Resources\KeyCounter\Mice\MouseResource;
use Filament\Resources\Pages\CreateRecord;

class CreateMouse extends CreateRecord
{
    protected static string $resource = MouseResource::class;
}
