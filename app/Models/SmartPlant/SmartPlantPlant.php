<?php

namespace App\Models\SmartPlant;

use Illuminate\Database\Eloquent\Model;

/**
 * Class SmartPlantPlant
 *
 * Representa una planta que será asociada desde los registros de lecturas
 * subidos a la API.
 *
 * @package App\Models\SmartPlant
 */
class SmartPlantPlant extends Model
{
    protected $table = 'smartplant_plants';

    /**
     * Relación con todos los registros de lecturas asociados a una planta.
     * @return mixed
     */
    public function registers()
    {
        return $this->hasMany(SmartPlanRegister::class, 'plant_id', 'id');
    }

    /**
     * Devuelve la imagen de la planta o una por defecto predefinida.
     *
     * @return string
     */
    public function getUrlImageAttribute()
    {
        $image = $this->image ?? 'smartplant/default.jpg';

        return asset('storage/' . $image);
    }

    /**
     * Devuelve los 100 últimos registros.
     *
     * @return mixed
     */
    public function last100registers()
    {
        return SmartPlanRegister::where('plant_id', $this->id)
            ->whereNotNull('soil_humidity')
            ->orderBy('created_at', 'DESC')
            ->limit(100)
            ->get();
    }
}
