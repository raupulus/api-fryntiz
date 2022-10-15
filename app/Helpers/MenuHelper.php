<?php

/**
 * Class MenuHelper
 *
 * Helper para ayudar a generar menús o devolver menús usados en distintos
 * lugares de la aplicación centralizando su mantenimiento.
 *
 */
class MenuHelper
{
    /**
     * Devuelve las rutas para acceder al listado principal de cada api.
     *
     * @return array[]
     */
    public static function getApiRoutesIndex()
    {
        $path = request()->path();

        return [
            'weatherstation' => [
                'route' => route('weather_station.index'),
                'name' => 'Weather Station',
                'selected' => $path == 'weatherstation',
            ],
            'keycounter' => [
                'route' => route('keycounter.index'),
                'name' => 'Keycounter',
                'selected' => $path == 'keycounter',
            ],
            'airflight' => [
                'route' => route('airflight.index'),
                'name' => 'Airflight',
                'selected' => $path == 'airflight',
            ],
            'smartplant' => [
                'route' => route('smartplant.index'),
                'name' => 'Smart Plant',
                'selected' => $path == 'smartplant',
            ],
            'energy' => [
                'route' => route('hardware.energy.index'),
                'name' => 'Energy',
                'selected' => $path == 'hardware/energy',
            ],
        ];
    }

    /**
     * Devuelve un array con las rutas hacia la documentación de las Apis.
     *
     * @return array
     */
    public static function getApiRoutesDocs()
    {
        return [];
    }
}
