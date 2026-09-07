<?php

declare(strict_types=1);

namespace App\Models\Hardware;

use App\Models\BaseModels\BaseModel;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * La instalación energética (D79).
 *
 * Agrupa elementos que comparten batería y tensión, que es lo que hace posible
 * preguntar «cuánto ha generado la casa hoy» sin ir listando ids a mano. Antes
 * no existía nada equivalente: las lecturas colgaban del dispositivo que mide, y
 * un mismo monitor mide cosas de instalaciones distintas.
 *
 * @property int $id
 * @property int $user_id
 * @property string $name
 * @property string $slug
 * @property bool $is_standalone Nodo autoabastecido: placa pequeña y batería.
 * @property float|null $nominal_voltage
 * @property float|null $battery_capacity_ah
 * @property string|null $notes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read User|null $user
 * @property-read Collection<int, HardwareEnergy> $elements
 *
 * @method static Builder<static>|EnergySystem newModelQuery()
 * @method static Builder<static>|EnergySystem newQuery()
 * @method static Builder<static>|EnergySystem query()
 *
 * @mixin \Eloquent
 */
class EnergySystem extends BaseModel
{
    use SoftDeletes;

    protected $table = 'energy_systems';

    protected $fillable = [
        'user_id', 'name', 'slug', 'is_standalone',
        'nominal_voltage', 'battery_capacity_ah', 'notes',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'is_standalone' => 'boolean',
        'nominal_voltage' => 'float',
        'battery_capacity_ah' => 'float',
    ];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Elementos —paneles, routers, baterías— que forman la instalación.
     */
    public function elements(): HasMany
    {
        return $this->hasMany(HardwareEnergy::class, 'energy_system_id');
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeStandalone(Builder $query, bool $standalone = true): Builder
    {
        return $query->where('is_standalone', $standalone);
    }
}
