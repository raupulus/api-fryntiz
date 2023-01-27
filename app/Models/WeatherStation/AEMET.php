<?php

namespace App\Models\WeatherStation;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AEMET extends Model
{
    use HasFactory;

    private $URL = 'https://opendata.aemet.es/opendata/api';

    private $API_KEY;

    private $CITY = 'id11016'; // Chipiona

    private $PATHS = [
        'predictionDaily' => 'prediccion/especifica/municipio/diaria/11016',
        'predictionHourly' => 'prediccion/especifica/municipio/horaria/11016',
        'playaReglaChipiona' => 'prediccion/especifica/playa/1101604',
        'playaCruzDelMarChipiona' => 'prediccion/especifica/playa/1101602',
        'allCityInfo' => 'maestro/municipios',
        'predictionUvi' => 'prediccion/especifica/uvi/0',
        'conventionalObservationData' => 'observacion/convencional/datos/estacion/5906X',
        'periodClimatologiaPasada' => 'valores/climatologicos/diarios/datos/fechaini/{start}/fechafin/{end}/estacion/5910',
        'imageVegetation' => 'satelites/producto/nvdi',
        'imageMarTemperature' => 'satelites/producto/sst',
        'altamarPrediction' => 'prediccion/maritima/altamar/area/1',
        'costaPrediction' => 'prediccion/maritima/costera/costa/42',
        'contamination' => 'red/especial/contaminacionfondo/estacion/17',
        'ozono' => 'red/especial/perfilozono/estacion/peninsula',
        'sunradiation' => 'red/especial/radiacion',
        'avisos_cap' => 'avisos_cap/ultimoelaborado/area/61', // Andalucia
    ];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->API_KEY = config('aemet.AEMET_API_KEY');
    }

    /**
     * Devuelve los datos de un endpoint realizando una petición con curl.
     * Los datos devueltos pueden devolverse en json o en raw según hayan sido
     * recibidos.
     *
     * @param string $url  Recibe la url con el endpoint completo de la API.
     * @param bool   $json Recibe si la respuesta será en JSON o RAW.
     *
     * @return bool|string|null
     */
    public function getCurl(string $url, bool $json = true)
    {
        $curl = curl_init();

        curl_setopt_array($curl, [

            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            //CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_ENCODING => "",
            CURLOPT_HTTPHEADER => [
                'cache-control: no-cache',
                'Accept: application/json',
                'api_key: ' . $this->API_KEY
            ],
        ]);

        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);

        if ($err) {
            \Log::error('AMET model, method getCurl()');
            \Log::error($err);

            return null;
        }

        if ($json) {
            return json_decode($response, true, 512,
                JSON_INVALID_UTF8_SUBSTITUTE
            );
        }

        return $response;
    }

    /**
     * Devuelve la url completa a partir de la clave para el endpoint/path
     * recibido como parámetro.
     *
     * @param string $path Clave del path a utilizar.
     *
     * @return string
     */
    public function getUrl(string $path)
    {
        return $this->URL . '/' . $this->PATHS[ $path ];
    }


    /**
     * Busca la información de una ciudad en concreto.
     *
     * @param string $name Nombre de la ciudad a buscar.
     *
     * @return mixed|void
     */
    public function getCityInfoByName(string $name = 'Chipiona')
    {
        $url = $this->getUrl('allCityInfo');
        $response = $this->getCurl($url);

        foreach ($response as $key => $value) {
            if (isset($value['nombre']) && $value['nombre'] == $name) {
                return $value;
            }
        }
    }


    /**
     * Devuelve la precicción por horas para la ciudad de Chipiona.
     *
     * @return bool|string|null
     */
    public function getPredictionHourly()
    {
        $url = $this->getUrl('predictionHourly');
        $curl = $this->getCurl($url);


        return $curl;
    }

    /**
     * Devuelve la predicción diaria para la ciudad de Chipiona.
     *
     * @return array
     */
    public function getPredictionDaily()
    {
        $url = $this->getUrl('predictionDaily');
        $curl = $this->getCurl($url);

        if ($curl && $curl['datos']) {
            $curl2 = $this->getCurl($curl['datos']);


            if (!$curl2 || !count($curl2) || !isset($curl2[0]['prediccion']) || !$curl2[0]['prediccion']) {

                return null;
            }

        } else {
            return null;
        }


        // Aquí hay $curl2 con datos. $curl2 es un array

        $finalArray = [];

        function extractRange(Carbon $readAt, string $stringRange): array|null
        {
            $stringToArray = explode('-', $stringRange);

            if (!count($stringToArray) === 2) {
                return null;
            }

            $readAt->setMinute(0)->setSecond(0)->setMicrosecond(0);

            $start = clone( $readAt );
            $end = clone( $readAt );

            $start->setHour($stringToArray[0]);
            $end->setHour($stringToArray[1]);

            return [
                'period_start_at' => $start,
                'period_end_at' => $end
            ];
        }

        ## Recorro toda las páginas que han sido devueltas.
        foreach ($curl2 as $page) {
            $predictions = $page['prediccion'];

            ## Recorro todas las predicciones para la página actual.
            foreach ($predictions as $days) {
                $predictionClean = [];

                ## Recorro todos los días con las predicciones.
                foreach ($days as $day) {

                    $readAtRaw = isset($day['fecha']) ? $day['fecha'] : null;

                    if (!$readAtRaw) {
                        continue;
                    }

                    $readAt = Carbon::parse($readAtRaw);

                    $rainfall = $day['probPrecipitacion'];
                    $snowerCover = $day['cotaNieveProv'];
                    $skyStatus = $day['estadoCielo'];
                    $wind = $day['viento'];
                    $windMax = $day['rachaMax'];
                    $temperature = $day['temperatura'];
                    $thermalSensation = $day['sensTermica'];
                    $humidity = $day['humedadRelativa'];
                    $uvMax = isset($day['uvMax']) ? $day['uvMax'] : null;

                    $tmpArray = [];

                    ## Precipitaciones por rango
                    foreach ($rainfall as $registerRange) {

                        if (!isset($registerRange['periodo']) || !$registerRange['periodo']) {
                            continue;
                        }

                        $prepareData = extractRange($readAt, $registerRange['periodo']);

                        if (!$prepareData) {
                            continue;
                        }

                        $tmpArray['rainfall'][] = array_merge([
                            'prob_precipitation' => $registerRange['value'] ? $registerRange['value'] : null,
                        ], $prepareData);
                    }


                    ## Cota de nieve por rango (m)
                    foreach ($snowerCover as $registerRange) {
                        if (!isset($registerRange['periodo']) || !$registerRange['periodo']) {
                            continue;
                        }

                        $prepareData = extractRange($readAt, $registerRange['periodo']);

                        if (!$prepareData) {
                            continue;
                        }

                        $tmpArray['snower_cover'][] = array_merge([
                            'snower_cover' => $registerRange['value'] ? $registerRange['value'] : null,
                        ], $prepareData);
                    }

                    ## Estado del cielo
                    foreach ($skyStatus as $registerRange) {
                        if (!isset($registerRange['periodo']) || !$registerRange['periodo']) {
                            continue;
                        }

                        $prepareData = extractRange($readAt, $registerRange['periodo']);

                        if (!$prepareData) {
                            continue;
                        }

                        $tmpArray['sky_status'][] = array_merge([
                            'sky_status' => $registerRange['value'] ? $registerRange['value'] : null,
                            'sky_status_description' => $registerRange['descripcion'] ? $registerRange['descripcion'] : null,
                        ], $prepareData);
                    }

                    ## Dirección y velocidad del viento
                    foreach ($wind as $registerRange) {
                        if (!isset($registerRange['periodo']) || !$registerRange['periodo']) {
                            continue;
                        }

                        $prepareData = extractRange($readAt, $registerRange['periodo']);

                        if (!$prepareData) {
                            continue;
                        }

                        $tmpArray['sky_status'][] = array_merge([
                            'wind_direction' => $registerRange['direccion'] ? $registerRange['direccion'] : null, // Dirección del viento (N/Norte, NE/Nordeste, E/Este, SE/Sudeste, S/Sur, SO/Suroeste, O / Oeste, NO / Noroeste, C / Calma
                            'wind_speed' => $registerRange['velocidad'] ? $registerRange['velocidad'] : null, // Kilómetros por hora (km/h)
                        ], $prepareData);
                    }

                    ## Racha máxima de viento
                    foreach ($windMax as $registerRange) {
                        if (!isset($registerRange['periodo']) || !$registerRange['periodo']) {
                            continue;
                        }

                        $prepareData = extractRange($readAt, $registerRange['periodo']);

                        if (!$prepareData) {
                            continue;
                        }

                        $tmpArray['wind_max'][] = array_merge([
                            'wind_max' => $registerRange['value'] ? $registerRange['value'] : null, // Kilómetros por hora (km/h)
                        ], $prepareData);
                    }


                    ## Temperatura
                    $tmpArray['temperature_day']['temperature_max'] = $temperature['maxima'];
                    $tmpArray['temperature_day']['temperature_min'] = $temperature['minima'];


                    if ($temperature['maxima'] && $temperature['minima']) {
                        $tmpArray['temperature_day'] = array_merge(
                            $tmpArray['temperature_day'],
                            extractRange($readAt, '00-24')
                        );
                    }

                    foreach ($temperature['dato'] as $registerRange) {
                        $range = $registerRange['hora'] - 6 . '-' . $registerRange['hora'];

                        $prepareData = extractRange($readAt, $range);

                        if (!$prepareData) {
                            continue;
                        }

                        $tmpArray['temperature'][] = array_merge([
                            'temperature' => $registerRange['value'] ? $registerRange['value'] : null,
                        ], $prepareData);
                    }

                    dd($tmpArray);

                    ## Sensación térmica
                    $tmpArray['thermal_sensation_day']['thermal_sensation_max'] = $thermalSensation['maxima'];
                    $tmpArray['thermal_sensation_day']['thermal_sensation_min'] = $thermalSensation['minima'];


                    if ($thermalSensation['maxima'] && $thermalSensation['minima']) {
                        $tmpArray['thermal_sensation_day'] = array_merge(
                            $tmpArray['thermal_sensation_day'],
                            extractRange($readAt, '00-24')
                        );
                    }

                    foreach ($thermalSensation['dato'] as $registerRange) {
                        $range = $registerRange['hora'] - 6 . '-' . $registerRange['hora'];

                        $prepareData = extractRange($readAt, $range);

                        if (!$prepareData) {
                            continue;
                        }

                        $tmpArray['thermal_sensation'][] = array_merge([
                            'thermal_sensation' => $registerRange['value'] ? $registerRange['value'] : null,
                        ], $prepareData);
                    }


                    ## Humedad Relativa
                    $tmpArray['humidity_day']['humidity_max'] = $humidity['maxima'];
                    $tmpArray['humidity_day']['humidity_min'] = $humidity['minima'];

                    if ($humidity['maxima'] && $humidity['minima']) {
                        $tmpArray['humidity_day'] = array_merge(
                            $tmpArray['humidity_day'],
                            extractRange($readAt, '00-24')
                        );
                    }

                    foreach ($humidity['dato'] as $registerRange) {
                        $range = $registerRange['hora'] - 6 . '-' . $registerRange['hora'];

                        $prepareData = extractRange($readAt, $range);

                        if (!$prepareData) {
                            continue;
                        }

                        $tmpArray['humidity'][] = array_merge([
                            'humidity' => $registerRange['value'] ? $registerRange['value'] : null,
                        ], $prepareData);
                    }

                    if ($uvMax) {
                        $tmpArray['uv']['uv_max'] = $uvMax;

                        $tmpArray['uv'] = array_merge(
                            $tmpArray['uv'],
                            extractRange($readAt, '00-24')
                        );
                    }

                    $predictionClean[ $readAt->format('Y-m-d') ] = $tmpArray;
                }


                if ($predictionClean && count($predictionClean)) {
                    $finalArray[] = $predictionClean;
                }
            }


        }

        return $finalArray;
    }

    /**
     * Devuelve los datos emitidos por la estación convencional de Chipiona.
     * Son datos enviados en las últimas 24 horas.
     * La respuesta contiene este formato:
     *
     * [ {
     * "idema" : "5906X",
     * "lon" : -6.400558,
     * "fint" : "2023-01-16T20:00:00",
     * "prec" : 0.0,
     * "alt" : 10.0,
     * "vmax" : 10.4,
     * "vv" : 6.8,
     * "dv" : 259.0,
     * "lat" : 36.75,
     * "dmax" : 257.0,
     * "ubi" : "CHIPIONA  ECA",
     * "hr" : 83.0,
     * "tamin" : 15.2,
     * "ta" : 15.3,
     * "tamax" : 15.3
     * }, ... ]
     *
     * @return void
     */
    public function getConventionalObservationData()
    {
        $url = $this->getUrl('conventionalObservationData');

        return $this->getCurl($url);
    }

    /**
     * Valor climatológico medio en el periodo de fechas recibidos como
     * parámetros. Se recibe un objeto por cada día, no llega al día actual.
     * Este valor se calcula a partir de los datos de la estación convencional
     * de Rota.
     * [
     * {
     * "fecha" : "2023-01-11",
     * "indicativo" : "5910",
     * "nombre" : "ROTA, BASE NAVAL",
     * "provincia" : "CADIZ",
     * "altitud" : "21",
     * "tmed" : "13,6",
     * "prec" : "0,0",
     * "tmin" : "9,5",
     * "horatmin" : "03:15",
     * "tmax" : "17,7",
     * "horatmax" : "13:50",
     * "dir" : "36",
     * "velmedia" : "2,5",
     * "racha" : "6,7",
     * "horaracha" : "13:30",
     * "sol" : "5,9",
     * "presMax" : "1027,5",
     * "horaPresMax" : "11",
     * "presMin" : "1024,3",
     * "horaPresMin" : "15"
     * },
     * ]
     *
     * @param \Carbon\Carbon $startCarbon Fecha de inicio del periodo.
     * @param \Carbon\Carbon $endCarbon   Fecha de fin del periodo.
     *
     * @return bool|mixed|string|null
     */
    public function getPeriodClimatologiaPasada(Carbon $startCarbon,
                                                Carbon $endCarbon)
    {
        $url = $this->getUrl('periodClimatologiaPasada');

        $start = $startCarbon->format('Y-m-d\TH:i:s') . 'UTC';
        $end = $endCarbon->format('Y-m-d\TH:i:s') . 'UTC';

        $url = str_replace('{start}', $start, $url);
        $url = str_replace('{end}', $end, $url);

        $curl = $this->getCurl($url);

        if ($curl && isset($curl['datos']) && $curl['datos']) {
            $url2 = $curl['datos'];

            return $this->getCurl($url2);
        }

        return null;
    }

    /**
     * Devolvemos la url hacia un mapa de España con la cantidad de vegetación.
     *
     * @return string|null
     */
    public function getImageVegetation()
    {
        $url = $this->getUrl('imageVegetation');
        $curl = $this->getCurl($url);

        if ($curl && isset($curl['datos']) && $curl['datos']) {
            return $curl['datos'];
        }

        return null;
    }

    /**
     * Devuelve la url hacia un mapa de España con la temperatura del mar.
     *
     * @return string|null
     */
    public function getImageMarTemperature()
    {
        $url = $this->getUrl('imageMarTemperature');
        $curl = $this->getCurl($url);

        if ($curl && isset($curl['datos']) && $curl['datos']) {
            return $curl['datos'];
        }

        return null;
    }




    /**
     * Devuelve los datos registrados para la capa de ozono.
     *
     * De la API vuelve un archivo que proceso para devolver un array con los
     * datos.
     *
     * @return null|array
     */
    public function getOzono()
    {
        $url = $this->getUrl('ozono');
        $curl = $this->getCurl($url);

        $fieldNames = ['time_min', 'time_s', 'pressure', 'height', 'temperature',
            'humidity', 'virt_t_c', 'dpd_c', 'l_rate_c_km', 'asc_rate_m_s03',
            'm_pa', 'tb_c'];

        $nFieldNames = count($fieldNames);

        try {
            if ($curl && isset($curl['datos']) && $curl['datos']) {
                $curl2 = $this->getCurl($curl['datos'], false);

                if ($curl2) {
                    $documentToArray = explode("\r\n", $curl2);

                    $arrayClean = [];

                    foreach ($documentToArray as $idx => $value) {
                        $lineTmp = trim($value);
                        $lineTmp = preg_replace('/\s+/', ';', $lineTmp);

                        if ($lineTmp) {
                            $tmpArray = explode(';', $lineTmp);

                            if (count($tmpArray) === $nFieldNames) {
                                $arrayClean[] = array_combine($fieldNames, $tmpArray);
                            }
                        }
                    }

                    return $arrayClean;
                }
            }
        } catch (\Exception $e) {
            \Log::error('Error al obtener la información de la capa de ozono: ' . $e->getMessage());

            return null;
        }

        return null;
    }

    public function getSunRadiation()
    {
        $url = $this->getUrl('sunradiation');
        $curl = $this->getCurl($url);


        if ($curl && isset($curl['datos']) && $curl['datos']) {
            $curl2 = $this->getCurl($curl['datos'], false);
            $curlMetadata = $this->getCurl($curl['metadatos']);

            if (!$curl2 || !$curlMetadata || !isset($curlMetadata['campos'])) {
                return;
            }

            $arrayRaw = explode("\r\n", $curl2);
            $targetArray = null;

            // Estación más cercana "Huelva"
            foreach ($arrayRaw as $line) {
                $arrayRawExplode = explode(';', $line);

                $cleanArray = [];

                $isHuelva = false;

                foreach ($arrayRawExplode as $idx => $attribute) {
                    $attrClean = trim($attribute);
                    $attrClean = str_replace('"', '', $attrClean);


                    if (str_contains($attrClean, 'Huelva')) {
                        $cleanArray = $arrayRawExplode;
                        $isHuelva = true;
                    }
                }

                if ($isHuelva) {
                    foreach ($cleanArray as $idx => $huelvaAttr) {
                        $cleanArray[ $idx ] = trim($huelvaAttr);
                        $cleanArray[ $idx ] = str_replace('"', '', $cleanArray[ $idx ]);
                    }

                    $targetArray = $cleanArray;

                    break;
                }
            }


            // Array de posiciones de los campos
            // ASociar con targetArray posiciones -> array key
            // Almacenar array de campos asociados al id restando 1 a la posición o rango

            $fieldsRaw = $curlMetadata['campos'];
            $positionsArray = [];

            ## Sacamos las posiciones del array como otro array parseando cadenas
            foreach ($fieldsRaw as $field) {
                if (!isset($field['posicion_csv'])) {
                    echo 'NO EXISTE POSICION_CSV';
                    dd($field);

                    continue;
                }

                $postions = [];
                $positionsRaw = $field['posicion_csv'];

                $postionsTmp = explode(',', $positionsRaw);
                foreach ($postionsTmp as $position) {
                    $position = trim($position);
                    $position = str_replace(' ', '', $position);

                    if (str_contains($position, '-')) {
                        $tmp = explode('-', $position);

                        if (count($tmp) !== 2) {
                            echo 'ERROR EN POSICION';
                            dd($postionsTmp, $tmp);
                            continue;
                        }

                        $newRange = range($tmp[0] - 1, $tmp[1] - 1);


                        if ($newRange && count($newRange)) {
                            $postions = array_merge($postions, $newRange);
                        }

                    } else {
                        $postions[] = ( (float)trim($position) ) - 1;
                    }
                }

                $positionsArray[ $field['id'] ]['positions'] = $postions;

                if (isset($field['validacion'])) {
                    $positionsArray[ $field['id'] ]['validation'] = $field['validacion'];
                }

            }


            $finalArray = [];

            $namesArray = [
                "Estación" => [ // Nombre Estación
                    'station',
                ],
                'Indicativo' => [ // Indicativo Climatológico Estación
                    'indicative',
                ],
                'Tipo' => [ // Variable Medida
                    'type_global',
                    'type_diffuse',
                    'type_direct',
                    'type_uv_eritematica',
                    'type_infrarroja'
                ],
                'Hora Solar Verdadera GL/DF/DT' => [ // Radiación horaria acumulada entre: (hora indicada -1) y (hora indicada) entre las 5 y las 20 Hora Solar Verdadera. Variables: Global/Difusa/Directa", "unidad": "10*kJ/m2"
                    'real_solar_hour_global',
                    'real_solar_hour_diffuse',
                    'real_solar_hour_direct',
                ],
                'Suma GL/DF/DT' => [ // Hora Solar Verdadera GL/DF/DT "10*kJ/m2"
                    'sum_global',
                    'sum_diffuse',
                    'sum_direct',
                ],
                'Hora Solar Verdadera UVER' => [ //Radiación semihoraria acumulada entre: (hora:minutos indicados - 30 minutos y (hora:minutos indicados) entre las 4:30 y las 20 Hora  Solar Verdadera. Variables: Radiación Ultravioleta Eritemática "unidad": "J/m2",
                    'real_solar_hour_uver'
                ],
                'Suma UVER' => [
                    'sum_uver'
                ],
                'Hora Solar Verdadera INFRARROJA' => [ //Radiación horaria acumulada entre (hora indicada -1) y (hora indicada) entre las 1 y las 24 Hora Solar Verdadera. Variables: Radiación Infrarroja. "unidad": "10*kJ/m2",
                    'real_solar_hour_infrared'
                ],
                'Suma IR' => [ // Radiación diaria acumulada. Variables: Radiación Infrarroja. "unidad": "10*kJ/m2",
                    'sum_infrared'
                ],
            ];


            if ($targetArray && count($targetArray) && $positionsArray && count($positionsArray)) {
                foreach ($positionsArray as $id => $positions) {

                    $datas = [];

                    foreach ($positions['positions'] as $idx => $position) {
                        $datas[ $idx ] = $targetArray[ $position ];

                    }

                    if (isset($namesArray[ $id ])) {

                        foreach ($namesArray[ $id ] as $i => $name) {
                            if (isset($datas[ $i ])) {
                                $finalArray[ $name ] = $datas[ $i ];
                            } else {
                                echo 'NO DEFINIDO: ' . $i;
                            }
                        }
                    }
                }

                return $finalArray;
            }
        }

        return null;
    }


    public function test()
    {

        $url = 'https://opendata.aemet.es/opendata/api/prediccion/especifica/municipio/horaria/11016';

        $response = $this->getCurl($url);

        return $response;


    }

}
