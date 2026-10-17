<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\AiProviders\Pages;

use App\Filament\Admin\Resources\AiProviders\AiProviderResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAiProviders extends ListRecords
{
    protected static string $resource = AiProviderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
