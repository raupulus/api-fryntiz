<?php

declare(strict_types=1);

namespace App\Filament\Admin\Clusters;

use BackedEnum;
use Filament\Clusters\Cluster;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Support\Icons\Heroicon;

/**
 * Cluster que agrupa los recursos de Airflights en el panel de administración.
 */
class AirFlight extends Cluster
{
    /**
     * El cluster agrupa infraestructura, no contenido. Sin esto, quien entra al
     * panel a escribir veía en la navegación los módulos de energía, vuelos,
     * KeyCounter y SmartPlant enteros (AR-SEC-04).
     */
    public static function canAccess(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    protected static ?string $clusterBreadcrumb = 'Airflights';

    protected static ?string $title = 'Airflights';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPaperAirplane;

    protected static string|\UnitEnum|null $navigationGroup = 'Módulos';

    protected static ?int $navigationSort = 50;

    protected static ?SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
}
