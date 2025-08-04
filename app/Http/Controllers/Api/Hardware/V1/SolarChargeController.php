<?php

namespace App\Http\Controllers\Api\Hardware\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Hardware\V1\StoreSolarChargeRequest;
use App\Models\Hardware\HardwareDevice;
use App\Models\Hardware\HardwarePowerGenerator;
use App\Models\Hardware\HardwarePowerGeneratorHistorical;
use App\Models\Hardware\HardwarePowerGeneratorToday;
use App\Models\Hardware\HardwarePowerLoad;
use App\Models\Hardware\HardwarePowerLoadHistorical;
use App\Models\Hardware\HardwarePowerLoadToday;
use App\Models\Hardware\SolarCharge;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use JsonHelper;
use function array_keys;
use function auth;
use function request;
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
     * Procesa el guardado de los datos de la carga solar.
     *
     * @param \App\Http\Requests\Api\Hardware\V1\StoreSolarChargeRequest $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StoreSolarChargeRequest $request)
    {
        $requestValidated = collect($request->validated());
        $date = $requestValidated->get('date');
        $readAt = $requestValidated->get('read_at');

        $device_id = $request->get('device_id');

        ## Usuario logueado.
        $user = auth()->user();

        ## Dispositivo sobre el que se guardan los registros.
        $device = HardwareDevice::where('id', $device_id)
            ->where('user_id', $user->id)
            ->first();

        ## Actualizo los datos del dispositivo (HardwareDeviceController).
        $device = $device->updateModel($request);
        $device->save();

        ## Guardo los datos para la carga de energía.
        $hardwarePowerGenerator = HardwarePowerGenerator::createModel($device, $requestValidated);
        $hardwarePowerGenerator->save();

        ## Actualizo el histórico diario
        $hardwarePowerGeneratorToday = HardwarePowerGeneratorToday::where('hardware_device_id', $device->id)
            ->where('date', $date)
            ->first();

        if ($hardwarePowerGeneratorToday) {
            if ($readAt > $hardwarePowerGeneratorToday->read_at) {
                $hardwarePowerGeneratorToday->updateModel($requestValidated);
            }
        } else {
            $hardwarePowerGeneratorToday = HardwarePowerGeneratorToday::createModel($device, $requestValidated);
        }

        $hardwarePowerGeneratorToday->save();


        ## Guardo los datos para el Historial de generar de energía.
        $hardwarePowerGeneratorHistorical =
            HardwarePowerGeneratorHistorical::where('hardware_device_id', $device->id)
                ->orderByDesc('read_at')
                ->first();

        ## Actualizo el historial de este dispositivo solo si es posterior al último registro.
        if ($hardwarePowerGeneratorHistorical) {
            $generatorContinueDays = $requestValidated->get('days_operating') >=  $hardwarePowerGeneratorHistorical->days_operating;
            $generatorNewRead = $readAt > $hardwarePowerGeneratorHistorical->read_at;

            if ($generatorContinueDays && $generatorNewRead) {
                $hardwarePowerGeneratorHistorical->updateModel($requestValidated);
            } else if (! $generatorContinueDays && $generatorNewRead) {
                $hardwarePowerGeneratorHistorical = HardwarePowerGeneratorHistorical::createModel($device, $requestValidated);
            }
        } else {
            $hardwarePowerGeneratorHistorical = HardwarePowerGeneratorHistorical::createModel($device, $requestValidated);
        }

        $hardwarePowerGeneratorHistorical->save();

        ## HardwarePowerLoad
        $hardwarePowerLoad = HardwarePowerLoad::createModel($device, $requestValidated);
        $hardwarePowerLoad->save();

        ## HardwarePowerLoadToday
        $hardwarePowerLoadToday = HardwarePowerLoadToday::where('hardware_device_id', $device->id)
            ->where('date', $date)
            ->first();

        if ($hardwarePowerLoadToday) {
            if ($readAt > $hardwarePowerLoadToday->read_at) {
                $hardwarePowerLoadToday->updateModel($requestValidated);
            }
        } else {
            $hardwarePowerLoadToday = HardwarePowerLoadToday::createModel($device, $requestValidated);
        }

        $hardwarePowerLoadToday->save();

        ## HardwarePowerLoadHistorical datos para el Historial de consumo.
        $hardwarePowerLoadHistorical =
            HardwarePowerLoadHistorical::where('hardware_device_id',
                $device->id)
                ->orderByDesc('read_at')
                ->first();

        ## Actualizo el historial de este dispositivo solo si es posterior al último registro.
        if ($hardwarePowerLoadHistorical) {
            $loadContinueDays = $requestValidated->get('days_operating') >=  $hardwarePowerLoadHistorical->days_operating;
            $loadNewRead = $readAt > $hardwarePowerLoadHistorical->read_at;

            if ($loadContinueDays && $loadNewRead) {
                $hardwarePowerLoadHistorical->updateModel($requestValidated);
            } else if (! $loadContinueDays && $loadNewRead) {
                $hardwarePowerLoadHistorical = HardwarePowerLoadHistorical::createModel($device, $requestValidated);
            }
        } else {
            $hardwarePowerLoadHistorical = HardwarePowerLoadHistorical::createModel($device, $requestValidated);
        }

        $hardwarePowerLoadHistorical->save();

        return JsonHelper::created([
            'message' => 'Carga de energía registrada correctamente.',
            'data' => [
                'device' => $device->id,
                'hardwarePowerGenerator' => $hardwarePowerGenerator->id,
                'hardwarePowerGeneratorToday' => $hardwarePowerGeneratorToday->id,
                'hardwarePowerGeneratorHistorical' => $hardwarePowerGeneratorHistorical->id,
                'hardwarePowerLoad' => $hardwarePowerLoad->id,
                'hardwarePowerLoadToday' => $hardwarePowerLoadToday->id,
                'hardwarePowerLoadHistorical' => $hardwarePowerLoadHistorical->id,
            ]
            //'request' => $request->validated()
        ]);

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
