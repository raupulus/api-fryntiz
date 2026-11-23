<?php

declare(strict_types=1);

namespace App\Models\SmartPlant;

use App\Models\BaseModels\BaseModel;
use App\Models\User;
use App\Traits\BelongsToUser;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Class SmartPlantPlant
 *
 * Representa una planta que será asociada desde los registros de lecturas
 * subidos a la API.
 *
 * @property int $id
 * @property int|null $user_id Usuario asociado
 * @property string $name Nombre común de la planta
 * @property string $slug Slug único legible para URLs públicas
 * @property string $name_scientific Nombre científico de la planta
 * @property string $description Descripción general de la planta
 * @property string $details Descripción avanzada con detalles de la planta
 * @property string|null $image Imagen que representa a la planta
 * @property Carbon $start_at Momento en el que se ha sembrado
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 * @property-read string $url_image
 * @property-read Collection<int, SmartPlantRegister> $registers
 * @property-read int|null $registers_count
 * @property-read User|null $user
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SmartPlantPlant forUser(int $userId)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SmartPlantPlant newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SmartPlantPlant newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SmartPlantPlant query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SmartPlantPlant whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SmartPlantPlant whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SmartPlantPlant whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SmartPlantPlant whereDetails($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SmartPlantPlant whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SmartPlantPlant whereImage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SmartPlantPlant whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SmartPlantPlant whereNameScientific($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SmartPlantPlant whereStartAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SmartPlantPlant whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SmartPlantPlant whereUserId($value)
 *
 * @mixin \Eloquent
 */
class SmartPlantPlant extends BaseModel
{
    use BelongsToUser;
    use SoftDeletes;

    protected $table = 'smartplant_plants';

    protected $casts = [
        'start_at' => 'datetime',
    ];

    protected $fillable = [
        'user_id',
        'hardware_device_id',
        'name',
        'slug',
        'name_scientific',
        'description',
        'details',
        'image',
        'start_at',
    ];

    /**
     * Genera automáticamente un slug único si no se proporciona explícitamente
     * al crear o actualizar una planta.
     */
    protected static function booted(): void
    {
        static::saving(function (self $plant): void {
            $currentSlug = trim((string) $plant->slug);

            if ($currentSlug === '') {
                $plant->slug = self::generateUniqueSlug($plant->name, $plant->exists ? (int) $plant->id : null);
            } else {
                $plant->slug = Str::limit(Str::slug($currentSlug) ?: 'planta', 160, '');
            }
        });
    }

    /**
     * Usa `slug` como identificador de ruta en la web pública.
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * Construye un slug URL-safe único dentro de `smartplant_plants`.
     */
    public static function generateUniqueSlug(?string $name, ?int $ignoreId = null): string
    {
        $base = Str::limit(Str::slug((string) $name), 140, '');

        if ($base === '') {
            $base = 'planta';
        }

        $candidate = $base;
        $suffix = 2;

        while (static::query()
            ->when($ignoreId !== null, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->where('slug', $candidate)
            ->exists()) {
            $candidate = Str::limit($base, 140, '').'-'.$suffix;
            $suffix++;
        }

        return $candidate;
    }

    /**
     * Relación con todos los registros de lecturas asociados a una planta.
     *
     * @return mixed
     */
    public function registers(): HasMany
    {
        return $this->hasMany(SmartPlantRegister::class, 'plant_id', 'id');
    }

    /**
     * Último registro recibido para esta planta (evita N+1 en tablas Filament).
     *
     * @return HasOne<SmartPlantRegister, $this>
     */
    public function latestRegister(): HasOne
    {
        return $this->hasOne(SmartPlantRegister::class, 'plant_id', 'id')->latestOfMany('created_at');
    }

    /**
     * Devuelve la imagen de la planta o una por defecto predefinida.
     *
     * @return string
     */
    public function getUrlImageAttribute()
    {
        $image = is_string($this->image) ? trim($this->image) : '';

        if ($image === '' || $image === 'smartplant/default.jpg') {
            $image = 'smartplant/default.webp';
        }

        return asset('storage/'.$image);
    }

    /**
     * Devuelve los 100 últimos registros.
     *
     * @return mixed
     */
    public function last100registers()
    {
        return SmartPlantRegister::where('plant_id', $this->id)
            ->whereNotNull('soil_humidity')
            ->orderBy('created_at', 'desc')
            ->limit(100)
            ->get();
    }
}
