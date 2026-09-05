<?php

declare(strict_types=1);

namespace App\Filament\Admin\Widgets;

use App\Models\KeyCounter\Keyboard;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class Keyboard30DaysWidget extends BaseWidget
{
    /**
     * Actividad de tecleo agregada de todos los usuarios, sin filtrar por dueño:
     * revela hábitos y horarios ajenos (AR-SEC-04).
     */
    public static function canView(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    protected int|string|array $columnSpan = 1;

    protected static ?int $sort = 4;

    protected function getStats(): array
    {
        $pulsations = (float) (Keyboard::where('start_at', '>=', now()->subDays(30))->sum('pulsations') ?? 0);

        return [
            Stat::make('Pulsaciones de Teclas (Últimos 30 días)', number_format($pulsations))
                ->description('Total de pulsaciones de teclado registradas')
                ->descriptionIcon('heroicon-m-pencil-square')
                ->color('primary'),
        ];
    }
}
