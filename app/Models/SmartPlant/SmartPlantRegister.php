<?php

declare(strict_types=1);

namespace App\Models\SmartPlant;

use App\Models\BaseModels\BaseModel;
use App\Models\Hardware\HardwareDevice;
use App\Traits\BelongsToHardwareDevice;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Representa los registros de sensores para las lecturas tomadas a las
 * plantas asociadas en cada momento.
 *
 * @property int $id
 * @property int $plant_id
 * @property int|null $hardware_device_id Dispositivo asociado
 * @property int|null $uv Cantidad rayos UV en el ambiente
 * @property numeric|null $temperature Cantidad de temperatura en el ambiente
 * @property numeric|null $pressure Cantidad de temperatura en el ambiente
 * @property numeric|null $humidity Cantidad de humedad en el ambiente
 * @property int $soil_humidity Humedad del suelo en porcentaje, rango 1-100
 * @property int|null $soil_humidity_raw Humedad del suelo en valor bruto de resistencia al medir.
 * @property bool $full_water_tank Indica si hay agua en el tanque de agua
 * @property bool $waterpump_enabled Indica si se ha activado la bomba de agua
 * @property bool $vaporizer_enabled Indica si se ha activado el vaporizador
 * @property Carbon|null $created_at
 * @property-read HardwareDevice|null $hardwareDevice
 * @property-read SmartPlantPlant $plant
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SmartPlantRegister newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SmartPlantRegister newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SmartPlantRegister query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SmartPlantRegister whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SmartPlantRegister whereFullWaterTank($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SmartPlantRegister whereHardwareDeviceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SmartPlantRegister whereHumidity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SmartPlantRegister whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SmartPlantRegister wherePlantId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SmartPlantRegister wherePressure($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SmartPlantRegister whereSoilHumidity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SmartPlantRegister whereSoilHumidityRaw($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SmartPlantRegister whereTemperature($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SmartPlantRegister whereUv($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SmartPlantRegister whereVaporizerEnabled($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SmartPlantRegister whereWaterpumpEnabled($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SmartPlantRegister forDevice(int $deviceId)
 *
 * @mixin \Eloquent
 */
class SmartPlantRegister extends BaseModel
{
    use BelongsToHardwareDevice;

    protected $table = 'smartplant_registers';

    protected $fillable = [
        'plant_id',
        'hardware_device_id',
        'uv',
        'pressure',
        'temperature',
        'humidity',
        'soil_humidity',
        'soil_humidity_raw',
        'full_water_tank',
        'waterpump_enabled',
        'vaporizer_enabled',
    ];

    public function setUpdatedAt($value)
    {
        // Desactivo el updated_at
    }

    public function plant(): BelongsTo
    {
        return $this->belongsTo(SmartPlantPlant::class, 'plant_id');
    }
}
