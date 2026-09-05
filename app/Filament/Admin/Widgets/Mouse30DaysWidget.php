<?php

declare(strict_types=1);

namespace App\Filament\Admin\Widgets;

use App\Models\KeyCounter\Mouse;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class Mouse30DaysWidget extends BaseWidget
{
    /**
     * Actividad de ratón agregada de todos los usuarios, sin filtrar por dueño
     * (AR-SEC-04).
     */
    public static function canView(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    protected int|string|array $columnSpan = 1;

    protected static ?int $sort = 5;

    protected function getStats(): array
    {
        $clicks = (float) (Mouse::where('start_at', '>=', now()->subDays(30))->sum('total_clicks') ?? 0);

        return [
            Stat::make('Clicks de Ratón (Últimos 30 días)', number_format($clicks))
                ->description('Total de clicks de ratón registrados')
                ->descriptionIcon('heroicon-m-cursor-arrow-rays')
                ->color('success'),
        ];
    }
}
