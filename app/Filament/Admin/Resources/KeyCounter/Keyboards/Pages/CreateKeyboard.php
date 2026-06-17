<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\KeyCounter\Keyboards\Pages;

use App\Filament\Admin\Resources\KeyCounter\Keyboards\KeyboardResource;
use Filament\Resources\Pages\CreateRecord;

class CreateKeyboard extends CreateRecord
{
    protected static string $resource = KeyboardResource::class;
}
