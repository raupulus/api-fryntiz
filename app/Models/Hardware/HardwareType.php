<?php

declare(strict_types=1);

namespace App\Models\Hardware;

use App\Models\BaseModels\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name Nombre del tipo de hardware (EJ: Portátil).
 * @property string|null $description Descripción del tipo de hardware.
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HardwareType newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HardwareType newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HardwareType query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HardwareType whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HardwareType whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HardwareType whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HardwareType whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HardwareType whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HardwareType whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class HardwareType extends BaseModel
{
    use HasFactory;

    protected $fillable = ['name', 'description'];
}
