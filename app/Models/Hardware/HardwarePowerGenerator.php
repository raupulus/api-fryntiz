<?php

declare(strict_types=1);

namespace App\Models\Hardware;

use App\Models\BaseModels\BaseModel;
use App\Traits\BelongsToHardwareDevice;
use App\Traits\IsEnergyReading;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Carbon;

/**
 * Una lectura de generación (D115).
 *
 * Misma estructura que la de consumo —crudos, derivados y trazabilidad— más lo
 * que sólo tiene un generador: el estado de carga que informa el controlador y
 * la luz de calle.
 *
 * **Nada de corrientes con signo** (D110). Una corriente negativa es una avería
 * de cableado, no un dato: se guarda tal cual y se marca sospechosa. El estado
 * de carga es un campo propio, `charging_status`, y no se deduce del signo.
 *
 * @property int $id
 * @property int|null $hardware_device_id Dispositivo que mide
 * @property int|null $hardware_energy_id Elemento medido
 * @property float|null $battery_voltage
 * @property float|null $battery_temperature
 * @property float|null $temperature Temperatura del aparato (°C)
 * @property int|null $battery_percentage
 * @property int|null $charging_status Código interno del fabricante
 * @property string|null $charging_status_label deactivated, mppt, boost, floating…
 * @property float|null $amperage Corriente media del periodo (A) — crudo
 * @property float|null $voltage Tensión del periodo (V) — crudo
 * @property int|null $delta_seconds Segundos que cubre la media — crudo
 * @property float|null $power V·A, potencia media del periodo (W)
 * @property float|null $energy_wh V·A·s/3600 — esto sí se suma
 * @property float|null $energy_ah A·s/3600 — esto sí se suma
 * @property string $energy_source device | derived
 * @property string $voltage_source measured | nominal
 * @property bool $is_suspicious
 * @property string|null $suspicious_reason
 * @property bool|null $light_status
 * @property int|null $light_brightness
 * @property Carbon|null $read_at Cuándo se midió
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read HardwareDevice|null $hardwareDevice
 * @property-read HardwareEnergy|null $energy
 *
 * @method static Builder<static>|HardwarePowerGenerator newModelQuery()
 * @method static Builder<static>|HardwarePowerGenerator newQuery()
 * @method static Builder<static>|HardwarePowerGenerator query()
 * @method static Builder<static>|HardwarePowerGenerator forDevice(int $deviceId)
 * @method static Builder<static>|HardwarePowerGenerator ofElement(int $energyId)
 * @method static Builder<static>|HardwarePowerGenerator reliable()
 *
 * @mixin \Eloquent
 */
class HardwarePowerGenerator extends BaseModel
{
    use BelongsToHardwareDevice;
    use HasFactory;
    use IsEnergyReading;

    protected $table = 'hardware_power_generators';

    protected $fillable = [
        'hardware_device_id', 'hardware_energy_id',
        'battery_voltage', 'battery_temperature', 'battery_percentage', 'temperature',
        'charging_status', 'charging_status_label',
        'voltage', 'amperage', 'delta_seconds',
        'power', 'energy_wh', 'energy_ah',
        'energy_source', 'voltage_source',
        'is_suspicious', 'suspicious_reason',
        'light_status', 'light_brightness', 'read_at',
    ];

    protected $casts = [
        'hardware_device_id' => 'integer',
        'hardware_energy_id' => 'integer',
        'battery_voltage' => 'float',
        'battery_temperature' => 'float',
        'temperature' => 'float',
        'battery_percentage' => 'integer',
        'charging_status' => 'integer',
        'voltage' => 'float',
        'amperage' => 'float',
        'delta_seconds' => 'integer',
        'power' => 'float',
        'energy_wh' => 'float',
        'energy_ah' => 'float',
        'is_suspicious' => 'boolean',
        'light_status' => 'boolean',
        'light_brightness' => 'integer',
        'read_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
