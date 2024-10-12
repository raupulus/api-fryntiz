<?php

namespace App\Http\Controllers\Api\Hardware\V1;

use App\Http\Controllers\Controller;
use App\Models\Hardware\HardwareDevice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HardwareDeviceController extends Controller
{

    /**
     * Devuelve información sobre un dispositivo de hardware recibido.
     */
    public function getDeviceInfo($id): JsonResponse
    {
        $device = HardwareDevice::where('hardware_devices.id', $id)
            ->select(['hardware_devices.hardware_type_id', 'hardware_devices.name', 'hardware_devices.name_friendly',
                'hardware_devices.model', 'hardware_devices.description', 'hardware_devices.ip_local', 'hardware_devices.ip_public',
                'hardware_devices.last_seen_at', 'hardware_types.name as hardware_type_name'])
            ->leftJoin('hardware_types', 'hardware_types.id', '=', 'hardware_devices.hardware_type_id')
            ->whereNull('hardware_devices.deleted_at')
            ->first();

        return \JsonHelper::success([
            'device' => $device,
        ]);
    }

    /**
     * Devuelve un listado de equipos.
     */
    public function getComputersList(Request $request): JsonResponse
    {

        $clientIp = $request->header('Local-Ip');
        $deviceId = $request->header('Device-Id');
        $publicIp = $request->header('X-Forwarded-For') ?? $request->ip();

        if ($clientIp && $deviceId) {
            $device = HardwareDevice::find($deviceId);

            if ($device) {
                $device->fill([
                    'last_seen_at' => now(),
                    'ip_local' => $clientIp,
                    'ip_public' => $publicIp,
                ]);

                $device->save();
            }
        }

        $devices = HardwareDevice::select(['hardware_devices.id', 'hardware_devices.name_friendly', 'hardware_types.name'])
            ->leftJoin('hardware_types', 'hardware_types.id', '=', 'hardware_devices.hardware_type_id')
            ->whereIn('hardware_devices.hardware_type_id', [3,4,5])
            ->get();

        return \JsonHelper::success([
            'devices' => $devices,
        ]);
    }
}
