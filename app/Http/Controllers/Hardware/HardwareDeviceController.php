<?php

declare(strict_types=1);

namespace App\Http\Controllers\Hardware;

use App\DTO\Hardware\PublicHardwareDeviceData;
use App\Http\Controllers\Controller;
use App\Models\Hardware\HardwareDevice;
use Illuminate\View\View;

use function abort_unless;
use function view;

/**
 * Controlador para la visualización pública del parque de hardware.
 *
 * Expone el escaparate de hardware y telemetría de salud, aplicando
 * estrictamente Privacy by Design para no filtrar nunca datos sensibles
 * como IPs, números de serie o metadatos de red privada.
 */
class HardwareDeviceController extends Controller
{
    /**
     * Catálogo público de dispositivos hardware.
     */
    public function index(): View
    {
        $devices = HardwareDevice::public()
            ->with([
                'type',
                'components.availableComponent',
                'image.fileType',
            ])
            ->orderBy('name')
            ->get();

        $deviceDtos = $devices->map(
            static fn (HardwareDevice $device): PublicHardwareDeviceData => PublicHardwareDeviceData::fromModel($device)
        );

        $types = $deviceDtos
            ->pluck('typeName')
            ->filter()
            ->unique()
            ->values()
            ->all();

        $totalCount = $deviceDtos->count();
        $onlineCount = $deviceDtos->where('isOnline', true)->count();

        return view('hardware.index', [
            'devices' => $deviceDtos,
            'types' => $types,
            'totalCount' => $totalCount,
            'onlineCount' => $onlineCount,
        ]);
    }

    /**
     * Ficha técnica detallada de un dispositivo público.
     */
    public function show(HardwareDevice $device): View
    {
        abort_unless($device->is_public, 404);

        $device->loadMissing([
            'type',
            'components.availableComponent',
            'image.fileType',
        ]);

        $deviceDto = PublicHardwareDeviceData::fromModel($device);

        return view('hardware.show', [
            'device' => $deviceDto,
        ]);
    }
}
