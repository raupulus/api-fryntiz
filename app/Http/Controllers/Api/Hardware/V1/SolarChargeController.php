<?php

namespace App\Http\Controllers\Api\Hardware\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Hardware\V1\StoreSolarChargeRequest;
use App\Models\Hardware\HardwareDevice;
use App\Models\Hardware\HardwarePowerGenerator;
use App\Models\Hardware\HardwarePowerGeneratorHistorical;
use App\Models\Hardware\HardwarePowerGeneratorToday;
use App\Models\Hardware\HardwarePowerLoad;
use App\Models\Hardware\HardwarePowerLoadToday;
use App\Models\Hardware\SolarCharge;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use JsonHelper;
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
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     *
     */

    /**
     * Procesa el guardado de los datos de la carga solar.
     *
     * @param \App\Http\Requests\Api\Hardware\V1\StoreSolarChargeRequest $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StoreSolarChargeRequest $request)
    {
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


        ## Actualizo el histórico diario
        $hardwarePowerGeneratorToday = HardwarePowerGeneratorToday::where('hardware_device_id', $device->id)
            ->where('date', $request->date ?? Carbon::create($request->created_at)->format('Y-m-d'))
            ->first();

        if ($hardwarePowerGeneratorToday) {
            if ($request->created_at > $hardwarePowerGeneratorToday->created_at) {
                $hardwarePowerGeneratorToday->updateModel($request);
                $hardwarePowerGeneratorToday->created_at = $request->created_at;
            }
        } else {
            $hardwarePowerGeneratorToday = HardwarePowerGeneratorToday::createModel($device, $request);
            $hardwarePowerGeneratorToday->save();
            $hardwarePowerGeneratorToday->created_at = $request->created_at;
            $hardwarePowerGeneratorToday->date = $request->date ??
                Carbon::create($request->created_at)->format('Y-m-d');
        }

        $hardwarePowerGeneratorToday->save();


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


        ## HardwarePowerLoad
        $hardwarePowerLoad = HardwarePowerLoad::createModel($device, $request);
        $hardwarePowerLoad->save();

        ## HardwarePowerLoadToday
        $hardwarePowerLoadToday = HardwarePowerLoadToday::where('hardware_device_id', $device->id)
            ->where('date', $request->date ??
                Carbon::create($request->created_at)->format('Y-m-d'))
            ->first();

        if ($hardwarePowerLoadToday) {
            $hardwarePowerLoadToday->updateModel($request);

            if ($request->created_at > $hardwarePowerLoadToday->created_at) {
                $hardwarePowerLoadToday->created_at = $request->created_at;
            }
        } else {
            $hardwarePowerLoadToday = HardwarePowerLoadToday::createModel($device, $request);
            $hardwarePowerLoadToday->save();
            $hardwarePowerLoadToday->created_at = $request->created_at;
            $hardwarePowerLoadToday->date = $request->date ??
                Carbon::create($request->created_at)->format('Y-m-d');
        }

        $hardwarePowerLoadToday->save();


        // TODO → Falta histórico de carga de energía.
        ## HardwarePowerLoadHistorical

        return JsonHelper::created([
            'message' => 'Carga de energía registrada correctamente.',
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
