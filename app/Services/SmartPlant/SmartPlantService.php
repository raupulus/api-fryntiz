<?php

namespace App\Services\SmartPlant;

use App\Models\SmartPlant\SmartPlantPlant;
use App\Models\SmartPlant\SmartPlantRegister;
use Illuminate\Database\Eloquent\Collection;

class SmartPlantService
{
    public function storeRegister(array $data): SmartPlantRegister
    {
        return SmartPlantRegister::create($data);
    }

    public function getUserPlants(int $userId): Collection
    {
        return SmartPlantPlant::forUser($userId)
            ->with(['registers' => fn ($q) => $q->latest()->limit(1)])
            ->get();
    }
}
