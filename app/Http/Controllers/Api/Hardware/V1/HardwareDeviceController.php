<?php

namespace App\Http\Controllers\Api\Hardware\V1;

use App\Http\Controllers\Controller;
use App\Models\Hardware\HardwareDevice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HardwareDeviceController extends Controller
{

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
