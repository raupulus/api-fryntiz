<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Configuración de Open-Meteo Marine (mareas)
|--------------------------------------------------------------------------
|
| Sin API key: es un servicio público y gratuito. AEMET no publica altura de
| marea (sus dos productos marítimos son boletines de texto, ver
| docs/apis/aemet/07-maritima.md); la fuente oficial española sería Puertos
| del Estado, pero no tiene una API pública sencilla de integrar. Open-Meteo
| Marine sirve `sea_level_height_msl` horario por coordenadas, sin registro.
|
*/

return [

    'base_url' => env('OPEN_METEO_MARINE_BASE_URL', 'https://marine-api.open-meteo.com/v1/marine'),

    'timeout_seconds' => 15,

    /*
     * Punto de referencia: Chipiona. Fijo a propósito, no por fila: no hay
     * ningún caso de uso de este proyecto que pida marea de otro sitio.
     */
    'reference' => [
        'lat' => (float) env('OPEN_METEO_MARINE_LAT', 36.7371),
        'lon' => (float) env('OPEN_METEO_MARINE_LON', -6.4348),
    ],

    /*
     * 2 días de sobra para siempre tener la próxima pleamar/bajamar
     * calculada, sin pedir más ventana de la que se necesita.
     */
    'forecast_days' => (int) env('OPEN_METEO_MARINE_FORECAST_DAYS', 2),

    'timezone' => env('OPEN_METEO_MARINE_TIMEZONE', 'Europe/Madrid'),

    /*
     * Una franja de marea entre picos dura ~6 h (semidiurna, dos pleamares y
     * dos bajamares al día). Un hueco menor entre dos extremos del mismo tipo
     * es ruido de la interpolación sobre puntos horarios casi iguales, no una
     * marea real — se funden quedándose con el más extremo. Ver
     * TideExtremesCalculator::merge().
     */
    'min_hours_between_same_type' => 3,
];
