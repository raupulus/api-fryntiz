<?php

declare(strict_types=1);

namespace App\Services\AirFlight;

use App\Models\AirFlight\AirFlightAirPlane;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class AirFlightService
{
    public function addAircraft(array $data): AirFlightAirPlane
    {
        return AirFlightAirPlane::create($data);
    }

    public function addAircraftBatch(array $records): array
    {
        $stored = [];
        foreach ($records as $record) {
            $stored[] = AirFlightAirPlane::create($record);
        }

        return $stored;
    }

    public function getAircraftHistory(int $perPage = 50): LengthAwarePaginator
    {
        return AirFlightAirPlane::with('routes')
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }
}
