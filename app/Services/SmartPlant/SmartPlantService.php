<?php

namespace App\Services\SmartPlant;

use App\Models\SmartPlant\SmartPlantPlant;
use App\Models\SmartPlant\SmartPlantRegister;

class SmartPlantService
{
    public function storeRegister(array $data): SmartPlantRegister
    {
        return SmartPlantRegister::create($data);
    }

    public function getUserPlants(int $userId): \Illuminate\Database\Eloquent\Collection
    {
        return SmartPlantPlant::forUser($userId)
            ->with(['registers' => fn ($q) => $q->latest()->limit(1)])
            ->get();
    }
}
