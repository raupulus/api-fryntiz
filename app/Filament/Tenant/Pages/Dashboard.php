<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Pages;

use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-home';

    protected static ?string $title = 'Mi Panel';
}
