<?php

namespace App\Models\WeatherStation;

use App\Events\WeatherStationUpdateEvent;

/**
 * Class Lightning
 *
 * @package App\Models\WeatherStation
 */
class Lightning extends BaseWheaterStation
{
    protected $table = 'meteorology_lightning';

    protected $fillable = [
        'distance',
        'energy',
        'noise_floor',
        'created_at'
    ];

    /**
     * @var string[] Campos que se pueden devolver por api.
     */
    public $apiFields = [
        'distance',
        'energy',
    ];

    /**
     * @var string Nombre de la variable.
     */
    public $slug = 'lightning';

    /**
     * Nombre amigable para la representación del modelo.
     *
     * @var string
     */
    public $name = 'Rayos y Relámpagos';

    protected $dispatchesEvents = [
        'created' => WeatherStationUpdateEvent::class,
    ];

    /**
     * Devuelve un array con todos los títulos de una tabla.
     *
     * @return array
     */
    public static function getTableHeads()
    {
        return [
            'distance' => 'Distancia (Km)',
            'energy' => 'Energía',
            'noise_floor' => 'Reducción de Ruido',
            'created_at' => 'Instante'
        ];
    }
}
