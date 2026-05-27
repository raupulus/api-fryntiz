<?php

namespace App\Models\Hardware;

use App\Models\BaseModels\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Componente instalado en un dispositivo hardware.
 *
 * @property int $hardware_device_id
 * @property int $hardware_available_component_id
 * @property string|null $serial_number
 * @property string|null $notes
 */
class HardwareComponent extends BaseModel
{
    use SoftDeletes;

    protected $table = 'hardware_components';

    protected $fillable = [
        'hardware_device_id',
        'hardware_available_component_id',
        'serial_number',
        'notes',
    ];

    public function device(): BelongsTo
    {
        return $this->belongsTo(HardwareDevice::class, 'hardware_device_id');
    }

    public function availableComponent(): BelongsTo
    {
        return $this->belongsTo(HardwareAvailableComponent::class, 'hardware_available_component_id');
    }
}
