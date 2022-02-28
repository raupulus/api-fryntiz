<?php

namespace App\Http\Controllers\Api\SmartPlant\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\SmartPlant\V1\StoreRegisterRequest;
use App\Models\SmartPlant\SmartPlantRegister;
use App\Models\SmartPlant\SmartPlantPlant;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use JsonHelper;
use function get_object_vars;
use function GuzzleHttp\json_decode;
use function is_array;
use function response;

/**
 * Class SmartPlantController
 *
 * @package App\Http\Controllers\SmartPlant
 */
class SmartPlantRegisterController extends Controller
{
    /**
     * Devuelve todos los elementos del modelo.
     * @return \Illuminate\Http\JsonResponse
     */
    public function all()
    {
        $model = SmartPlantRegister::whereNotNull('soil_humidity')
            ->orderBy('start_at', 'DESC')
            ->orderBy('created_at', 'DESC')
            ->whereNull('deleted_at')
            ->get();
        return response()->json($model);
    }

    public function store(StoreRegisterRequest $request)
    {
        $plant = SmartPlantPlant::find($request->plant_id);

        if (!$plant) {
            return JsonHelper::notFound();
        }

        $model = SmartPlantRegister::create($request->validated());







        return response()->json([
            'message' => 'test',
            'request' => $request->all(),
            'model' => $model,
        ]);
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
                $model = new SmartPlantRegister();

                ## Obtengo atributos y los validos para excluir posible basura.
                //$attributes = $this->addValidate(get_object_vars($d));
                $attributes = get_object_vars($d);

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

                    if (! $model->plant_id) {
                        $model->plant_id = 1;
                    }


                    //$model->user_id = auth()->id();

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
}
