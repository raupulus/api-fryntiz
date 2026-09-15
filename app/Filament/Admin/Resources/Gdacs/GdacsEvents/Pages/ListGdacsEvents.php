<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Gdacs\GdacsEvents\Pages;

use App\Filament\Admin\Resources\Gdacs\GdacsEvents\GdacsEventResource;
use App\Filament\Admin\Widgets\GdacsActiveEventsWidget;
use Filament\Resources\Pages\ListRecords;

class ListGdacsEvents extends ListRecords
{
    protected static string $resource = GdacsEventResource::class;

    /**
     * Solo lectura: `gdacs:sync` es el único que escribe. Nada de "Nuevo" aquí.
     */
    protected function getHeaderActions(): array
    {
        return [];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            GdacsActiveEventsWidget::class,
        ];
    }
}
