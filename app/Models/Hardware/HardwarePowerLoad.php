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
 * Una lectura de consumo (D115).
 *
 * Guarda los tres crudos —corriente media del periodo, tensión y segundos que
 * cubre la media— y los derivados que salen de ellos. Los derivados son caché
 * para no recalcular millones de filas en cada consulta; la verdad son los
 * crudos, y no se tiran nunca.
 *
 * `power` es la potencia **media del periodo**, no la instantánea, y por eso
 * `SUM(power)` sigue sin ser vatios-hora: para eso está `energy_wh`.
 *
 * El despeje de `P = V·I` que hacía este modelo se ha ido al servicio, que es
 * quien conoce el elemento y por tanto su tensión nominal. Aquí se despejaba
 * contra el único voltaje de la petición, que es justo el fallo que se arregla.
 *
 * @property int $id
 * @property int|null $hardware_device_id Dispositivo que mide
 * @property int|null $hardware_energy_id Elemento medido
 * @property int|null $fan Velocidad del ventilador en RPM
 * @property float|null $temperature Temperatura del dispositivo (°C)
 * @property float|null $voltage Tensión del periodo (V) — crudo
 * @property float|null $amperage Corriente media del periodo (A) — crudo
 * @property int|null $delta_seconds Segundos que cubre la media — crudo
 * @property float|null $power V·A, potencia media del periodo (W)
 * @property float|null $energy_wh V·A·s/3600 — esto sí se suma
 * @property float|null $energy_ah A·s/3600 — esto sí se suma
 * @property string $energy_source device | derived
 * @property string $voltage_source measured | nominal
 * @property bool $is_suspicious
 * @property string|null $suspicious_reason
 * @property float|null $battery_voltage
 * @property int|null $battery_percentage
 * @property Carbon|null $read_at Cuándo se midió
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read HardwareDevice|null $hardwareDevice
 * @property-read HardwareEnergy|null $energy
 *
 * @method static Builder<static>|HardwarePowerLoad newModelQuery()
 * @method static Builder<static>|HardwarePowerLoad newQuery()
 * @method static Builder<static>|HardwarePowerLoad query()
 * @method static Builder<static>|HardwarePowerLoad forDevice(int $deviceId)
 * @method static Builder<static>|HardwarePowerLoad ofElement(int $energyId)
 * @method static Builder<static>|HardwarePowerLoad reliable()
 *
 * @mixin \Eloquent
 */
class HardwarePowerLoad extends BaseModel
{
    use BelongsToHardwareDevice;
    use HasFactory;
    use IsEnergyReading;

    protected $table = 'hardware_power_loads';

    protected $fillable = [
        'hardware_device_id', 'hardware_energy_id',
        'fan', 'temperature',
        'voltage', 'amperage', 'delta_seconds',
        'power', 'energy_wh', 'energy_ah',
        'energy_source', 'voltage_source',
        'is_suspicious', 'suspicious_reason',
        'battery_voltage', 'battery_percentage', 'read_at',
    ];

    protected $casts = [
        'hardware_device_id' => 'integer',
        'hardware_energy_id' => 'integer',
        'fan' => 'integer',
        'temperature' => 'float',
        'voltage' => 'float',
        'amperage' => 'float',
        'delta_seconds' => 'integer',
        'power' => 'float',
        'energy_wh' => 'float',
        'energy_ah' => 'float',
        'is_suspicious' => 'boolean',
        'battery_voltage' => 'float',
        'battery_percentage' => 'integer',
        'read_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
