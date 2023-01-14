<?php

namespace App\Models\WeatherStation;

use App\Events\WeatherStationUpdateEvent;
use Illuminate\Notifications\Notifiable;

/**
 * Class Humidity
 *
 * @package App\Models\WeatherStation
 */
class Humidity extends BaseWheaterStation
{
    use Notifiable;

    protected $table = 'meteorology_humidity';

    /**
     * @var string[] Campos que se pueden devolver por api.
     */
    public $apiFields = [
        'value',
    ];

    /**
     * @var string Nombre de la variable.
     */
    public $slug = 'humidity';

    /**
     * Nombre amigable para la representación del modelo.
     *
     * @var string
     */
    public $name = 'Humedad';

    protected $dispatchesEvents = [
        'created' => WeatherStationUpdateEvent::class,
    ];
}
