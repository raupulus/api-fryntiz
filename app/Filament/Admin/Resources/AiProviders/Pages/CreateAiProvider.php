<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\AiProviders\Pages;

use App\Filament\Admin\Resources\AiProviders\AiProviderResource;
use Filament\Resources\Pages\CreateRecord;

class CreateAiProvider extends CreateRecord
{
    protected static string $resource = AiProviderResource::class;
}
