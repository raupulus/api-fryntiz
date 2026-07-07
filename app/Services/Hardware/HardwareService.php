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

    /**
     * Actualiza el último estado conocido de un dispositivo de hardware.
     *
     * No se guarda histórico: solo se sobrescribe el último estado del propio
     * dispositivo (temperatura, tensión, batería, CPU, disco, uptime, IPs y
     * métricas extra). El campo `last_seen_at` se fija al momento actual.
     *
     * Solo se actualizan las claves presentes en `$data` para no sobrescribir
     * con nulos valores previamente conocidos que no vengan en esta subida.
     *
     * @param  int  $deviceId  Identificador del dispositivo hardware.
     * @param  array  $data  Estado del dispositivo (temp, voltage, battery_level, cpu, disk, uptime, ip_local, ip_public, extra).
     * @return HardwareDevice Dispositivo actualizado.
     */
    public function updateDeviceStatus(int $deviceId, array $data): HardwareDevice
    {
        $device = HardwareDevice::query()->findOrFail($deviceId);

        $allowed = ['temp', 'voltage', 'battery_level', 'cpu', 'disk', 'uptime', 'ip_local', 'ip_public', 'extra'];

        $status = array_intersect_key($data, array_flip($allowed));

        $status['last_seen_at'] = now();

        $device->fill($status)->save();

        return $device;
    }
}
