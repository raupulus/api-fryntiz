<?php

declare(strict_types=1);

namespace App\Models\Hardware;

use App\Http\Traits\ImageTrait;
use App\Models\BaseModels\BaseModel;
use App\Models\File;
use App\Models\User;
use App\Traits\BelongsToUser;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Http\Request;

use function array_filter;

/**
 * Class HardwareDeviceController
 *
 * @property int $id
 * @property int|null $user_id Usuario asociado
 * @property int|null $image_id Relación con la imagen asociada
 * @property int|null $hardware_type_id Relación con el tipo de hardware asociado
 * @property int|null $referred_thing_id Relación con el dispositivo afiliado
 * @property string|null $name Nombre real del dispositivo, EJ: Raspberry Pi 4b+
 * @property string|null $name_friendly Nombre amistoso para reconocerlo EJ: Raspberry en azotea
 * @property string|null $ref Referencia del dispositivo
 * @property string|null $brand Marca del fabricante, EJ: Apple
 * @property string|null $model Modelo del dispositivo, EJ: 4b+
 * @property string|null $software_version Versión del software del dispositivo
 * @property string|null $hardware_version Versión del hardware del dispositivo
 * @property string|null $serial_number Número de serie del dispositivo
 * @property string|null $battery_type Tipo de batería, EJ: Gel, li ion
 * @property int|null $battery_nominal_capacity Capacidad nominal de la batería en mAh, EJ: 4200
 * @property string|null $url_company Enlace a la página de la empresa fabricante
 * @property string|null $description Descripción del dispositivo.
 * @property string|null $buy_at Fecha de compra del dispositivo
 * @property string|null $last_seen_at Última vez que se vio el dispositivo
 * @property string|null $ip_local Ip local del dispositivo
 * @property string|null $ip_public Ip pública del dispositivo
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $deleted_at
 * @property-read Collection<int, HardwareComponent> $components
 * @property-read int|null $components_count
 * @property-read mixed $current_energy_statistics
 * @property-read mixed $energy_statistics
 * @property-read string $url_image
 * @property-read string $url_image_large
 * @property-read string $url_image_medium
 * @property-read string $url_image_micro
 * @property-read string $url_image_normal
 * @property-read string $url_image_small
 * @property-read Collection<int, HardwareEnergy> $hardwareEnergy
 * @property-read int|null $hardware_energy_count
 * @property-read Collection<int, HardwareDevice> $hardwareEnergyGenerator
 * @property-read int|null $hardware_energy_generator_count
 * @property-read Collection<int, HardwareDevice> $hardwareEnergyLoad
 * @property-read int|null $hardware_energy_load_count
 * @property-read HardwareType|null $hardwareType
 * @property-read File|null $image
 * @property-read Collection<int, HardwarePowerGeneratorToday> $powerGeneratorToday
 * @property-read int|null $power_generator_today_count
 * @property-read Collection<int, HardwarePowerGenerator> $powerGenerators
 * @property-read int|null $power_generators_count
 * @property-read Collection<int, HardwarePowerGeneratorHistorical> $powerGeneratorsHistorical
 * @property-read int|null $power_generators_historical_count
 * @property-read Collection<int, HardwarePowerLoad> $powerLoads
 * @property-read int|null $power_loads_count
 * @property-read Collection<int, HardwarePowerLoadHistorical> $powerLoadsHistorical
 * @property-read int|null $power_loads_historical_count
 * @property-read Collection<int, HardwarePowerLoadToday> $powerLoadsToday
 * @property-read int|null $power_loads_today_count
 * @property-read HardwareType|null $type
 * @property-read User|null $user
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HardwareDevice forUser(int $userId)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HardwareDevice newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HardwareDevice newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HardwareDevice query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HardwareDevice whereBatteryNominalCapacity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HardwareDevice whereBatteryType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HardwareDevice whereBrand($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HardwareDevice whereBuyAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HardwareDevice whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HardwareDevice whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HardwareDevice whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HardwareDevice whereHardwareTypeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HardwareDevice whereHardwareVersion($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HardwareDevice whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HardwareDevice whereImageId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HardwareDevice whereIpLocal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HardwareDevice whereIpPublic($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HardwareDevice whereLastSeenAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HardwareDevice whereModel($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HardwareDevice whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HardwareDevice whereNameFriendly($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HardwareDevice whereRef($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HardwareDevice whereReferredThingId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HardwareDevice whereSerialNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HardwareDevice whereSoftwareVersion($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HardwareDevice whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HardwareDevice whereUrlCompany($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HardwareDevice whereUserId($value)
 *
 * @mixin \Eloquent
 */
class HardwareDevice extends BaseModel
{
    use BelongsToUser, HasFactory, ImageTrait;

    protected $table = 'hardware_devices';

    protected $fillable = ['user_id', 'hardware_type_id', 'referred_thing_id',
        'name', 'name_friendly', 'ref', 'model', 'brand', 'software_version',
        'hardware_version', 'serial_number', 'battery_type', 'battery_nominal_capacity',
        'url_company', 'description', 'buy_at', 'last_seen_at', 'ip_local', 'ip_public'];

    protected $casts = [
        'buy_at' => 'datetime',
    ];

    /**
     * Relación con el tipo de hardware.
     */
    public function type(): BelongsTo
    {
        return $this->belongsTo(HardwareType::class, 'hardware_type_id', 'id');
    }

    /**
     * Alias para compatibilidad con Filament Resources.
     */
    public function hardwareType(): BelongsTo
    {
        return $this->belongsTo(HardwareType::class, 'hardware_type_id', 'id');
    }

