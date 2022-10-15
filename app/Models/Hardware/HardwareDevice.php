<?php

namespace App\Models\Hardware;

use App\Http\Requests\Api\Hardware\V1\StoreSolarChargeRequest;
use App\Http\Traits\ImageTrait;
use App\Models\BaseModels\BaseModel;
use App\Models\File;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Request;
use function array_filter;

/**
 * Class HardwareDeviceController
 *
 * @package App\Models\Hardware
 */
class HardwareDevice extends BaseModel
{
    use HasFactory, ImageTrait;

    protected $table = 'hardware_devices';

    protected $fillable = ['user_id', 'hardware_type_id', 'referred_thing_id',
        'name', 'name_friendly', 'ref', 'model', 'brand', 'software_version',
        'hardware_version', 'serial_number', 'battery_type', 'battery_nominal_capacity',
        'url_company', 'description', 'buy_at'];

    protected $dates = ['created_at', 'updated_at', 'deleted_at', 'buy_at'];

    /**
     *
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function powerGenerators()
    {
        return $this->hasMany(HardwarePowerGenerator::class, 'hardware_device_id', 'id');
    }

    /**
     *
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function powerGeneratorToday()
    {
        return $this->hasMany(HardwarePowerGeneratorToday::class, 'hardware_device_id', 'id');
    }

    /**
     *
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function powerGeneratorsHistorical()
    {
        return $this->hasMany(HardwarePowerGeneratorHistorical::class, 'hardware_device_id', 'id');
    }

    /**
     *
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function powerLoads()
    {
        return $this->hasMany(HardwarePowerLoad::class, 'hardware_device_id', 'id');
    }

    /**
     *
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function powerLoadsToday()
    {
        return $this->hasMany(HardwarePowerLoadToday::class, 'hardware_device_id', 'id');
    }

    /**
     *
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function powerLoadsHistorical()
    {
        return $this->hasMany(HardwarePowerLoadHistorical::class, 'hardware_device_id', 'id');
    }

    /**
     * Relación con la imagen asociada al curriculum.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function image()
    {
        return $this->hasOne(File::class, 'id', 'image_id');
    }


    public static function createModel(HardwareDevice $device, $request)
    {

    }

    public function updateModel(Request|StoreSolarChargeRequest $request)
    {
        //$dataFromSolar = $request->only(['hardware', 'version',
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

        return (object)[
            'generatorCurrent' => $generatorCurrent,
            'loadCurrent' => $loadCurrent,
            'generatorToday' => $generatorToday,
            'loadToday' => $loadToday,
            'generatorsHistorical' => $generatorsHistorical,
            'loadsHistorical' => $loadsHistorical,
        ];
    }

}
