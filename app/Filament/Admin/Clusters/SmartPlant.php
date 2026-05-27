<?php

namespace App\Filament\Admin\Clusters;

use BackedEnum;
use Filament\Clusters\Cluster;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Support\Icons\Heroicon;

/**
 * Cluster que agrupa los recursos de Smart Plant en el panel de administración.
 */
class SmartPlant extends Cluster
{
    protected static ?string $clusterBreadcrumb = 'Smart Plant';

    protected static ?string $title = 'Smart Plant';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSparkles;

    protected static string|\UnitEnum|null $navigationGroup = 'Módulos';

    protected static ?int $navigationSort = 60;

    protected static ?SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
}
