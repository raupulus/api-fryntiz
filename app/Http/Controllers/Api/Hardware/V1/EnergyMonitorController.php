<?php

namespace App\Http\Controllers\Api\Hardware\V1;

use App\Http\Controllers\Controller;
use App\Models\Hardware\HardwareDevice;
use App\Models\Hardware\HardwarePowerLoad;
use App\Models\Hardware\HardwarePowerLoadHistorical;
use App\Models\Hardware\HardwarePowerLoadToday;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Class EnergyMonitorController
 *
 * Procesa los datos de los sensores de energía.
 *
 * @package App\Http\Controllers\Api\Hardware\V1
 */
class EnergyMonitorController extends Controller
{

    // TODO: Crear validación request
    public function store(Request $request)
    {
        $hardwareDeviceId = $request->get('hardware_device');

        if (!$hardwareDeviceId) {
            return \JsonHelper::failed('Dispositivo no encontrado');
        }

        $hardwareDevice = HardwareDevice::find($hardwareDeviceId);

        if (!$hardwareDevice) {
            return \JsonHelper::failed('Dispositivo no encontrado');
        }

        $temperature = $request->get('cpu_avg');
        $now = Carbon::now();
        $date = $now->format('Y-m-d');


        ## Solo el propietario del hardware monitor puede guardar datos
        if ((int)$hardwareDevice->user_id !== auth()->id()) {
            return \JsonHelper::failed();
        }

        ## Obtengo array con todos los registros de consumo
        $sensors = collect($request->get('intensity'));

        if (!$sensors || !$sensors->count()) {
            Log::debug('No hay datos de intensidad');
            //Log::debug($sensors);

            return \JsonHelper::failed('No hay datos de intensidad');
        }

        ## Obtengo todos los dispositivos hardware monitorizados
        $hardwaresEnergy = $hardwareDevice->hardwareEnergy;

        if (!$hardwaresEnergy || !$hardwaresEnergy->count()) {
            Log::debug('No hay dispositivos de energía asociados');
            //Log::debug($hardwaresEnergy);

            return \JsonHelper::failed('No hay dispositivos de energía asociados');
        }

        ## Recorro cada dispositivo monitorizado y guardo sus registros.
        foreach ($hardwaresEnergy as $hardwareEnergy) {
            $pos = $hardwareEnergy->sensor_position;

            ## Obtengo los registros usando la posición asignada al dar de alta el dispositivo monitorizado para asociar sus registros.
            $sensor = $sensors->where('pos', $pos)->first();

            if (!$sensor || !count($sensor)) {
                continue;
            }

            ## Obtengo el dispositivo de hardware que está monitorizado.
            $hardwareEnergyMonitorized = $hardwareEnergy->monitorized;

            ## Compruebo si el dispositivo monitorizado es un generador de energía.
            $isGenerator = $hardwareEnergy->is_generator;

            ## Almaceno estadísticas.
            $voltage = $request->get('voltage_avg');
            $amperage = $sensor['avg'];
            $power = isset($sensor['power']) ? $sensor['power'] : $voltage * $amperage;
            $fan = isset($sensor['fan']) ? $sensor['fan'] : null;
            $batterVoltage = isset($sensor['battery_voltage']) ? $sensor['battery_voltage'] : null;
            $batterPorcentage = isset($sensor['battery_porcentage']) ? $sensor['battery_porcentage'] : null;


            ## Amperaje consumido respecto a una hora.
            $duration = $request->get('duration');

            if ($duration) {
                $amperageHour = ( $amperage * $duration ) / 3600;
            } else {
                $amperageHour = 0;
            }


            ##TODO: La parte de generador, por ahora no la uso. Queda en pausa hasta terminar de crear los sistemas autoabastecidos para implementar esta funcionalidad primero en la raspberry pi pico y tener clara la refactorización de las tablas, modelos, controladores...
            if ($isGenerator) {
                //HardwarePowerGenerator::createModel($hardwareEnergyMonitorized, $requestFinal)->save();
            } else {
                $requestFinal = new Request([
                    'fan' => $fan,
                    'temperature' => $temperature,
                    'voltage' => $voltage,
                    'amperage' => $amperage,
                    'power' => $power,
                    'battery_voltage' => $batterVoltage,
                    'battery_porcentage' => $batterPorcentage,
                ]);

                /******** CONSUMO EN EL MOMENTO ********/
                HardwarePowerLoad::createModel($hardwareEnergyMonitorized, $requestFinal)->save();

                /************ CONSUMO DIARIO ***********/
                HardwarePowerLoadToday::recalculateToday(
                    $hardwareEnergyMonitorized->id,
                    [
                        'fan',
                        'amperage' => $amperageHour,
                        'voltage' => $voltage,
                        'temperature' => $temperature,
                        'read_at' => $now,
                    ],
                    $date
                );

                /************ CONSUMO HISTÓRICO ***********/
                HardwarePowerLoadHistorical::calculateHistoricalFromTodays($hardwareEnergyMonitorized->id);
            }

        }

        return \JsonHelper::success([
            'message' => 'Datos recibidos correctamente',
        ]);
    }
}
