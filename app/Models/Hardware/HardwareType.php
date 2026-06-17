<?php

namespace App\Models\Hardware;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $name Nombre del tipo de hardware (EJ: Portátil).
 * @property string|null $description Descripción del tipo de hardware.
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $deleted_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HardwareType newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HardwareType newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HardwareType query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HardwareType whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HardwareType whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HardwareType whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HardwareType whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HardwareType whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HardwareType whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class HardwareType extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'description'];
}
