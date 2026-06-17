<?php

declare(strict_types=1);

namespace App\Filament\Admin\Pages;

use App\Filament\Admin\Clusters\AirFlight;
use App\Models\AirFlight\AirFlightRoute;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

/**
 * Página con mapa Leaflet mostrando las últimas posiciones de vuelos detectados.
 */
class AirFlightMap extends Page
{
    protected static ?string $cluster = AirFlight::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMap;

    protected static ?string $title = 'Mapa de vuelos';

    protected static ?string $slug = 'air-flight-map';

    protected static ?int $navigationSort = 0;

    protected string $view = 'filament.admin.pages.air-flight-map';

    public function getViewData(): array
    {
        $cutoff = now()->subMinutes(15);
        $latestPerPlane = AirFlightRoute::query()
            ->whereNotNull('lat')->whereNotNull('lon')
            ->where('seen_at', '>=', $cutoff)
            ->with('airplane:id,icao,country,category')
            ->orderByDesc('seen_at')
            ->get()
            ->unique('airplane_id')
            ->values();

        return [
            'planes' => $latestPerPlane->map(fn ($r) => [
                'icao' => $r->airplane?->icao,
                'flight' => $r->flight,
                'lat' => $r->lat, 'lon' => $r->lon,
                'altitude' => $r->altitude, 'track' => $r->track,
                'speed' => $r->speed, 'seen_at' => $r->seen_at?->toIso8601String(),
            ]),
        ];
    }
}
