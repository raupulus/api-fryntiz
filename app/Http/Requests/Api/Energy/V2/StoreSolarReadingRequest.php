<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\Energy\V2;

use App\Http\Requests\Api\BaseFormRequest;
use App\Rules\DeviceStatusPayload;
use App\Rules\OwnedHardwareDevice;
use Carbon\Carbon;

/**
 * Lectura de un controlador solar: el Renogy Rover (D109).
 *
 * El Rover es un aparato comercial: manda los nombres que le da la gana y no se
 * le va a cambiar el protocolo. Esta clase es **el único sitio donde se traduce
 * entre su vocabulario y el nuestro**, igual que hacía `main` en V1.
 *
 * Que quede claro por qué aquí sí hay tabla de alias y en `StoreEnergyRequest`
 * no: allí el firmware es nuestro y se ajusta al contrato; aquí el firmware es
 * de Renogy y el que se ajusta es el backend.
 *
 * Y una regla que no se salta: **un campo ausente deja NULL, nunca 0.** V1
 * casteaba a la brava (`(float) $this->campo`) y convertía «no tengo dato» en un
 * cero que contamina las medias.
 */
class StoreSolarReadingRequest extends BaseFormRequest
{
    /**
     * columna destino => nombres aceptados, por orden de preferencia.
     *
     * El primero es el nombre de la columna; los siguientes, los que manda el
     * firmware o los que usaba V1.
     *
     * @var array<string,list<string>>
     */
    private const ALIAS = [
        'hardware_device_id' => ['hardware_device_id', 'device_id'],

        // ── Batería ───────────────────────────────────────────────────────
        'battery_percentage' => ['battery_percentage', 'battery_soc'],
        'battery_temperature' => ['battery_temperature'],

        // El Rover llama `controller_temperature` a la del propio controlador.
        'temperature' => ['temperature', 'controller_temperature'],

        // ── Generación: la columna es `voltage`/`amperage`/`power`, heredada
        //    de `HardwarePowerGenerator`. El firmware dice `pv_*` o `solar_*`,
        //    y V1 decía `energy_*`.
        'voltage' => ['voltage', 'pv_voltage', 'solar_voltage', 'energy_voltage'],
        'amperage' => ['amperage', 'pv_current', 'solar_current', 'energy_amperage'],
        'power' => ['power', 'pv_power', 'solar_power', 'energy_power'],
        'charging_status' => ['charging_status', 'energy_charging_status'],
        'charging_status_label' => ['charging_status_label', 'energy_charging_status_label'],

        // ── Farola que gobierna el controlador ────────────────────────────
        'light_status' => ['light_status', 'street_light_status'],
        'light_brightness' => ['light_brightness', 'street_light_brightness'],

        // ── Salida de consumo del propio controlador ──────────────────────
        'load_current' => ['load_current', 'load_amperage'],
        'load_fan' => ['load_fan', 'fan'],

        // ── Estadísticas del día ──────────────────────────────────────────
        'day_battery_voltage_min' => ['day_battery_voltage_min', 'today_battery_min_voltage', 'battery_min_voltage'],
        'day_battery_voltage_max' => ['day_battery_voltage_max', 'today_battery_max_voltage', 'battery_max_voltage'],
        'day_charging_current_max' => ['day_charging_current_max', 'today_max_charging_current', 'today_energy_amperage_max'],
        'day_discharging_current_max' => ['day_discharging_current_max', 'today_max_discharging_current', 'today_load_amperage_max'],
        'day_charging_power_max' => ['day_charging_power_max', 'today_max_charging_power', 'today_energy_power_max'],
        'day_discharging_power_max' => ['day_discharging_power_max', 'today_max_discharging_power', 'today_load_power_max'],
        'day_charging_amp_hours' => ['day_charging_amp_hours', 'today_charging_amp_hours', 'today_energy_amperage'],
        'day_discharging_amp_hours' => ['day_discharging_amp_hours', 'today_discharging_amp_hours', 'today_load_amperage'],
        'day_power_generation_wh' => ['day_power_generation_wh', 'today_power_generation', 'today_energy_power'],
        'day_power_consumption_wh' => ['day_power_consumption_wh', 'today_power_consumption', 'today_load_power'],

        // ── Acumulado desde el último reinicio del controlador ────────────
        //
        // `total_operating_days` es el que detecta el reseteo: si baja, el
        // aparato ha vuelto a cero y la lectura abre fila nueva.
        'total_operating_days' => ['total_operating_days', 'historical_total_days_operating', 'days_operating'],
        'total_battery_over_discharges' => ['total_battery_over_discharges', 'historical_total_number_battery_over_discharges', 'number_battery_over_discharges'],
        'total_battery_full_charges' => ['total_battery_full_charges', 'historical_total_number_battery_full_charges', 'number_battery_full_charges'],
        'total_charging_amp_hours' => ['total_charging_amp_hours', 'historical_total_charging_amp_hours', 'historical_energy_amperage'],
        'total_discharging_amp_hours' => ['total_discharging_amp_hours', 'historical_total_discharging_amp_hours', 'historical_load_amperage'],
        'total_power_generation_wh' => ['total_power_generation_wh', 'historical_cumulative_power_generation', 'historical_energy_power'],
        'total_power_consumption_wh' => ['total_power_consumption_wh', 'historical_cumulative_power_consumption', 'historical_load_power'],

        // ── Configuración que declara el controlador ──────────────────────
        'system_voltage' => ['system_voltage', 'system_voltage_current'],
        'system_intensity' => ['system_intensity', 'system_intensity_current'],
    ];

