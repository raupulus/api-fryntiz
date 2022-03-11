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


        ## Preparo carga histórica contemplando la de generador y consumo general.
        $loadHistoricalFromGenerator = $hardwareGeneratorHistorical->sum('cumulative_power_generation');
        $loadHistoricalGeneral = $hardwareLoadHistorical->sum('power_avg');
        $loadHistorical = number_format(($loadHistoricalFromGenerator + $loadHistoricalGeneral)/1000, 2);

        ## Preparo carga diaria contemplando la de generador y consumo general.
        $loadTodayFromGenerator = $hardwareGeneratorToday->sum('power_generation');
        $loadTodayGeneral = $hardwareLoadToday->sum('power_avg');
        $loadToday = number_format(($loadTodayFromGenerator + $loadTodayGeneral)/1000, 2);

        return view('hardware.energy.index', [
            'hardwares' => $hardwares,
            'generatorToday' => $generatorToday,
            'generatorHistorical' => $generatorHistorical,
            'loadToday' => $loadToday,
            'loadHistorical' => $loadHistorical,
        ]);
    }
}
