<?php

namespace App\Models\WeatherStation;

use App\Events\WeatherStationUpdateEvent;
use Illuminate\Notifications\Notifiable;

/**
 * Class Temperature
 *
 * @package App\Models\WeatherStation
 */
class Temperature extends BaseWheaterStation
{
    use Notifiable;

    protected $table = 'meteorology_temperature';


    /**
     * @var string[] Campos que se pueden devolver por api.
     */
    public $apiFields = [
        'value',
    ];

    /**
     * @var string Nombre de la variable.
     */
    public $slug = 'temperature';

    /**
     * Nombre amigable para la representación del modelo.
     *
     * @var string
     */
    public $name = 'Temperatura';

    protected $dispatchesEvents = [
        'created' => WeatherStationUpdateEvent::class,
    ];
}
