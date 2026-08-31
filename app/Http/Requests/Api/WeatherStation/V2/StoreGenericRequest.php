<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\WeatherStation\V2;

use App\Http\Requests\Api\BaseFormRequest;
use App\Rules\KnownSensor;
use App\Rules\OwnedHardwareDevice;
use App\Support\WeatherStation\SensorCatalog;

/**
 * Validación del envío por lotes multi-sensor de la estación meteorológica.
 *
 * Es el endpoint que más se usa: el microcontrolador manda los 11 sensores en
 * una sola petición para no gastar radio en 11.
 *
 * Antes validaba esto y nada más:
 *
 *     'data'   => ['required', 'array', 'min:1'],
 *     'data.*' => ['required', 'array'],
 *
 * El contenido de cada sensor no pasaba por ningún FormRequest y `create()` lo
 * recibía tal cual. Por aquí entraba lo que por `/temperature/store` daba 422, y
 * lo que PostgreSQL rechazaba acababa en **500** (**N287**).
 *
 * Ahora cada sensor se valida con las mismas reglas que su endpoint individual.
 */
class StoreGenericRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * La estación viene en la URL (`POST /weather-stations/3/readings`), no en
     * el cuerpo. Se inyecta aquí para que las reglas de pertenencia sigan
     * aplicándose sobre `hardware_device_id` igual que antes.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'hardware_device_id' => $this->route('station'),
        ]);
    }

    public function rules(): array
    {
        // Las reglas de cada sensor salen del catálogo, que es el mismo sitio
        // del que las lee el endpoint individual. Antes estaban escritas dos
        // veces y se podían desincronizar sin que nada avisara: por aquí entraba
        // lo que por `/temperature/store` daba 422, y lo que PostgreSQL
        // rechazaba acababa en 500 (N287).
        $catalog = SensorCatalog::rulesByBatchKey();

        $rules = [
            'hardware_device_id' => ['required', 'integer', 'exists:hardware_devices,id', new OwnedHardwareDevice],
            'data' => ['required', 'array', 'min:1'],

            // Un sensor que no esté en la lista se rechaza en vez de tirarse en
            // silencio: `WeatherStationService::resolveSensorModel()` devuelve
            // null para los desconocidos y el lote se aceptaba igual.
            'data.*' => ['required', 'array', 'min:1'],
        ];

        $recibidos = $this->input('data');

        if (! is_array($recibidos)) {
            return $rules;
        }

        foreach (array_keys($recibidos) as $sensor) {
            if (! is_string($sensor) || ! isset($catalog[$sensor])) {
                // Deja que falle la regla de abajo con un mensaje entendible.
                $rules["data.{$sensor}"] = ['array', new KnownSensor(array_keys($catalog))];

                continue;
            }

            foreach ($catalog[$sensor] as $field => $fieldRules) {
                $rules["data.{$sensor}.*.{$field}"] = $fieldRules;
            }
        }

        return $rules;
    }
}