    public function authorize(): bool
    {
        return auth()->check();
    }

    protected function prepareForValidation(): void
    {
        // Las tres fechas se sintetizan si el firmware no las manda: por eso el
        // Rover nunca recibe un 422 por no mandarlas.
        $readAt = Carbon::parse($this->read_at ?? $this->created_at ?? now());

        $merge = [
            'read_at' => $readAt,
            'date' => $this->date ?? $readAt->format('Y-m-d'),
        ];

        foreach (self::ALIAS as $destination => $accepted) {
            $value = $this->firstPresentValue($accepted);

            // `null` significa «el firmware no lo ha mandado». No se mete la
            // clave: así la columna queda NULL y no un 0 inventado.
            if ($value !== null) {
                $merge[$destination] = $value;
            }
        }

        if (isset($merge['hardware_device_id'])) {
            $merge['hardware_device_id'] = (int) $merge['hardware_device_id'];
        }

        // AD-T05 (auditoría de datos 2026-09-02): un Rover real mandó "MPPT" en
        // mayúsculas mezclado con "mppt" en minúsculas para el mismo estado. No
        // se restringe a una lista cerrada —el Rover manda lo que le da la
        // gana, y una lista rígida rechazaría un estado nuevo de firmware—,
        // solo se normaliza la capitalización para no partir en dos el mismo
        // valor al agruparlo o mostrarlo.
        if (isset($merge['charging_status_label']) && is_string($merge['charging_status_label'])) {
            $merge['charging_status_label'] = mb_strtolower($merge['charging_status_label']);
        }

        $this->merge($merge);
    }

    /**
     * Devuelve el primer alias que venga en la petición con valor, o `null`.
     *
     * @param  list<string>  $accepted
     */
    private function firstPresentValue(array $accepted): mixed
    {
        foreach ($accepted as $name) {
            if ($this->has($name) && $this->input($name) !== null && $this->input($name) !== '') {
                return $this->input($name);
            }
        }

        return null;
    }

    public function rules(): array
    {
        return [
            // AR-V01: este bloque llegaba sin validar y se lo comía
            // `HardwareService::updateDeviceStatus()`. El servicio filtra las
            // claves, pero no los valores.
            'hardware_device_info' => ['nullable', new DeviceStatusPayload],
            'hardware_device_id' => ['required', 'integer', 'exists:hardware_devices,id', new OwnedHardwareDevice],
            'date' => ['required', 'date'],
            'read_at' => ['required', 'date'],

            // Identificación del aparato.
            'hardware' => ['nullable', 'string', 'max:255'],
            'version' => ['nullable', 'string', 'max:255'],
            'serial_number' => ['nullable', 'string', 'max:255'],
            'battery_type' => ['nullable', 'string', 'max:255'],

            // Batería.
            'battery_voltage' => ['nullable', 'numeric'],
            'battery_current' => ['nullable', 'numeric'],
            'battery_power' => ['nullable', 'numeric'],
            'battery_percentage' => ['nullable', 'integer', 'min:0', 'max:100'],
            'battery_temperature' => ['nullable', 'numeric'],
            'temperature' => ['nullable', 'numeric'],

            // Generación.
            'voltage' => ['nullable', 'numeric'],
            'amperage' => ['nullable', 'numeric'],
            'power' => ['nullable', 'numeric'],
            'delta_seconds' => ['nullable', 'integer', 'min:1'],
            'charging_status' => ['nullable', 'integer'],
            'charging_status_label' => ['nullable', 'string', 'max:255'],
            'light_status' => ['nullable', 'boolean'],
            'light_brightness' => ['nullable', 'integer', 'min:0', 'max:100'],

            // Salida de consumo del controlador.
            'load_voltage' => ['nullable', 'numeric'],
            'load_current' => ['nullable', 'numeric'],
            'load_power' => ['nullable', 'numeric'],
            'load_fan' => ['nullable', 'integer', 'min:0'],

            // Estadísticas del día.
            'day_battery_voltage_min' => ['nullable', 'numeric'],
            'day_battery_voltage_max' => ['nullable', 'numeric'],
            'day_charging_current_max' => ['nullable', 'numeric'],
            'day_discharging_current_max' => ['nullable', 'numeric'],
            'day_charging_power_max' => ['nullable', 'numeric'],
            'day_discharging_power_max' => ['nullable', 'numeric'],
            'day_charging_amp_hours' => ['nullable', 'numeric'],
            'day_discharging_amp_hours' => ['nullable', 'numeric'],
            'day_power_generation_wh' => ['nullable', 'numeric'],
            'day_power_consumption_wh' => ['nullable', 'numeric'],

            // Acumulado del controlador.
            'total_operating_days' => ['nullable', 'integer', 'min:0'],
            'total_battery_over_discharges' => ['nullable', 'integer', 'min:0'],
            'total_battery_full_charges' => ['nullable', 'integer', 'min:0'],
            'total_charging_amp_hours' => ['nullable', 'numeric', 'min:0'],
            'total_discharging_amp_hours' => ['nullable', 'numeric', 'min:0'],
            'total_power_generation_wh' => ['nullable', 'numeric', 'min:0'],
            'total_power_consumption_wh' => ['nullable', 'numeric', 'min:0'],

            // Configuración del controlador.
            'system_voltage' => ['nullable', 'numeric'],
            'system_intensity' => ['nullable', 'numeric'],
            'nominal_battery_capacity' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
