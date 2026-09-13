<?php

declare(strict_types=1);

namespace App\Http\Resources\V2\Energy;

use App\Models\Hardware\HardwareEnergyReading;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Recurso JSON unificado para lecturas de energía (D115, Fase 5).
 *
 * Las magnitudes se redondean a la precisión de su columna. Sin eso, el mismo
 * recurso salía con distinta precisión según de dónde viniera: recién guardado
 * llevaba el float entero del cálculo (`1.1166666666666667`) y releído de la
 * base el valor ya recortado por el `decimal` de la columna (`1.1167`). Un
 * cliente que compare lo que le devolvió el POST con lo que le devuelve el GET
 * no debería ver dos números distintos para la misma lectura.
 *
 * @mixin HardwareEnergyReading
 */
class EnergyReadingResource extends JsonResource
{
    /**
     * Decimales de cada magnitud, los mismos que declara su columna.
     */
    private const PRECISION = [
        'voltage' => 3,
        'amperage' => 3,
        'power' => 3,
        'energy_wh' => 4,
        'energy_ah' => 4,
        'battery_voltage' => 2,
        'temperature' => 3,
    ];

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'hardware_device_id' => $this->hardware_device_id,
            'hardware_energy_id' => $this->hardware_energy_id,
            'role' => $this->hardwareEnergy?->role,

            'measured' => [
                'amperage' => $this->redondea('amperage', $this->amperage),
                'voltage' => $this->redondea('voltage', $this->voltage),
                'power' => $this->redondea('power', $this->power),
                'delta_seconds' => $this->delta_seconds,
                'temperature' => $this->redondea('temperature', $this->temperature),
                'battery_voltage' => $this->redondea('battery_voltage', $this->battery_voltage),
                'battery_percentage' => $this->battery_percentage,
                'fan' => $this->fan,
                'charging_status' => $this->charging_status,
                'charging_status_label' => $this->charging_status_label,
                'light_status' => $this->light_status,
                'light_brightness' => $this->light_brightness,
            ],

            'derived' => [
                'energy_wh' => $this->redondea('energy_wh', $this->energy_wh),
                'energy_ah' => $this->redondea('energy_ah', $this->energy_ah),
            ],

            'sources' => [
                'energy' => $this->energy_source,
                'voltage' => $this->voltage_source,
            ],

            'is_suspicious' => (bool) $this->is_suspicious,
            'suspicious_reason' => $this->suspicious_reason,

            'created_at' => $this->created_at?->toISOString(),
        ];
    }

    /**
     * Redondea una magnitud a los decimales de su columna, respetando el null.
     */
    private function redondea(string $campo, mixed $valor): ?float
    {
        return $valor === null ? null : round((float) $valor, self::PRECISION[$campo]);
    }
}
