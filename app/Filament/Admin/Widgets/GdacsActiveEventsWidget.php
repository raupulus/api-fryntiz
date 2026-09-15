<?php

declare(strict_types=1);

namespace App\Filament\Admin\Widgets;

use App\Filament\Admin\Resources\Gdacs\GdacsEvents\GdacsEventResource;
use App\Models\Gdacs\GdacsEvent;
use Filament\Widgets\Widget;

/**
 * Tarjeta visual arriba de la tabla de {@see GdacsEventResource}
 * con lo que GDACS marca como activo ahora mismo dentro del radio. No se
 * monta si no hay nada activo: sin esto habría un hueco vacío la inmensa
 * mayoría de los días.
 */
class GdacsActiveEventsWidget extends Widget
{
    protected string $view = 'filament.admin.widgets.gdacs-active-events-widget';

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return (auth()->user()?->isAdmin() ?? false) && GdacsEvent::query()->active()->exists();
    }

    protected function getViewData(): array
    {
        return [
            'events' => GdacsEvent::query()->active()->orderBy('distance_km')->get(),
        ];
    }
}
