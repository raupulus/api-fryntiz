<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\WeatherStation\V2;

use App\Http\Requests\Api\BaseFormRequest;
use App\Rules\OwnedHardwareDevice;
use App\Support\WeatherStation\SensorCatalog;

/**
 * Escritura de lecturas de un sensor concreto de una estación.
 *
 * Sustituye a los siete FormRequests que había (uno por sensor), que eran el
 * mismo fichero con distintas reglas dentro. Las reglas salen ahora de
 * `SensorCatalog`, que es también de donde las lee el lote multi-sensor:
 * antes estaban duplicadas y se podían desincronizar sin que nada avisara.
 *
 * La estación viene en la URL, no en el cuerpo: en
 * `POST /weather-stations/3/temperatures` la estación es el 3. El
 * `hardware_device_id` se inyecta desde la ruta para que las reglas de
 * pertenencia sigan aplicándose igual.
 *
 * Admite dos formas (C2, «dejamos añadir por lotes en cada sensor también»):
 *
 *   { "value": 21.4 }                          una lectura
 *   { "readings": [ {...}, {...}, {...} ] }    varias de golpe
 */
class StoreSensorReadingsRequest extends BaseFormRequest
{
    /** Tope de lecturas por lote: un microcontrolador no acumula más. */
    public const MAX_PER_BATCH = 500;

    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Mete la estación de la URL en el cuerpo y normaliza la forma a un lote.
     *
     * Así hay un solo camino de validación en vez de dos.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'hardware_device_id' => $this->route('station'),
        ]);

        if ($this->has('readings')) {
            return;
        }

        // Una lectura suelta se trata como un lote de una.
        $suelta = $this->except(['hardware_device_id', 'readings']);

        $this->merge(['readings' => $suelta === [] ? [] : [$suelta]]);
    }

    public function rules(): array
    {
        $rules = [
            'hardware_device_id' => ['required', 'integer', 'exists:hardware_devices,id', new OwnedHardwareDevice],
            'readings' => ['required', 'array', 'min:1', 'max:'.self::MAX_PER_BATCH],
            'readings.*' => ['required', 'array'],
        ];

        foreach (SensorCatalog::rulesFor($this->sensorSegment()) as $field => $fieldRules) {
            $rules["readings.*.{$field}"] = $fieldRules;
        }

        return $rules;
    }

    /**
     * Las lecturas ya validadas, listas para insertar.
     *
     * Se recortan a los campos que el sensor declara: lo que mande de más un
     * firmware antiguo no llega al `create()`.
     *
     * @return array<int, array<string, mixed>>
     */
    public function readings(): array
    {
        $allowed = array_keys(SensorCatalog::rulesFor($this->sensorSegment()));

        return array_values(array_map(
            static fn (array $reading): array => array_intersect_key($reading, array_flip($allowed)),
            $this->validated('readings')
        ));
    }

    /**
     * Segmento del sensor en la URL (`temperatures`, `wind-directions`…).
     */
    public function sensorSegment(): string
    {
        return (string) $this->route('sensor');
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
            'readings.required' => 'No se ha enviado ninguna lectura.',
        ];
    }
}
