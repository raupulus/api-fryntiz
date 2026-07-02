<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Content\Contents\Pages;

use App\Filament\Admin\Resources\Content\Contents\ContentResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListContents extends ListRecords
{
    protected static string $resource = ContentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // ContentResource tiene página de creación propia (no modal), así que
            // CreateAction navega a esa URL: hay que propagar la plataforma
            // filtrada como query string para que el formulario la preseleccione.
            CreateAction::make()
                ->url(fn (): string => ContentResource::getUrl('create', array_filter([
                    'platform_id' => $this->tableFilters['platform_id']['value'] ?? null,
                ]))),
        ];
    }
}
