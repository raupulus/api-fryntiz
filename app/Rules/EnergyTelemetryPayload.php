<?php

declare(strict_types=1);

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Validator;

/**
 * Valida el bloque de telemetría de energía `energy` en peticiones IoT (D115, Fase 4).
 *
 * Estructura esperada:
 * {
 *   "duration": 60, // Opcional, segundos del intervalo
 *   "generator": { ... }, // Opcional
 *   "battery": { ... },   // Opcional
 *   "loads": [ { ... } ]  // Opcional
 * }
 */
class EnergyTelemetryPayload implements ValidationRule
{
    /**
     * Reglas de validación para el bloque principal energy.
     *
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [
            'duration' => ['nullable', 'integer', 'min:1'],

            'generator' => ['nullable', 'array'],
            'generator.voltage' => ['nullable', 'numeric'],
            'generator.amperage' => ['nullable', 'numeric'],
            'generator.power' => ['nullable', 'numeric'],
            'generator.temperature' => ['nullable', 'numeric'],
            'generator.fan' => ['nullable', 'integer', 'min:0'],
            'generator.charging_status' => ['nullable', 'integer'],
            'generator.charging_status_label' => ['nullable', 'string', 'max:255'],
            'generator.light_status' => ['nullable', 'boolean'],
            'generator.light_brightness' => ['nullable', 'integer', 'between:0,100'],
            'generator.today_energy_wh' => ['nullable', 'numeric', 'min:0'],
            'generator.historical_energy_wh' => ['nullable', 'numeric', 'min:0'],

            'battery' => ['nullable', 'array'],
            'battery.voltage' => ['nullable', 'numeric'],
            'battery.amperage' => ['nullable', 'numeric'],
            'battery.power' => ['nullable', 'numeric'],
            'battery.soc' => ['nullable', 'numeric', 'between:0,100'],
            'battery.battery_percentage' => ['nullable', 'numeric', 'between:0,100'],
            'battery.temperature' => ['nullable', 'numeric'],
            'battery.charging_status' => ['nullable', 'integer'],
            'battery.charging_status_label' => ['nullable', 'string', 'max:255'],
            'battery.today_energy_ah' => ['nullable', 'numeric', 'min:0'],
            'battery.historical_energy_ah' => ['nullable', 'numeric', 'min:0'],
            'battery.battery_full_charges' => ['nullable', 'integer', 'min:0'],
            'battery.battery_over_discharges' => ['nullable', 'integer', 'min:0'],

            'loads' => ['nullable', 'array'],
            'loads.*' => ['required', 'array'],
            'loads.*.channel' => ['nullable', 'integer', 'min:0'],
            'loads.*.sensor_position' => ['nullable', 'integer', 'min:0'],
            'loads.*.voltage' => ['nullable', 'numeric'],
            'loads.*.amperage' => ['nullable', 'numeric'],
            'loads.*.power' => ['nullable', 'numeric'],
            'loads.*.temperature' => ['nullable', 'numeric'],
            'loads.*.fan' => ['nullable', 'integer', 'min:0'],
            'loads.*.today_energy_wh' => ['nullable', 'numeric', 'min:0'],
            'loads.*.historical_energy_wh' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    /**
     * Mensajes personalizados en español con tildes.
     *
     * @return array<string, string>
     */
    public static function messages(): array
    {
        return [
            'duration.integer' => 'La duración del intervalo debe ser un número entero de segundos.',
            'duration.min' => 'La duración del intervalo debe ser al menos de 1 segundo.',
            'generator.array' => 'El bloque del generador debe ser un objeto.',
            'generator.voltage.numeric' => 'La tensión del generador debe ser numérica.',
            'generator.amperage.numeric' => 'La corriente del generador debe ser numérica.',
            'generator.power.numeric' => 'La potencia del generador debe ser numérica.',
            'generator.light_brightness.between' => 'El brillo de iluminación del generador debe estar entre 0 y 100.',
            'battery.array' => 'El bloque de la batería debe ser un objeto.',
            'battery.voltage.numeric' => 'La tensión de la batería debe ser numérica.',
            'battery.amperage.numeric' => 'La corriente de la batería debe ser numérica.',
            'battery.power.numeric' => 'La potencia de la batería debe ser numérica.',
            'battery.soc.between' => 'El estado de carga (SOC) de la batería debe estar comprendido entre 0 y 100 %.',
            'battery.battery_percentage.between' => 'El porcentaje de la batería debe estar comprendido entre 0 y 100 %.',
            'loads.array' => 'El bloque de consumos debe ser una lista de cargas.',
            'loads.*.channel.integer' => 'El canal del consumo debe ser un entero mayor o igual a 0.',
            'loads.*.voltage.numeric' => 'La tensión del consumo debe ser numérica.',
            'loads.*.amperage.numeric' => 'La corriente del consumo debe ser numérica.',
            'loads.*.power.numeric' => 'La potencia del consumo debe ser numérica.',
        ];
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null) {
            return;
        }

        if (! is_array($value)) {
            $fail('El bloque energy debe ser un objeto estructurado.');

            return;
        }

        // Comprobar que al menos uno de los 3 subsistemas esté presente y no vacío
        $hasGenerator = isset($value['generator']) && is_array($value['generator']) && $value['generator'] !== [];
        $hasBattery = isset($value['battery']) && is_array($value['battery']) && $value['battery'] !== [];
        $hasLoads = isset($value['loads']) && is_array($value['loads']) && $value['loads'] !== [];

        if (! $hasGenerator && ! $hasBattery && ! $hasLoads) {
            $fail('El bloque energy debe contener al menos uno de los subsistemas: generator, battery o loads.');

            return;
        }

        $validator = Validator::make($value, self::rules(), self::messages());

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $fail($error);
            }
        }
    }
}
