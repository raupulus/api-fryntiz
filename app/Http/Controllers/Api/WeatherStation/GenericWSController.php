<?php

namespace App\Http\Controllers\Api\WeatherStation;

use App\Models\Hardware\HardwareDevice;
use App\Models\WeatherStation\Humidity;
use App\Models\WeatherStation\Pressure;
use App\Models\WeatherStation\Temperature;
use App\Models\WeatherStation\WindDirection;
use App\Models\WeatherStation\Winter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class GenericWSController
{
    /**
     *
     * 'hardware_device_id' => 9, // 9 = Esp32
     * 'temperature' => 11.8, // 11.8ºC
     * 'humidity' => 77, // 77%
     * 'wind_speed' => 1.2, // 1.2m/s
     * 'wind_average_speed' => 1.2, // 1.2m/s
     * 'wind_min_speed' => 1.2, // 1.2m/s
     * 'wind_max_speed' => 1.2, // 1.2 m/s
     * 'wind_grade' => 45.0, // 0 - 360, 0 = N, 90 = E, 180 = S, 270 = W
     * 'wind_direction_resistance' => 0.0, // Optional: 0 - 65535
     * 'wind_direction' => 'N', // Optional: N, NE, E, SE, S, SW, W, NW
     * 'rain' => 243, // 243 mm  (0.243m) (0.243L) Puede ser lluvia acumulada o caída en el último periodo de tiempo
     * 'moisture' => ?????? Puede ser el nivel de lluvia actual, vigilar
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function add(Request $request)
    {

        // TODO: Preparar validación de request

        // TODO: Preparar store en modelos


        if (!$request->has('hardware_device_id')) {
            return response()->json([
                'message' => 'hardware_device_id is required',
            ], 400);
        }

        $hardwareDevice = HardwareDevice::find($request->get('hardware_device_id'));

        if (!$hardwareDevice || $hardwareDevice->user_id != auth()->id()) {
            return response()->json([
                'message' => 'hardware_device_id is required and must be valid for your user',
            ], 400);
        }

        $stored = [];

        if ($request->has('temperature')) {

            $stored['temperature'] = new Temperature([
                'value' => $request->get('temperature'),
            ]);
            $stored['temperature']->hardware_device_id = $hardwareDevice->id;
            $stored['temperature']->user_id = auth()->id();
            $stored['temperature']->save();
        }

        if ($request->has('humidity')) {
            $stored['humidity'] = new Humidity([
                'value' => $request->get('humidity'),
            ]);

            // TODO: Añadir punto de rocío calculado???

            $stored['humidity']->hardware_device_id = $hardwareDevice->id;
            $stored['humidity']->user_id = auth()->id();
            $stored['humidity']->save();
        }

        if ($request->has('wind_grades')) {
            $grades = $request->get('wind_grades');
            $resistance = $request->get('wind_direction_resistance') ??
                WindDirection::getResistance($grades);
            $direction = $request->get('wind_direction') ??
                WindDirection::getDirection($grades);

            $stored['wind_direction'] = new WindDirection([
                'resistance' => $resistance,
                'grades' => $grades, // TODO: crear campo grade
                'direction' => $direction,
            ]);

            $stored['wind_direction']->hardware_device_id = $hardwareDevice->id;
            $stored['wind_direction']->user_id = auth()->id();
            $stored['wind_direction']->save();
        }

        if ($request->has('wind_speed')) {
            $windSpeed = $request->get('wind_speed');
            $windAverageSpeed = $request->get('wind_average_speed');
            $windMinSpeed = $request->get('wind_min_speed');
            $windMaxSpeed = $request->get('wind_max_speed');

            $stored['winter'] = new Winter([
                'speed' => $windSpeed,
                'average' => $windAverageSpeed,
                'min' => $windMinSpeed,
                'max' => $windMaxSpeed,
            ]);

            $stored['winter']->hardware_device_id = $hardwareDevice->id;
            $stored['winter']->user_id = auth()->id();
            $stored['winter']->save();
        }

        if ($request->has('rain')) {
            $rain = $request->get('rain');

            // TODO: Crear modelo Rain

            Log::debug('Rain: ' . $rain);
        }

        if ($request->has('pressure')) {
            $pressure = $request->get('pressure');

            $stored['pressure'] = new Pressure([
                'user_id' => auth()->id(),
                'hardware_device_id' => $hardwareDevice->id,
                'value' => $pressure,
            ]);


            $stored['pressure']->hardware_device_id = $hardwareDevice->id;
            $stored['pressure']->user_id = auth()->id();
            $stored['pressure']->save();
        }

        if ($request->has('moisture')) {
            $moisture = $request->get('moisture');

            // TODO: Crear modelo Moisture
            Log::debug('moisture: ' . $moisture);

        }

        //Log::debug($request->all());




        return response()->json([
            'message' => 'OK',
        ]);
    }
}
