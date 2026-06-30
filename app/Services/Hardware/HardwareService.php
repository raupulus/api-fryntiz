<?php

declare(strict_types=1);

namespace App\Services\Hardware;

use App\Models\Hardware\HardwareDevice;
use App\Models\Hardware\HardwareEnergy;
use App\Models\Hardware\SolarCharge;
use Illuminate\Database\Eloquent\Collection;

/**
 * Servicio encargado de la gestión de dispositivos de hardware, monitorización y registros energéticos.
 */
class HardwareService
{
    /**
     * Obtiene la información detallada de un dispositivo de hardware específico,
     * incluyendo su tipo y los componentes instalados.
     *
     * @param  int  $deviceId  Identificador único del dispositivo.
     * @return HardwareDevice|null Modelo del dispositivo o null si no existe.
     */
    public function getDeviceInfo(int $deviceId): ?HardwareDevice
    {
        return HardwareDevice::with(['type', 'components.availableComponent'])
            ->find($deviceId);
    }

    /**
     * Obtiene la lista de computadoras o dispositivos asociados a un usuario determinado.
     *
     * @param  int  $userId  Identificador único del usuario.
     * @return Collection Colección de dispositivos de hardware.
     */
    public function getComputersList(int $userId): Collection
    {
        return HardwareDevice::forUser($userId)->with('type')->get();
    }

    /**
     * Almacena en la base de datos un nuevo registro de métricas de energía consumida/generada.
     *
     * @param  array  $data  Datos del registro energético.
     * @return HardwareEnergy Modelo de energía recién creado.
     */
    public function storeEnergyData(array $data): HardwareEnergy
    {
        return HardwareEnergy::create($data);
    }

    /**
     * Registra una nueva carga o ciclo de un panel/batería solar.
     *
     * @param  array  $data  Datos de la carga solar.
     * @return SolarCharge Modelo de la carga solar recién creado.
     */
    public function storeSolarCharge(array $data): SolarCharge
    {
        return SolarCharge::create($data);
    }
}
