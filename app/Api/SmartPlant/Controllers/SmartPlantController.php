<?php

namespace App\Http\Controllers\SmartPlant;

use App\Http\Controllers\Controller;
use App\SmartPlant\Plant;
use App\SmartPlant\PlantRegister;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use function get_object_vars;
use function GuzzleHttp\json_decode;
use function is_array;
use function redirect;
use function response;
use function view;

abstract class SmartPlantController extends Controller
{
    /**
     * @var string Ruta y modelo sobre el que se trabajará.
     */
    protected $model = PlantRegister::class;

    /**
     * @var string Mensaje de error al agregar un nuevo dato.
     */
    protected $addError = '';

    public function index()
    {
        $smartplants = Plant::all();

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
        $model = $this->model::whereNotNull('soil_humidity')
            ->orderBy('created_at', 'DESC')
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
        $requestValidate = $this->addValidate($request->all());

        $model = new $this->model;
        $model->fill($requestValidate);

        ## Respuesta cuando se ha guardado el modelo correctamente
        if ($model->save()) {
            // response bien

            // TODO → Crear sistema de respuestas habituales 200,201,404,419...

            return response()->json('Guardado Correctamente', 201);
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
        $data = json_decode($request->get('data'));

        $fallidos = 0;

        ## Proceso cada dato recibido mediante JSON.
        foreach ($data as $d) {
            try {
                $model = new $this->model;

                ## Parseo la fecha
                $d->created_at = (new \DateTime($d->created_at))->format('Y-m-d H:i:s');


                // TODO → Corregir fallo al subir cuando se está validando

                ## Obtengo atributos y los validos para excluir posible basura.
                $attributes = $this->addValidate(get_object_vars($d));
                $model->fill($attributes);
                // TEMPORAL:
                //$z = get_object_vars($d);
                if (is_array($attributes)) {
                    $model->fill($attributes);
                    $model->save();
                } else {
                    $fallidos++;
                }

                //$model->save();
            } catch (Exception $e) {
                Log::error('Error insertando datos en contador de pulsaciones');
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
            'smartbonsai_plant_id' => 'required|numeric',
            'uv' => 'nullable|numeric',
            'temperature' => 'nullable|numeric',
            'humidity' => 'nullable|numeric',
            'soil_humidity' => 'required|numeric',
            'full_water_tank' => 'nullable|boolean',
            'waterpump_enabled' => 'nullable|boolean',
            'vaporizer_enabled' => 'nullable|boolean',
            //'created_at' => 'required|date_format:Y-m-d H:i:s',
        ])->validate();
    }
}
