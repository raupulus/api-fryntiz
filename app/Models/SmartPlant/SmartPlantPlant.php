<?php

namespace App\Models\SmartPlant;

use App\Models\BaseModels\BaseModel;
use App\Traits\BelongsToUser;

/**
 * Class SmartPlantPlant
 *
 * Representa una planta que será asociada desde los registros de lecturas
 * subidos a la API.
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
    public function registers()
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
