<?php

declare(strict_types=1);

namespace App\Models\Hardware;

use App\Models\BaseModels\BaseModel;
use App\Traits\BelongsToHardwareDevice;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * Componente instalado en un dispositivo hardware.
 *
 * @property int $id
 * @property int|null $hardware_available_component_id Componente asociado al hardware
 * @property int|null $hardware_device_id Dispositivo asociado
 * @property string|null $name Nombre del componente
 * @property string|null $brand Marca del componente
 * @property string|null $model Modelo del componente
 * @property string|null $quantity Cantidad de unidades de este componente
 * @property string|null $power Potencia, consumo en watios
 * @property string|null $description Descripción del componente
 * @property string|null $buy_at Fecha de adquisición, el momento de compra o inicio de posesión
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read HardwareAvailableComponent|null $availableComponent
 * @property-read HardwareDevice|null $hardwareDevice
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HardwareComponent newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HardwareComponent newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HardwareComponent onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HardwareComponent query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HardwareComponent whereBrand($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HardwareComponent whereBuyAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HardwareComponent whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HardwareComponent whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HardwareComponent whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HardwareComponent whereHardwareAvailableComponentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HardwareComponent whereHardwareDeviceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HardwareComponent whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HardwareComponent whereModel($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HardwareComponent whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HardwareComponent wherePower($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HardwareComponent whereQuantity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HardwareComponent whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HardwareComponent withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HardwareComponent withoutTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HardwareComponent forDevice(int $deviceId)
 *
 * @mixin \Eloquent
 */
class HardwareComponent extends BaseModel
{
    use BelongsToHardwareDevice;
    use SoftDeletes;

    protected $table = 'hardware_components';

    protected $fillable = [
        'hardware_device_id',
        'hardware_available_component_id',
        'serial_number',
        'notes',
    ];

    public function availableComponent(): BelongsTo
    {
        return $this->belongsTo(HardwareAvailableComponent::class, 'hardware_available_component_id');
    }
}
