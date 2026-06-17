<?php

declare(strict_types=1);

namespace App\Services\Hardware;

use App\Models\Hardware\HardwareDevice;
use App\Models\Hardware\HardwareEnergy;
use App\Models\Hardware\SolarCharge;
use Illuminate\Database\Eloquent\Collection;

class HardwareService
{
    public function getDeviceInfo(int $deviceId): ?HardwareDevice
    {
        return HardwareDevice::with(['type', 'components.availableComponent'])
            ->find($deviceId);
    }

    public function getComputersList(int $userId): Collection
    {
        return HardwareDevice::forUser($userId)->with('type')->get();
    }

    public function storeEnergyData(array $data): HardwareEnergy
    {
        return HardwareEnergy::create($data);
    }

    public function storeSolarCharge(array $data): SolarCharge
    {
        return SolarCharge::create($data);
    }
}
