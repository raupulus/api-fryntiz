<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\WeatherStation\V2;

use App\Enums\HardwareLocationTypeEnum;
use App\Http\Requests\Api\BaseFormRequest;
use App\Http\Resources\V2\WeatherStation\WeatherStationResource;
use Illuminate\Validation\Rule;

/**
 * Validación para consultar las estaciones de una zona.
 *
 * Endpoint público de lectura: acepta `sensors` (lista separada por comas) y
 * `location_type` (indoor/outdoor) para acotar dentro de la zona.
 */
class ShowZoneRequest extends BaseFormRequest
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
            'location_type' => ['sometimes', 'nullable', Rule::in(array_column(HardwareLocationTypeEnum::cases(), 'value'))],
        ];
    }

    public function messages(): array
    {
        return [
            'sensors.array' => 'El parametro sensors debe ser una lista de sensores.',
            'sensors.*.in' => 'Alguno de los sensores solicitados no es valido.',
            'location_type.in' => 'La ubicacion debe ser indoor u outdoor.',
        ];
    }
}
