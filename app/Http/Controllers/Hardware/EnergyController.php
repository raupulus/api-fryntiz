<?php

namespace App\Http\Controllers\Hardware;

use App\Http\Controllers\Controller;
use App\Models\Hardware\HardwareDevice;
use App\Models\Hardware\HardwarePowerGeneratorHistorical;
use App\Models\Hardware\HardwarePowerLoadHistorical;
use App\Models\Hardware\HardwarePowerLoadToday;
use Illuminate\Http\Request;
use function number_format;

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


        $generatorToday = number_format($hardwareGeneratorToday->sum('cumulative_power_generation')/1000, 2);
        $generatorHistorical = number_format($hardwareGeneratorHistorical->sum('cumulative_power_generation')/1000, 2);
        $loadToday = number_format($hardwareLoadToday->sum('cumulative_power_consumption')/1000, 2);
        $loadHistorical = number_format($hardwareLoadHistorical->sum('cumulative_power_consumption')/1000, 2);


        return view('hardware.energy.index', [
            'hardwares' => $hardwares,
            'generatorToday' => $generatorToday,
            'generatorHistorical' => $generatorHistorical,
            'loadToday' => $loadToday,
            'loadHistorical' => $loadHistorical,
        ]);
    }
}
