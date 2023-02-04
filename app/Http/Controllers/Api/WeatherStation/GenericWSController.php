<?php

namespace App\Http\Controllers\Api\WeatherStation;

use App\Models\Hardware\HardwareDevice;
use App\Models\WeatherStation\AirQuality;
use App\Models\WeatherStation\Eco2;
use App\Models\WeatherStation\Humidity;
use App\Models\WeatherStation\Light;
use App\Models\WeatherStation\Lightning;
use App\Models\WeatherStation\Pressure;
use App\Models\WeatherStation\Rain;
use App\Models\WeatherStation\Temperature;
use App\Models\WeatherStation\Tvoc;
use App\Models\WeatherStation\Wind;
use App\Models\WeatherStation\WindDirection;
use Illuminate\Http\Request;

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

            $stored['winter'] = new Wind([
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
            $moisture = $request->get('moisture');

            // TODO!!! mirar si lo calculo aquí con el registro anterior o si
            // esto es mejor traerlo desde el dispositivo
            $rain_intensity = $request->get('rain_intensity');
            $rain_month = $request->get('rain_month');

            $stored['rain'] = new Rain([
                'rain' => $rain,
                'rain_intensity' => $rain_intensity,
                'rain_month' => $rain_month,
                'moisture' => $moisture,
            ]);

            //Log::debug('Rain: ' . $rain);

            $stored['rain']->hardware_device_id = $hardwareDevice->id;
            $stored['rain']->user_id = auth()->id();
            $stored['rain']->save();
        }

        if ($request->has('pressure')) {
            $pressure = $request->get('pressure');

            $stored['pressure'] = new Pressure([
                'value' => $pressure,
            ]);

            $stored['pressure']->hardware_device_id = $hardwareDevice->id;
            $stored['pressure']->user_id = auth()->id();
            $stored['pressure']->save();
        }

        if ($request->has('lumens')) {
            $lumens = $request->get('lumens') ?? 0;
            $index = $request->get('uv_index');
            $uva = $request->get('uva');
            $uvb = $request->get('uvb');


            $stored['light'] = new Light([
                'lumens' => $lumens,
                'index' => $index,
                'uva' => $uva,
                'uvb' => $uvb,
            ]);

            $stored['light']->hardware_device_id = $hardwareDevice->id;
            $stored['light']->user_id = auth()->id();
            $stored['light']->save();
        }

        if ($request->has('tvoc')) {
            $tvoc = $request->get('tvoc');

            $stored['tvoc'] = new Tvoc([
                'value' => $tvoc,
            ]);

            $stored['tvoc']->hardware_device_id = $hardwareDevice->id;
            $stored['tvoc']->user_id = auth()->id();
            $stored['tvoc']->save();
        }

        if ($request->has('eco2')) {
            $tvoc = $request->get('eco2');

            $stored['eco2'] = new Eco2([
                'value' => $tvoc,
            ]);

            $stored['eco2']->hardware_device_id = $hardwareDevice->id;
            $stored['eco2']->user_id = auth()->id();
            $stored['eco2']->save();
        }

        if ($request->has('air_quality')) {
            $airQuality = $request->get('air_quality');
            $gasResistance = $request->get('gas_resistance');

            $stored['air_quality'] = new AirQuality([
                'air_quality' => $airQuality,
                'gas_resistance' => $gasResistance,
            ]);

            $stored['air_quality']->hardware_device_id = $hardwareDevice->id;
            $stored['air_quality']->user_id = auth()->id();
            $stored['air_quality']->save();
        }

        if ($request->has('lightning_distance')) {
            $lightningDistance = $request->get('lightning_distance');
            $lightningEnergy = $request->get('lightning_energy');
            $lightningNoiseFloor = $request->get('lightning_noise_floor');

            $stored['lightning'] = new Lightning([
                'distance' => $lightningDistance,
                'energy' => $lightningEnergy,
                'noise_floor' => $lightningNoiseFloor,
            ]);

            $stored['lightning']->hardware_device_id = $hardwareDevice->id;
            $stored['lightning']->user_id = auth()->id();
            $stored['lightning']->save();
        }



        //Log::debug($request->all());




        return response()->json([
            'message' => 'OK',
        ]);
    }
}
