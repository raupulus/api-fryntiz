<?php

namespace App\Models\WeatherStation;

/**
 * Class Tvoc
 *
 * @package App\Models\WeatherStation
 */
class Tvoc extends BaseWheaterStation
{
    protected $table = 'meteorology_tvoc';

    /**
     * @var string[] Campos que se pueden devolver por api.
     */
    public $apiFields = [
        'value',
    ];

    /**
     * @var string Nombre de la variable.
     */
    public $slug = 'tvoc';

    /**
     * Nombre amigable para la representación del modelo.
     *
     * @var string
     */
    public $name = 'Calidad del aire (tvoc)';
}
