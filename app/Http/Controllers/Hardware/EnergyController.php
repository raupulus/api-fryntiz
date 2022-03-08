<?php

namespace App\Http\Controllers\Hardware;

use App\Http\Controllers\Controller;
use App\Models\Hardware\HardwareDevice;
use App\Models\Hardware\HardwarePowerGeneratorHistorical;
use App\Models\Hardware\HardwarePowerLoadHistorical;
use App\Models\Hardware\HardwarePowerLoadToday;
use Illuminate\Http\Request;

/**
 * Class EnergyController
 *
 * Gestiona peticiones para el consumo y la producción de energía.
 *
 * @package App\Http\Controllers\Hardware
 */
class EnergyController extends Controller
{
    public function index()
    {
        $hardwares = HardwareDevice::where('user_id', 2)->get();
        $hardware_ids = $hardwares->pluck('id')->toArray();
        $hardwareGeneratorToday = HardwarePowerGeneratorHistorical::whereIn('hardware_device_id', $hardware_ids)->get();
        $hardwareGeneratorHistorical = HardwarePowerGeneratorHistorical::whereIn
        ('hardware_device_id', $hardware_ids)->get();

        $hardwareLoadToday = HardwarePowerLoadToday::whereIn
        ('hardware_device_id', $hardware_ids)->get();
        $hardwareLoadHistorical = HardwarePowerLoadHistorical::whereIn
        ('hardware_device_id', $hardware_ids)->get();

        return view('hardware.energy.index', [
            'hardwares' => $hardwares,
            'hardwareGeneratorToday' => $hardwareGeneratorToday,
            'hardwareGeneratorHistorical' => $hardwareGeneratorHistorical,
            'hardwareLoadToday' => $hardwareLoadToday,
            'hardwareLoadHistorical' => $hardwareLoadHistorical,
        ]);
    }
}
