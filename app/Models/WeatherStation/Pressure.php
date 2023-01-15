<?php

namespace App\Models\WeatherStation;

use App\Events\WeatherStationUpdateEvent;

/**
 * Class Pressure
 *
 * @package App\Models\WeatherStation
 */
class Pressure extends BaseWheaterStation
{
    protected $table = 'meteorology_pressure';

    /**
     * @var string[] Campos que se pueden devolver por api.
     */
    public $apiFields = [
        'value',
    ];

    /**
     * @var string Nombre de la variable.
     */
    public $slug = 'pressure';

    /**
     * Nombre amigable para la representación del modelo.
     *
     * @var string
     */
    public $name = 'Presión';

    protected $dispatchesEvents = [
        'created' => WeatherStationUpdateEvent::class,
    ];
}
