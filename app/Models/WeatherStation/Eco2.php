<?php

namespace App\Models\WeatherStation;

/**
 * Class Eco2
 *
 * @package App\Models\WeatherStation
 */
class Eco2 extends BaseWheaterStation
{
    protected $table = 'meteorology_eco2';

    /**
     * @var string[] Campos que se pueden devolver por api.
     */
    public $apiFields = [
        'value',
    ];

    /**
     * @var string Nombre de la variable.
     */
    public $slug = 'eco2';

    /**
     * Nombre amigable para la representación del modelo.
     *
     * @var string
     */
    public $name = 'Calidad del aire (eCO2)';
}
