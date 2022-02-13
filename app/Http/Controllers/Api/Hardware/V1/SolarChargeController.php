<?php

namespace App\Http\Controllers\Api\Hardware\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Hardware\V1\StoreSolarChargeRequest;
use App\Models\Hardware\HardwareDevice;
use App\Models\Hardware\HardwarePowerGenerator;
use App\Models\Hardware\HardwarePowerGeneratorHistorical;
use App\Models\Hardware\SolarCharge;
use Carbon\Carbon;
use Illuminate\Http\Request;
use JsonHelper;
use function auth;
use function response;

/**
 * Class SolarChargeController
 */
class SolarChargeController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     *
     */
    /*
{
   "battery_voltage":12.5,
   "battery_temperature":17,
   "battery_percentage":68,
   "controller_temperature":19,
   "load_voltage":12.5,
   "load_current":0.83,
   "load_power":10,
   "solar_voltage":0.6,
   "solar_current":0.0,
   "solar_power":0,
   "street_light_status":"None",
   "street_light_brightness":"None",
   "charging_status":0,
   "charging_status_label":"None",
   "hardware":"V0.0.2",
   "version":"V3.0.1",
   "serial_number":"461577",
   "system_voltage_current":24,
   "system_intensity_current":20,
   "battery_type":"None",
   "nominal_battery_capacity":200,
   "today_battery_max_voltage":14.6,
   "today_battery_min_voltage":12.1,
   "today_max_charging_current":10.12,
   "today_max_charging_power":1012,
   "today_charging_amp_hours":32,
   "today_discharging_amp_hours":10,
   "today_power_generation":435,
   "today_power_consumition":120,
   "historical_total_days_operating":62,
   "historical_total_number_battery_over_discharges":4,
   "historical_total_number_battery_full_charges":68,
   "historical_total_charging_amp_hours":0,
   "historical_total_discharging_amp_hours":0,
   "historical_cumulative_power_generation":"None",
   "historical_cumulative_power_consumption":"None"
}
 */
    public function store(StoreSolarChargeRequest $request)
    {
        $dataRequest = $request->all();
        $device_id = $request->json('device_id');

        ## Usuario logueado.
        $user = auth()->user();

        ## Dispositivo sobre el que se guardan los registros.
        $device = HardwareDevice::where('id', $device_id)
            ->where('user_id', $user->id)
            ->first();


        ## Actualizo los datos del dispositivo (HardwareDevice).
        $device = $device->updateModel($request);
        $device->save();

        ## Guardo los datos para la carga de energía.
        $hardwarePowerGenerator = HardwarePowerGenerator::createModel($device, $request);
        $hardwarePowerGenerator->save();

        ## Guardo los datos para el Historial de carga de energía.
        $hardwarePowerGeneratorHistorical =
            HardwarePowerGeneratorHistorical::where('hardware_device_id',
                $device->id)->first();

        ## Actualizo el historial de este dispositivo solo si es posterior al último registro.
        if ($hardwarePowerGeneratorHistorical) {
            if ($request->created_at > $hardwarePowerGeneratorHistorical->created_at) {
                $hardwarePowerGeneratorHistorical->updateModel($request);
                $hardwarePowerGeneratorHistorical->created_at = $request->created_at;
            }
        } else {
            $hardwarePowerGeneratorHistorical = HardwarePowerGeneratorHistorical::createModel($device, $request);
            $hardwarePowerGeneratorHistorical->save();
            $hardwarePowerGeneratorHistorical->created_at = $request->created_at;
        }

        $hardwarePowerGeneratorHistorical->save();





        return response()->json([
            'device_id' => $device_id,
            'hardwarePowerGeneratorHistorical' => $hardwarePowerGeneratorHistorical,
            'device' => $device,
            'request' => $dataRequest,
        ]);


// TODO → Crear validación de request y guardar todos los datos en db

        $generator = $device->powerGenerator;
        $generatorToday = $device->powerGeneratorToday;
        $generatorHistorical = $device->powerGeneratorHistorical;
        $loads = $device->loads;
        $loadsToday = $device->loadToday;
        $loadsHistorical = $device->loadHistorical;
    }

    /**
     * Display the specified resource.
     *
     * @param \App\Models\Hardware\SolarCharge $solarCharge
     *
     * @return \Illuminate\Http\Response
     */
    public function show(SolarCharge $solarCharge)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request         $request
     * @param \App\Models\Hardware\SolarCharge $solarCharge
     *
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, SolarCharge $solarCharge)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \App\Models\Hardware\SolarCharge $solarCharge
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy(SolarCharge $solarCharge)
    {
        //
    }
}
