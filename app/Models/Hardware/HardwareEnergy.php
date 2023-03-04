<?php

namespace App\Models\Hardware;

use App;
use Illuminate\Database\Eloquent\Model;


/**
 * Class Platform
 */
class HardwareEnergy extends Model
{
    protected $table = 'hardware_energy';

    protected $fillable = ['hardware_device_id', 'hardware_device_monitorized_id', 'is_generator', 'sensor_position'];

    /**
     * Relación con el dispositivo que monitoriza la energía.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
   public function hardware()
   {
       return $this->belongsTo(HardwareDevice::class, 'hardware_device_id', 'id');
   }

    /**
     * Dispositivo monitorizado.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
   public function monitorized()
   {
       return $this->belongsTo(HardwareDevice::class, 'hardware_device_monitorized_id', 'id');
   }

}
