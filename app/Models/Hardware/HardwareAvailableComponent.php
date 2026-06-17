<?php

namespace App\Models\Hardware;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $name Nombre general (Tarjeta gráfica, Procesador...)
 * @property string|null $type Tipo de componente (gpu, cpu, ram..)
 * @property string $slug Slug para el tipo
 * @property string|null $description Descripción del componente
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $deleted_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HardwareAvailableComponent newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HardwareAvailableComponent newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HardwareAvailableComponent query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HardwareAvailableComponent whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HardwareAvailableComponent whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HardwareAvailableComponent whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HardwareAvailableComponent whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HardwareAvailableComponent whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HardwareAvailableComponent whereSlug($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HardwareAvailableComponent whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HardwareAvailableComponent whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class HardwareAvailableComponent extends Model
{
    use HasFactory;
}
