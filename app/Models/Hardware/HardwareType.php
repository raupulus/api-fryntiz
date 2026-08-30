<?php

declare(strict_types=1);

namespace App\Models\Hardware;

use App\Models\BaseModels\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $name Nombre del tipo de hardware (EJ: Portátil).
 * @property string|null $slug Identificador estable para la API (EJ: pc-portatil).
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

    /**
     * Nombre del tipo de hardware que identifica a las estaciones meteorológicas.
     * Coincide con el registro sembrado por HardwareTypeSeeder.
     */
    public const WEATHER_STATION = 'Estación Meteorológica';

    protected $fillable = ['name', 'slug', 'description'];

    /**
     * El `slug` sale solo del nombre si no se da.
     *
     * Es lo que permite seguir dando de alta un tipo con sólo su nombre —los
     * seeders y los tests lo hacen— sin dejar la columna a `null` y romper el
     * filtro `?type=` de la API.
     */
    protected static function booted(): void
    {
        static::saving(static function (self $tipo): void {
            if (blank($tipo->slug) && filled($tipo->name)) {
                $tipo->slug = Str::slug($tipo->name);
            }
        });
    }
}
