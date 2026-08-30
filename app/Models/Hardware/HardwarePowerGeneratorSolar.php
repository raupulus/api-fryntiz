<?php

declare(strict_types=1);

namespace App\Models\Hardware;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

/**
 * La lectura de un controlador solar: un Renogy Rover (D109).
 *
 * **Es un generador**, y por eso hereda: mismos crudos, mismos derivados, misma
 * trazabilidad y los mismos scopes. Lo que añade es lo que un generador
 * genérico no tiene y el mapa Modbus del Rover sí: el bloque de estadísticas
 * **del día** y el de **acumulado histórico** desde el último reinicio del
 * controlador, que hasta ahora se perdían enteros por no haber columnas.
 *
 * Detección de reinicio (venía de V1 y se había perdido): `total_operating_days`
 * sólo puede subir. Si en una lectura **baja**, el controlador se ha reseteado y
 * sus contadores han vuelto a cero, así que esa lectura abre **fila nueva** y no
 * machaca la anterior. Sin esto, un reset borra el acumulado de años.
 *
 * @property int $id
 * @property int|null $hardware_device_id
 * @property int|null $hardware_energy_id
 * @property Carbon|null $date
 * @property Carbon|null $read_at
 * @property string|null $hardware
 * @property string|null $version
 * @property string|null $serial_number
 * @property string|null $battery_type
 * @property float|null $battery_voltage
 * @property float|null $battery_current
 * @property float|null $battery_power
 * @property int|null $battery_percentage
 * @property float|null $battery_temperature
 * @property float|null $temperature Temperatura del controlador (°C)
 * @property float|null $voltage Tensión del panel (V) — crudo
 * @property float|null $amperage Corriente media del panel (A) — crudo
 * @property float|null $power V·A del panel (W)
 * @property float|null $load_voltage
 * @property float|null $load_current
 * @property float|null $load_power
 * @property int|null $load_fan
 * @property float|null $day_battery_voltage_min
 * @property float|null $day_battery_voltage_max
 * @property float|null $day_charging_current_max
 * @property float|null $day_discharging_current_max
 * @property float|null $day_charging_power_max
 * @property float|null $day_discharging_power_max
 * @property float|null $day_charging_amp_hours
 * @property float|null $day_discharging_amp_hours
 * @property float|null $day_power_generation_wh
 * @property float|null $day_power_consumption_wh
 * @property int|null $total_operating_days
 * @property int|null $total_battery_over_discharges
 * @property int|null $total_battery_full_charges
 * @property float|null $total_charging_amp_hours
 * @property float|null $total_discharging_amp_hours
 * @property float|null $total_power_generation_wh
 * @property float|null $total_power_consumption_wh
 * @property float|null $system_voltage
 * @property float|null $system_intensity
 * @property int|null $nominal_battery_capacity
 *
 * @method static Builder<static>|HardwarePowerGeneratorSolar newModelQuery()
 * @method static Builder<static>|HardwarePowerGeneratorSolar newQuery()
 * @method static Builder<static>|HardwarePowerGeneratorSolar query()
 *
 * @mixin \Eloquent
 */
class HardwarePowerGeneratorSolar extends HardwarePowerGenerator
{
    protected $table = 'hardware_power_generators_solar';

