<?php

namespace App\Filament\Admin\Clusters;

use BackedEnum;
use Filament\Clusters\Cluster;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Support\Icons\Heroicon;

/**
 * Cluster que agrupa los recursos de Energy en el panel de administración.
 */
class Energy extends Cluster
{
    protected static ?string $clusterBreadcrumb = 'Energy';

    protected static ?string $title = 'Energy';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBolt;

    protected static string|\UnitEnum|null $navigationGroup = 'Módulos';

    protected static ?int $navigationSort = 70;

    protected static ?SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
}
