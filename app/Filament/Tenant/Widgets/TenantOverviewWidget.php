<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Widgets;

use App\Filament\Tenant\Pages\EditProfile;
use App\Filament\Tenant\Resources\ApiTokens\ApiTokenResource;
use App\Models\ApiToken;
use App\Models\Hardware\HardwareDevice;
use App\Models\User;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;

/**
 * Resumen rápido de la cuenta del usuario en el panel `/panel`: tokens
 * activos, dispositivos propios y acceso directo al perfil.
 */
class TenantOverviewWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected static bool $isLazy = false;

    protected function getStats(): array
    {
        /** @var User|null $user */
        $user = auth()->user();

        if ($user === null) {
            return [];
        }

        $activeTokens = ApiToken::query()
            ->where('tokenable_type', User::class)
            ->where('tokenable_id', $user->id)
            ->where(function ($query): void {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->count();

        $devicesCount = HardwareDevice::query()
            ->where('user_id', $user->id)
            ->count();

        $lastSeen = HardwareDevice::query()
            ->where('user_id', $user->id)
            ->whereNotNull('last_seen_at')
            ->latest('last_seen_at')
            ->value('last_seen_at');

        $deviceDescription = $lastSeen !== null
            ? 'Última señal: '.Carbon::parse($lastSeen)->diffForHumans()
            : 'Sin telemetría reciente';

        return [
            Stat::make('Tokens activos', (string) $activeTokens)
                ->description('Gestionar tokens de hardware')
                ->descriptionIcon(Heroicon::OutlinedKey)
                ->color($activeTokens > 0 ? 'success' : 'gray')
                ->url(ApiTokenResource::getUrl('index')),

            Stat::make('Mis dispositivos', (string) $devicesCount)
                ->description($deviceDescription)
                ->descriptionIcon(Heroicon::OutlinedCpuChip)
                ->color($devicesCount > 0 ? 'info' : 'gray'),

            Stat::make('Mi cuenta', $user->name)
                ->description('Editar datos personales y contraseña')
                ->descriptionIcon(Heroicon::OutlinedUserCircle)
                ->color('primary')
                ->url(EditProfile::getUrl()),
        ];
    }
}
