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

    /**
     * Sólo lo que el mensaje por defecto no puede decir.
     *
     * El resto salía de aquí escrito a mano —98 cadenas repartidas por 19
     * ficheros, la mitad sin tildes y todas sólo en español— para acabar
     * diciendo lo mismo que ya dice `lang/{es,en}/validation.php`. Los nombres
     * de campo viven ahora en su bloque `attributes`, así que «El campo
     * hardware_device_id es obligatorio» sale ya como «El campo dispositivo es
     * obligatorio», en los dos idiomas y para todas las reglas.
     *
     * @return array<string,string>
     */
    public function messages(): array
    {
        return [
            'sensors.*.in' => 'Alguno de los sensores solicitados no existe.',
        ];
    }
}
