<?php

namespace App\Models\WeatherStation;

use App\Events\WeatherStationUpdateEvent;

/**
 * Class WindDirection
 *
 * @package App\Models\WeatherStation
 */
class WindDirection extends BaseWheaterStation
{
    protected $fillable = [
        'resistance',
        'direction',
        'grades',
        'created_at'
    ];

    protected $table = 'meteorology_wind_direction';

    /**
     * @var string[] Campos que se pueden devolver por api.
     */
    public $apiFields = [
        'grades',
    ];

    /**
     * @var string Nombre de la variable.
     */
    public $slug = 'wind_direction';

    /**
     * Nombre amigable para la representación del modelo.
     *
     * @var string
     */
    public $name = 'Dirección del viento';


    // Esto se realiza dentro del modelo para la velocidad del viento.
    // Por ahora no se contempla tener un evento para la dirección del viento.
    /*
    protected $dispatchesEvents = [
        'created' => WeatherStationUpdateEvent::class,
    ];
    */

    /**
     * Devuelve todos los elementos del modelo.
     */
    public static function all($columns = ['*'])
    {
        $query = parent::all();
        $query::whereNotNull('direction')
            ->orderBy('created_at', 'DESC')
            ->get();
        return $query;
    }

    public static function getResistance($grades)
    {
        $maxResistance = 65535; // 16bits
        return $maxResistance * ($grades / 360);
    }

    public static function getDirection($grades)
    {
        if ($grades >= 0 && $grades < 22.5) {
            return 'N';
        } elseif ($grades >= 22.5 && $grades < 67.5) {
            return 'NE';
        } elseif ($grades >= 67.5 && $grades < 112.5) {
            return 'E';
        } elseif ($grades >= 112.5 && $grades < 157.5) {
            return 'SE';
        } elseif ($grades >= 157.5 && $grades < 202.5) {
            return 'S';
        } elseif ($grades >= 202.5 && $grades < 247.5) {
            return 'SW';
        } elseif ($grades >= 247.5 && $grades < 292.5) {
            return 'W';
        } elseif ($grades >= 292.5 && $grades < 337.5) {
            return 'NW';
        } elseif ($grades >= 337.5 && $grades <= 360) {
            return 'N';
        } else {
           return 'N/A';
        }
    }
}