    /** @var list<string> */
    protected $fillable = [
        'hardware_device_id', 'hardware_energy_id',
        'date', 'read_at',

        // Identificación del aparato.
        'hardware', 'version', 'serial_number', 'battery_type',

        // Batería.
        'battery_voltage', 'battery_current', 'battery_power',
        'battery_percentage', 'battery_temperature',
        'temperature',

        // Generación: los crudos y los derivados heredados.
        'voltage', 'amperage', 'delta_seconds',
        'power', 'energy_wh', 'energy_ah',
        'energy_source', 'voltage_source',
        'is_suspicious', 'suspicious_reason',
        'charging_status', 'charging_status_label',
        'light_status', 'light_brightness',

        // Salida de consumo del propio controlador.
        'load_voltage', 'load_current', 'load_power', 'load_fan',

        // Estadísticas del día.
        'day_battery_voltage_min', 'day_battery_voltage_max',
        'day_charging_current_max', 'day_discharging_current_max',
        'day_charging_power_max', 'day_discharging_power_max',
        'day_charging_amp_hours', 'day_discharging_amp_hours',
        'day_power_generation_wh', 'day_power_consumption_wh',

        // Acumulado desde el último reinicio del controlador.
        'total_operating_days', 'total_battery_over_discharges',
        'total_battery_full_charges', 'total_charging_amp_hours',
        'total_discharging_amp_hours', 'total_power_generation_wh',
        'total_power_consumption_wh',

        // Configuración que declara el controlador.
        'system_voltage', 'system_intensity', 'nominal_battery_capacity',
    ];

    /** @var array<string,string> */
    protected $casts = [
        'hardware_device_id' => 'integer',
        'hardware_energy_id' => 'integer',
        'date' => 'date',
        'read_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',

        'battery_voltage' => 'float',
        'battery_current' => 'float',
        'battery_power' => 'float',
        'battery_percentage' => 'integer',
        'battery_temperature' => 'float',
        'temperature' => 'float',

        'voltage' => 'float',
        'amperage' => 'float',
        'delta_seconds' => 'integer',
        'power' => 'float',
        'energy_wh' => 'float',
        'energy_ah' => 'float',
        'is_suspicious' => 'boolean',
        'charging_status' => 'integer',
        'light_status' => 'boolean',
        'light_brightness' => 'integer',

        'load_voltage' => 'float',
        'load_current' => 'float',
        'load_power' => 'float',
        'load_fan' => 'integer',

        'day_battery_voltage_min' => 'float',
        'day_battery_voltage_max' => 'float',
        'day_charging_current_max' => 'float',
        'day_discharging_current_max' => 'float',
        'day_charging_power_max' => 'float',
        'day_discharging_power_max' => 'float',
        'day_charging_amp_hours' => 'float',
        'day_discharging_amp_hours' => 'float',
        'day_power_generation_wh' => 'float',
        'day_power_consumption_wh' => 'float',

        'total_operating_days' => 'integer',
        'total_battery_over_discharges' => 'integer',
        'total_battery_full_charges' => 'integer',
        'total_charging_amp_hours' => 'float',
        'total_discharging_amp_hours' => 'float',
        'total_power_generation_wh' => 'float',
        'total_power_consumption_wh' => 'float',

        'system_voltage' => 'float',
        'system_intensity' => 'float',
        'nominal_battery_capacity' => 'integer',
    ];

    /**
     * Última lectura registrada del mismo controlador.
     *
     * Se identifica por número de serie cuando lo hay —es lo único que
     * distingue dos Rover conectados al mismo dispositivo— y si no, por
     * dispositivo.
     */
    public static function latestForDevice(int $hardwareDeviceId, ?string $serialNumber = null): ?self
    {
        return self::query()
            ->where('hardware_device_id', $hardwareDeviceId)
            ->when($serialNumber, static fn (Builder $q, string $serie) => $q->where('serial_number', $serie))
            ->orderByDesc('read_at')
            ->orderByDesc('id')
            ->first();
    }

    /**
     * ¿Se ha reiniciado el controlador entre la lectura anterior y ésta?
     *
     * `total_operating_days` es un contador que sólo sube. Si baja, el aparato
     * ha vuelto a cero y sus acumulados de antes ya no son comparables con los
     * de ahora: la lectura nueva empieza una serie distinta.
     */
    public static function hasRestarted(?self $previous, ?int $daysNow): bool
    {
        if ($previous === null || $daysNow === null || $previous->total_operating_days === null) {
            return false;
        }

        return $daysNow < $previous->total_operating_days;
    }
}
