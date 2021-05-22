<?php

namespace App\Http\Controllers\Api\SmartPlant;

use App\Http\Controllers\Controller;
use App\Models\SmartPlant\SmartPlantPlant;
use App\Models\SmartPlant\SmartPlanRegister;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use function get_object_vars;
use function GuzzleHttp\json_decode;
use function is_array;
use function response;
use function view;

/**
 * Class SmartPlantController
 *
 * @package App\Http\Controllers\SmartPlant
 */
class SmartPlantController extends Controller
{
    /**
     * LLeva a la vista resumen con datos para depurar subidas.
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index()
    {
        $smartplants = SmartPlantPlant::all();

        return view('smartplant.index')->with([
            'smartplants' => $smartplants,
        ]);
    }

    /**
     * Devuelve todos los elementos del modelo.
     * @return \Illuminate\Http\JsonResponse
     */
    public function all()
    {
        $model = SmartPlanRegister::whereNotNull('soil_humidity')
            ->orderBy('start_at', 'DESC')
            ->orderBy('created_at', 'DESC')
            ->whereNull('deleted_at')
            ->get();
        return response()->json($model);
    }

    /**
     * Añade una nueva entrada de la medición desde el sensor.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\JsonResponse|void
     */
    public function add(Request $request)
    {
        Log::info('Entra en controlador para guardar registro de planta json');

        $requestValidate = $this->addValidate($request->all());

        try {
            $model = new SmartPlanRegister();
            $model->fill($requestValidate);

            ## Respuesta cuando se ha guardado el modelo correctamente
            if ($model->save()) {
                // response bien

                // TODO → Crear sistema de respuestas habituales 200,201,404,419...

                return response()->json('Guardado Correctamente', 201);
            }
        } catch (Exception $e) {
            Log::error('Error en controlador SmartPlantController método add');
        }

        // response mal
        return response()->json('No se ha guardado nada', 500);
    }

    /**
     * Recibe JSON con datos para guardar por lote.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     * @throws \Exception
     */
    public function addJson(Request $request)
    {
        Log::info('Entra en controlador para guardar registro de planta json');

        $data = json_decode($request->get('data'));

        $fallidos = 0;

        ## Proceso cada dato recibido mediante JSON.
        foreach ($data as $d) {
            try {
                $model = new SmartPlanRegister();

                ## Obtengo atributos y los validos para excluir posible basura.
                $attributes = $this->addValidate(get_object_vars($d));

                if (is_array($attributes)) {
                    $model->fill($attributes);

                    if ($model->soil_humidity > 100.0) {
                        $model->soil_humidity = 100.0;
                    }

                    if ($model->temperature > 100.0) {
                        $model->temperature = 100.0;
                    }

                    if ($model->humidity > 100.0) {
                        $model->humidity = 100.0;
                    }

                    $model->save();
                } else {
                    $fallidos++;
                }
            } catch (Exception $e) {
                Log::error('Error insertando datos en registros de smartplant');
                Log::error($e);
                $fallidos++;
            }
        }

        ## Respuesta cuando se ha guardado el modelo correctamente
        if ($fallidos == 0) {
            return response()->json('Guardado Correctamente', 201);
        } else if ($fallidos >= 1) {
            return response()->json('Fallidos: ' . $fallidos, 200);
        }

        return response()->json('No se ha guardado nada', 500);
    }

    /**
     * Reglas de validación a la hora de insertar datos.
     *
     * @param $request
     *
     * @return mixed
     */
    public function addValidate($data)
    {
        return Validator::make($data, [
            'plant_id' => 'required|numeric',
            'uv' => 'nullable|numeric',
            'temperature' => 'nullable|numeric',
            'humidity' => 'nullable|numeric',
            'soil_humidity' => 'required|numeric',
            'full_water_tank' => 'nullable|boolean',
            'waterpump_enabled' => 'nullable|boolean',
            'vaporizer_enabled' => 'nullable|boolean',
        ])->validate();
    }
}
