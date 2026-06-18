<?php

declare(strict_types=1);

namespace App\Services\SmartPlant;

use App\Models\SmartPlant\SmartPlantPlant;
use App\Models\SmartPlant\SmartPlantRegister;
use Illuminate\Database\Eloquent\Collection;

/**
 * Servicio de control y métricas para el módulo de Plantas Inteligentes (SmartPlant).
 */
class SmartPlantService
{
    /**
     * Almacena un nuevo registro de métricas de una planta.
     *
     * @param array $data Datos procedentes del sensor (humedad, temperatura, luz, etc).
     * @return \App\Models\SmartPlant\SmartPlantRegister El registro almacenado.
     */
    public function storeRegister(array $data): SmartPlantRegister
    {
        return SmartPlantRegister::create($data);
    }

    /**
     * Obtiene el listado de plantas asociadas a un usuario junto con su último registro de estado.
     *
     * @param int $userId Identificador único del usuario propietario.
     * @return \Illuminate\Database\Eloquent\Collection Colección de plantas del usuario.
     */
    public function getUserPlants(int $userId): Collection
    {
        return SmartPlantPlant::forUser($userId)
            ->with(['registers' => fn ($q) => $q->latest()->limit(1)])
            ->get();
    }
}
