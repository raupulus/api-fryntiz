<?php

namespace App\Http\Controllers\Hardware;

use App\Http\Controllers\Controller;
use App\Models\Hardware\HardwareDevice;
use App\Models\Hardware\HardwarePowerGeneratorHistorical;
use App\Models\Hardware\HardwarePowerGeneratorToday;
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
        $dateToday = date('Y-m-d');

        $hardwares = HardwareDevice::where('user_id', 2)->get();
        $hardware_ids = $hardwares->pluck('id')->toArray();

        $hardwareGeneratorToday = HardwarePowerGeneratorToday::whereIn('hardware_device_id', $hardware_ids)
            ->where('date', $dateToday)
            ->get();
        $hardwareGeneratorHistorical = HardwarePowerGeneratorHistorical::whereIn('hardware_device_id', $hardware_ids)->get();

        $hardwareLoadToday = HardwarePowerLoadToday::whereIn('hardware_device_id', $hardware_ids)
            ->where('date', $dateToday)
            ->get();
        $hardwareLoadHistorical = HardwarePowerLoadHistorical::whereIn('hardware_device_id', $hardware_ids)->get();





        $generatorToday = $hardwareGeneratorToday->sum('power_generation');
        $generatorHistorical = number_format($hardwareGeneratorHistorical->sum('cumulative_power_generation')/1000, 2);
        $loadToday = $hardwareLoadToday->sum('power_avg');


        // TODO → Sumar generado para solar con el historical generado en loads

        //$loadHistorical = number_format($hardwareLoadHistorical->sum('power_avg')/1000, 2);
        $loadHistorical = number_format($hardwareGeneratorHistorical->sum('cumulative_power_consumption')/1000, 2);

        return view('hardware.energy.index', [
            'hardwares' => $hardwares,
            'generatorToday' => $generatorToday,
            'generatorHistorical' => $generatorHistorical,
            'loadToday' => $loadToday,
            'loadHistorical' => $loadHistorical,
        ]);
    }
}
