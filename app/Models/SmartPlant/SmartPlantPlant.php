<?php

namespace App\Models\SmartPlant;

use Illuminate\Database\Eloquent\Relations\HasMany;

use App\Models\BaseModels\BaseModel;
use App\Traits\BelongsToUser;

/**
 * Class SmartPlantPlant
 *
 * Representa una planta que será asociada desde los registros de lecturas
 * subidos a la API.
 *
 * @property int $id
 * @property int|null $user_id Usuario asociado
 * @property string $name Nombre común de la planta
 * @property string $name_scientific Nombre científico de la planta
 * @property string $description Descripción general de la planta
 * @property string $details Descripción avanzada con detalles de la planta
 * @property string|null $image Imagen que representa a la planta
 * @property string $start_at Momento en el que se ha sembrado
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $deleted_at
 * @property-read string $url_image
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\SmartPlant\SmartPlantRegister> $registers
 * @property-read int|null $registers_count
 * @property-read \App\Models\User|null $user
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
 * @mixin \Eloquent
 */
class SmartPlantPlant extends BaseModel
{
    use BelongsToUser;

    protected $table = 'smartplant_plants';

    protected $fillable = [
        'user_id',
        'hardware_device_id',
        'name',
        'name_scientific',
        'description',
        'details',
        'image',
        'start_at',
    ];

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
     * Devuelve la imagen de la planta o una por defecto predefinida.
     *
     * @return string
     */
    public function getUrlImageAttribute()
    {
        $image = $this->image ?? 'smartplant/default.jpg';

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
            ->orderBy('created_at', 'DESC')
            ->limit(100)
            ->get();
    }
}
