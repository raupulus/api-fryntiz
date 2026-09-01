<?php

declare(strict_types=1);

namespace App\Models\Hardware;

use App\Models\BaseModels\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name Nombre general (Tarjeta gráfica, Procesador...)
 * @property string|null $type Tipo de componente (gpu, cpu, ram..)
 * @property string $slug Slug para el tipo
 * @property string|null $description Descripción del componente
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 *
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
 *
 * @mixin \Eloquent
 */
class HardwareAvailableComponent extends BaseModel
{
    /**
     * @var list<string> Campos que admiten asignación masiva.
     *
     * Sin esta lista el modelo NO admitía asignación masiva en absoluto:
     * sin `$fillable` ni `$guarded` propios, Eloquent aplica su
     * `$guarded = ['*']` por defecto y descarta —o rechaza con
     * MassAssignmentException— cualquier `create()` o `fill()`.
     */
    protected $fillable = [
        'name',
        'type',
        'slug',
        'description',
    ];

    use HasFactory;
}
