<?php

declare(strict_types=1);

namespace App\Http\Resources\V2\Energy;

use App\Models\Hardware\HardwarePowerGeneratorSolar;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Una lectura de controlador solar (D109).
 *
 * La versión anterior leía tres claves que no eran columnas y por eso salían a
 * `null` en **todas** las respuestas (**R-4**, **N280**): `device_id`,
 * `load_amperage` y `energy_*`. Aquí no hay claves duplicadas con el mismo dato
 * detrás: un dato, un nombre.
 *
 * Los bloques `day` y `total` son los que el controlador informa por Modbus y
 * que hasta ahora se perdían enteros por no haber columnas donde guardarlos.
 *
 * @mixin HardwarePowerGeneratorSolar
 */
class SolarReadingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'hardware_device_id' => $this->hardware_device_id,
            'hardware_energy_id' => $this->hardware_energy_id,
            'date' => $this->date,
            'read_at' => $this->read_at?->toISOString(),

            'controller' => [
                'hardware' => $this->hardware,
                'version' => $this->version,
                'serial_number' => $this->serial_number,
                'temperature' => $this->temperature,
                'system_voltage' => $this->system_voltage,
                'system_intensity' => $this->system_intensity,
                'nominal_battery_capacity' => $this->nominal_battery_capacity,
            ],

            'battery' => [
                'type' => $this->battery_type,
                'voltage' => $this->battery_voltage,
                'current' => $this->battery_current,
                'power' => $this->battery_power,
                'percentage' => $this->battery_percentage,
                'temperature' => $this->battery_temperature,
            ],

            'generation' => [
                'voltage' => $this->voltage,
                'amperage' => $this->amperage,
                'power' => $this->power,
                'energy_wh' => $this->energy_wh,
                'energy_ah' => $this->energy_ah,
                'charging_status' => $this->charging_status,
                'charging_status_label' => $this->charging_status_label,
            ],

            'load' => [
                'voltage' => $this->load_voltage,
                'current' => $this->load_current,
                'power' => $this->load_power,
                'fan' => $this->load_fan,
            ],

            'street_light' => [
                'status' => $this->light_status,
                'brightness' => $this->light_brightness,
            ],

            'day' => [
                'battery_voltage_min' => $this->day_battery_voltage_min,
                'battery_voltage_max' => $this->day_battery_voltage_max,
                'charging_current_max' => $this->day_charging_current_max,
                'discharging_current_max' => $this->day_discharging_current_max,
                'charging_power_max' => $this->day_charging_power_max,
                'discharging_power_max' => $this->day_discharging_power_max,
                'charging_amp_hours' => $this->day_charging_amp_hours,
                'discharging_amp_hours' => $this->day_discharging_amp_hours,
                'power_generation_wh' => $this->day_power_generation_wh,
                'power_consumption_wh' => $this->day_power_consumption_wh,
            ],

            // Acumulado desde el último reinicio del controlador. Si
            // `operating_days` baja respecto a la lectura anterior, el aparato
            // se ha reseteado y esta fila empieza una serie nueva.
            'total' => [
                'operating_days' => $this->total_operating_days,
                'battery_over_discharges' => $this->total_battery_over_discharges,
                'battery_full_charges' => $this->total_battery_full_charges,
                'charging_amp_hours' => $this->total_charging_amp_hours,
                'discharging_amp_hours' => $this->total_discharging_amp_hours,
                'power_generation_wh' => $this->total_power_generation_wh,
                'power_consumption_wh' => $this->total_power_consumption_wh,
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
}
