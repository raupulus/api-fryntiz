<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\WeatherStation\V2;

use App\Http\Requests\Api\BaseFormRequest;
use App\Http\Resources\V2\WeatherStation\WeatherStationResource;
use Illuminate\Validation\Rule;

/**
 * Validación para consultar una estación meteorológica concreta.
 *
 * Endpoint público de lectura: acepta el parámetro opcional `sensors` (lista
 * separada por comas) para acotar los sensores devueltos.
 */
class ShowStationRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Normaliza `sensors` de "wind,temperature" a array antes de validar.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('sensors') && is_string($this->query('sensors'))) {
            $sensors = array_filter(array_map('trim', explode(',', (string) $this->query('sensors'))));
            $this->merge(['sensors' => array_values($sensors)]);
        }
    }

    public function rules(): array
    {
        return [
            'sensors' => ['sometimes', 'array'],
            'sensors.*' => [Rule::in(WeatherStationResource::SENSORS)],
        ];
    }

    public function messages(): array
    {
        return [
            'sensors.array' => 'El parametro sensors debe ser una lista de sensores.',
            'sensors.*.in' => 'Alguno de los sensores solicitados no es valido.',
        ];
    }
}
