<?php

declare(strict_types=1);

namespace App\Models\Hardware;

use App\Models\BaseModels\BaseModel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Tipo de fuente de energía: solar, eólica, autoabastecido, batería o red.
 *
 * Es tabla y no enum a propósito (D107, D80): se filtra por ella desde la API y
 * desde la web —`GET /energy/stats?source=solar`— y con un enum hace falta un
 * despliegue para añadir un tipo nuevo.
 *
 * @property int $id
 * @property string $slug Identificador estable para la API.
 * @property string $name
 * @property string|null $description
 * @property string|null $icon
 * @property int $position
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, HardwareEnergy> $elements
 *
 * @method static Builder<static>|EnergySourceType newModelQuery()
 * @method static Builder<static>|EnergySourceType newQuery()
 * @method static Builder<static>|EnergySourceType query()
 *
 * @mixin \Eloquent
 */
class EnergySourceType extends BaseModel
{
    protected $table = 'energy_source_types';

    protected $fillable = ['slug', 'name', 'description', 'icon', 'position'];

    protected $casts = [
        'position' => 'integer',
    ];

    /**
     * La API y la web referencian el tipo por su slug, nunca por su id.
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * Elementos energéticos de este tipo.
     */
    public function elements(): HasMany
    {
        return $this->hasMany(HardwareEnergy::class, 'energy_source_type_id');
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeSlug(Builder $query, string $slug): Builder
    {
        return $query->where('slug', $slug);
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('position')->orderBy('name');
    }
}