    /**
     * Componentes instalados en el dispositivo.
     */
    public function components(): HasMany
    {
        return $this->hasMany(HardwareComponent::class, 'hardware_device_id', 'id');
    }

    public function powerGenerators(): HasMany
    {
        return $this->hasMany(HardwarePowerGenerator::class, 'hardware_device_id', 'id');
    }

    public function powerGeneratorToday(): HasMany
    {
        return $this->hasMany(HardwarePowerGeneratorToday::class, 'hardware_device_id', 'id');
    }

    public function powerGeneratorsHistorical(): HasMany
    {
        return $this->hasMany(HardwarePowerGeneratorHistorical::class, 'hardware_device_id', 'id');
    }

    public function powerLoads(): HasMany
    {
        return $this->hasMany(HardwarePowerLoad::class, 'hardware_device_id', 'id');
    }

    public function powerLoadsToday(): HasMany
    {
        return $this->hasMany(HardwarePowerLoadToday::class, 'hardware_device_id', 'id');
    }

    public function powerLoadsHistorical(): HasMany
    {
        return $this->hasMany(HardwarePowerLoadHistorical::class, 'hardware_device_id', 'id');
    }

    /**
     * Devuelve todos los dispositivos de energía asociados al dispositivo.
     * Sin distinguir entre generador y carga (consumo de energía)
     */
    public function hardwareEnergy(): HasMany
    {
        return $this->hasMany(HardwareEnergy::class, 'hardware_device_id', 'id');
    }

    /**
     * Devuelve todos los dispositivos de energía asociados al dispositivo.
     * Filtrando solo por los que son de tipo generador.
     *
     * @return HasMany
     */
    public function hardwareEnergyGenerator(): BelongsToMany
    {
        return $this->belongsToMany(self::class, 'hardware_energy', 'hardware_device_id', 'hardware_device_monitorized_id')
            ->whereIn('is_generator', [true])
            ->orderBy('sensor_position');
    }

    /**
     * Devuelve solo los dispositivos de energía que consumen carga (energía).
     * Se descartan los que son generadores.
     *
     * @return HasMany
     */
    public function hardwareEnergyLoad(): BelongsToMany
    {
        return $this->belongsToMany(self::class, 'hardware_energy', 'hardware_device_id', 'hardware_device_monitorized_id')
            ->whereNotIn('is_generator', [true])
            ->orderBy('sensor_position');
    }

    /**
     * Relación con la imagen asociada al curriculum.
     */
    public function image(): HasOne
    {
        return $this->hasOne(File::class, 'id', 'image_id');
    }

    public static function createModel(HardwareDevice $device, $request) {}

    public function updateModel(Request $request)
    {
        // $dataFromSolar = $request->only(['hardware', 'version',
        //    'serial_number','battery_type', 'nominal_battery_capacity']);

        $data = [
            'serial_number' => $request->get('serial_number'),
            'hardware_version' => $request->get('hardware_version') ?? $request->get('hardware'),
            'software_version' => $request->get('software_version') ?? $request->get('version'),
            'battery_type' => $request->get('battery_type'),
            'battery_nominal_capacity' => $request->get('battery_nominal_capacity') ?? $request->get('nominal_battery_capacity'),
        ];

        $this->fill(array_filter($data, 'strlen'));

        return $this;
    }

    public function getEnergyStatisticsAttribute()
    {
        $powerGenerators = $this->powerGenerators()->get();
        $powerLoads = $this->powerLoads()->get();

        $powerGeneratorsToday = $this->powerGeneratorToday()->get();
        $powerLoadsToday = $this->powerLoadsToday()->get();

        $powerGeneratorsHistorical = $this->powerGeneratorsHistorical()->get();
        $powerLoadsHistorical = $this->powerLoadsHistorical()->get();

        return [
            'powerGenerators' => $powerGenerators,
            'powerLoads' => $powerLoads,
            'powerGeneratorsToday' => $powerGeneratorsToday,
            'powerLoadsToday' => $powerLoadsToday,
            'powerGeneratorsHistorical' => $powerGeneratorsHistorical,
            'powerLoadsHistorical' => $powerLoadsHistorical,
        ];
    }

    public function getCurrentEnergyStatisticsAttribute()
    {
        $now = Carbon::now();
        $lastHour = $now->copy()->subHour();
        $day = $now->format('Y-m-d');

        $generatorCurrent = $this->powerGenerators()
            ->where('read_at', '>=', $lastHour)
            ->orderByDesc('read_at')
            ->first();
        $loadCurrent = $this->powerLoads()
            ->where('read_at', '>=', $lastHour)
            ->orderByDesc('read_at')
            ->first();

        $generatorToday = $this->powerGeneratorToday()
            ->where('read_at', '>=', $day)
            ->orderByDesc('read_at')
            ->first();
        $loadToday = $this->powerLoadsToday()
            ->where('read_at', '>=', $day)
            ->orderByDesc('read_at')
            ->first();

        $generatorsHistorical = $this->powerGeneratorsHistorical()->get();
        $loadsHistorical = $this->powerLoadsHistorical()->get();

        return (object) [
            'generatorCurrent' => $generatorCurrent,
            'loadCurrent' => $loadCurrent,
            'generatorToday' => $generatorToday,
            'loadToday' => $loadToday,
            'generatorsHistorical' => $generatorsHistorical,
            'loadsHistorical' => $loadsHistorical,
        ];
    }
}
