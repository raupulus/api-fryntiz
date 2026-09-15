<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Configuración de GDACS (Global Disaster Alert and Coordination System)
|--------------------------------------------------------------------------
|
| Sin API key: es una API pública y gratuita (ver docs/future/archived/gdacs-api.md).
| Lo único que pide es citar la fuente, de ahí `attribution`.
|
*/

return [

    'base_url' => env('GDACS_BASE_URL', 'https://www.gdacs.org/gdacsapi/api/events/geteventlist/SEARCH'),

    'timeout_seconds' => 15,

    /*
     * Comprobado en directo (2026-09-15): sin este parámetro, la API excluye
     * los eventos en verde por defecto — no está documentado en ningún PDF
     * oficial de GDACS. Verde es el nivel que predomina en una zona de
     * actividad sísmica/incendios baja-media, así que omitirlo se comía casi
     * todo lo relevante cerca de la instalación. Se manda siempre fijo, no es
     * configurable a propósito: no hay ningún caso de uso de este proyecto que
     * quiera perderse el nivel verde.
     */
    'alert_levels' => ['green', 'orange', 'red'],

    /*
     * Tipos de desastre a sondear. Los seis que cubre GDACS — ver
     * App\Enums\GdacsEventTypeEnum.
     */
    'event_types' => ['EQ', 'TC', 'FL', 'VO', 'DR', 'WF'],

    /*
     * Filtro por país de la propia API GDACS (parámetro `country`, sólo
     * admite el nombre en inglés, no ISO-3 — comprobado en directo).
     *
     * Se filtra aquí, en el servidor de GDACS, y no sólo por distancia en
     * cliente, porque sin ningún filtro geográfico un sondeo global de los
     * últimos días ya toca el límite de 100 registros por página (comprobado:
     * 100 eventos en sólo 7 días con los seis tipos y los tres niveles). Con
     * `country=Spain` son ~28 eventos en lo que va de año: cabe siempre en una
     * página, sin paginar.
     *
     * Compromiso consciente: un suceso genuino a menos de 150 km pero cuyo
     * centroide cae en Portugal o Marruecos no se vería. Si eso llega a
     * importar, la solución es sondear también ese país (una llamada más; la
     * API no admite varios países en una sola consulta con `;`, se comprobó).
     */
    'country' => env('GDACS_COUNTRY', 'Spain'),

    /*
     * Días hacia atrás que se piden en cada sondeo. GDACS actualiza el mismo
     * event_id in-place mientras el suceso sigue vivo, así que no hace falta
     * una ventana larga: 60 días de sobra cubre cualquier cosa que siga
     * "activa" o recién empezada sin arrastrar histórico innecesario.
     */
    'lookback_days' => (int) env('GDACS_LOOKBACK_DAYS', 60),

    /*
     * Punto de referencia y radio de interés. Por defecto, Chipiona (Cádiz).
     * Sólo se guardan en `gdacs_events` los sucesos dentro de este radio — no
     * hay filtro de radio en la API de GDACS (comprobado: `bbox`/`boundingBox`
     * los ignora en silencio), así que la distancia se calcula en cliente
     * (haversine) contra cada suceso que devuelve el filtro por país de
     * arriba.
     */
    'reference' => [
        'lat' => (float) env('GDACS_REFERENCE_LAT', 36.7371),
        'lon' => (float) env('GDACS_REFERENCE_LON', -6.4348),
        'radius_km' => (float) env('GDACS_RADIUS_KM', 150),
    ],

    /*
     * GDACS sólo pide esto: citar la fuente. No hay licencia restrictiva más
     * allá de un descargo de responsabilidad (los datos son automáticos,
     * "as is", no sustituyen alertas oficiales locales) — ver
     * docs/future/archived/gdacs-api.md.
     */
    'attribution' => 'Global Disaster Awareness and Coordination System, GDACS (gdacs.org)',
];
