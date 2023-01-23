<?php

namespace App\Models\WeatherStation;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

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

        $this->API_KEY = env('AEMET_API_KEY');
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
     * Devuelve la predicción diaria para la playa de Regla en Chipiona.
     *
     * La predicción diaria de la playa que se pasa como parámetro. Establece
     * el estado de nubosidad para unas horas determinadas, las 11 y las 17
     * hora oficial. Se analiza también si se espera precipitación en el
     * entorno de esas horas, entre las 08 y las 14 horas y entre las 14 y 20
     * horas.
     *
     * @return array
     */
    public function getPredictionBeachById(int $beachId)
    {

        if ($beachId == 1101602) {
            $url = $this->getUrl('playaCruzDelMarChipiona');
        } else if ($beachId == 1101604) {
            $url = $this->getUrl('playaReglaChipiona');
        } else {
            \Log::error('No existe la playa con ID ' . $beachId);
            return null;
        }

        $curl1 = $this->getCurl($url);

        if ($curl1 && isset($curl1['datos']) && $curl1['datos']) {
            $url2 = $curl1['datos'];
            $curl2 = $this->getCurl($url2);

            $finalArray = [];

            foreach ($curl2 as $register) {
                $sendAtRaw = $register['elaborado'];
                $sendAt = Carbon::parse($sendAtRaw);

                $beachId = $register['id'];
                $name = $register['nombre'];
                $slug = Str::slug($name, '_');
                $cityCode = $register['localidad'];


                ## Recorro cada predicción por cada día
                foreach ($register['prediccion']['dia'] as $prediction) {

                    $predictionDateRaw = $prediction['fecha'];

                    $predictionDate = Carbon::createFromFormat('Ymd', $predictionDateRaw);

                    $skyStatus = [
                        '100' => [
                            'description' => 'Despejado',
                            'code' => 0,
                        ],
                        '110' => [
                            'description' => 'Nuboso',
                            'code' => 1,
                        ],
                        '120' => [
                            'description' => 'Muy nuboso',
                            'code' => 2,
                        ],
                        '130' => [
                            'description' => 'Chubascos',
                            'code' => 3,
                        ],
                        '140' => [
                            'description' => 'Muy nuboso con lluvia',
                            'code' => 4,
                        ],
                    ];

                    $windStatus = [
                        '210' => [
                            'description' => 'Flojo',
                            'code' => 0,
                        ],
                        '220' => [
                            'description' => 'Moderado',
                            'code' => 1,
                        ],
                        '230' => [
                            'description' => 'Fuerte',
                            'code' => 2,
                        ],
                    ];

                    $waveStatus = [
                        '310' => [
                            'description' => 'Débil',
                            'code' => 0,
                        ],
                        '320' => [
                            'description' => 'Moderado',
                            'code' => 1,
                        ],
                        '330' => [
                            'description' => 'Fuerte',
                            'code' => 2,
                        ],
                    ];


                    $thermalSensationStatus = [
                        '410' => [
                            'description' => 'Muy frío',
                            'code' => 0,
                        ],
                        '420' => [
                            'description' => 'Frío',
                            'code' => 1,
                        ],
                        '430' => [
                            'description' => 'Muy Fresco',
                            'code' => 2,
                        ],
                        '440' => [
                            'description' => 'Fresco',
                            'code' => 3,
                        ],
                        '450' => [
                            'description' => 'Suave',
                            'code' => 4,
                        ],
                        '460' => [
                            'description' => 'Calor Agradable',
                            'code' => 5,
                        ],
                        '470' => [
                            'description' => 'Calor Moderado',
                            'code' => 6,
                        ],
                        '480' => [
                            'description' => 'Calor Fuerte',
                            'code' => 7,
                        ],
                    ];

                    $predictionFinal = [
                        'beach_id' => $beachId,
                        'name' => $name,
                        'slug' => $slug,
                        'city_code' => $cityCode,
                        'send_at' => $sendAt,

                        'sky_morning_status_code' => $skyStatus[ $prediction['estadoCielo']['f1'] ]['code'],
                        'sky_morning_status' => $skyStatus[ $prediction['estadoCielo']['f1'] ]['description'],
                        'sky_afternoon_status_code' => $skyStatus[ $prediction['estadoCielo']['f2'] ]['code'],
                        'sky_afternoon_status' => $skyStatus[ $prediction['estadoCielo']['f2'] ]['description'],
                        'sky_extra_info' => $prediction['estadoCielo']['value'],

                        'wind_morning_status_code' => $windStatus[ $prediction['viento']['f1'] ]['code'],
                        'wind_morning_status' => $windStatus[ $prediction['viento']['f1'] ]['description'],
                        'wind_afternoon_status_code' => $windStatus[ $prediction['viento']['f2'] ]['code'],
                        'wind_afternoon_status' => $windStatus[ $prediction['viento']['f2'] ]['description'],
                        'wind_extra_info' => $prediction['viento']['value'],


                        'wave_morning_status_code' => $waveStatus[ $prediction['oleaje']['f1'] ]['code'],
                        'wave_morning_status' => $waveStatus[ $prediction['oleaje']['f1'] ]['description'],
                        'wave_afternoon_status_code' => $waveStatus[ $prediction['oleaje']['f2'] ]['code'],
                        'wave_afternoon_status' => $waveStatus[ $prediction['oleaje']['f2'] ]['description'],
                        'wave_extra_info' => $prediction['oleaje']['value'],


                        'temperature_max' => $prediction['tMaxima']['valor1'],
                        'temperature_max_extra_info' => $prediction['tMaxima']['value'],


                        'thermal_sensation_status_code' => $thermalSensationStatus[ $prediction['sTermica']['valor1'] ]['code'],
                        'thermal_sensation_status' => $thermalSensationStatus[ $prediction['sTermica']['valor1'] ]['description'],
                        'thermal_sensation_extra_info' => $prediction['tMaxima']['value'],


                        'water_temperature' => $prediction['tAgua']['valor1'],
                        'water_temperature_extra_info' => $prediction['tAgua']['value'],


                        // Uv máximo para cielo "despejado"
                        'uv_max' => $prediction['uvMax']['valor1'],
                        'uv_max_extra_info' => $prediction['uvMax']['value'],
                    ];


                    $predictionDateString = $predictionDate->format('Y-m-d');

                    $predictionFinal['date'] = $predictionDateString;

                    $finalArray[ $predictionDateString ] = $predictionFinal;

                }

            }

            return $finalArray;
        }

        return null;
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

        function extractRange(Carbon $readAt, string $stringRange) : array|null
        {
            $stringToArray = explode('-', $stringRange);

            if (! count($stringToArray) === 2) {
                return null;
            }

            $readAt->setMinute(0)->setSecond(0)->setMicrosecond(0);

            $start = clone($readAt);
            $end = clone($readAt);

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

                    $predictionClean[$readAt->format('Y-m-d')] = $tmpArray;
                }


                if ($predictionClean && count($predictionClean)) {
                    $finalArray[] = $predictionClean;
                }
            }


        }

        return $finalArray;
    }

    public function getUviInfo()
    {
        $url = $this->getUrl('predictionUvi');
        $curl1 = $this->getCurl($url);

        try {

            if ($curl1 && isset($curl1['datos']) && $curl1['datos']) {
                $url2 = $curl1['datos'];

                $rawResponse = $this->getCurl($url2, false);

                $rawResponseToArray = explode("\n", $rawResponse);

                $cadiz = '';

                foreach ($rawResponseToArray as $key => $value) {
                    if (str_contains($value, 'diz')) {
                        $cadiz = $value;

                        break;
                    }
                }

                $cadizClean = explode(',', $cadiz)[1];
                $cadizClean = str_replace(['"', "\r"], '', $cadizClean);

                return (int)$cadizClean;
            }
        } catch (\Exception $e) {
            \Log::error('Error al obtener la información de la UV: ' . $e->getMessage());

            return null;
        }

        return null;
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
     * Predicción para altamar en la zona del Atlántico Norte.
     *
     * @return bool|mixed|string|null
     */
    public function getAltamarPrediction()
    {
        $url = $this->getUrl('altamarPrediction');
        $curl = $this->getCurl($url);

        if ($curl && isset($curl['datos']) && $curl['datos']) {
            $curl2 = $this->getCurl($curl['datos']);

            $cadiz = null;

            try {
                foreach ($curl2[0]['prediccion']['zona'] as $value) {
                    if (isset($value['nombre']) && str_contains($value['nombre'], 'diz')) {
                        $cadiz = $value;

                        break;
                    }
                }
            } catch (\Exception $e) {
                \Log::error('Error al obtener la información de la altamar: ' . $e->getMessage());

                return null;
            }

            return $cadiz;
        }

        return null;
    }

    /**
     * Predicción para la costa en la zona Costa de Andalucía Occidental y
     * Ceuta.
     *
     * Revisar datos y filtrar solo los de mi zona.
     *
     * @return bool|mixed|string|null
     */
    public function getCostaPrediction()
    {
        $url = $this->getUrl('costaPrediction');
        $curl = $this->getCurl($url);

        if ($curl && isset($curl['datos']) && $curl['datos']) {
            return $this->getCurl($curl['datos']);
        }

        return null;
    }

    /**
     * Devuelve Ficheros diarios con datos diezminutales de la estación de la
     * red de contaminación de fondo EMEP/VAG/CAMP pasada por parámetro, de
     * temperatura, presión, humedad, viento (dirección y velocidad), radiación
     * global, precipitación y 4 componentes químicos: O3,SO2,NO,NO2 y PM10.
     * Los datos se encuentran en formato FINN (propio del Ministerio de Medio
     * Ambiente). Periodicidad: cada hora.
     *
     * Esto se mide en huelva, Doñana
     *
     * Se actualiza cada diez minutos
     *
     * CUIDADO: Lo que devuelve es un fichero sin formato JSON, locura
     * parsear...
     *
     * @return string|null
     */
    public function getContamination()
    {
        $url = $this->getUrl('contamination');
        $curl = $this->getCurl($url);

        //dd($curl['datos']);
        if ($curl && isset($curl['datos']) && $curl['datos']) {
            $curl2 = $this->getCurl($curl['datos'], false);

            if (!$curl2) {
                return;
            }
        }

        /*
        Ficheros diarios con datos diezminutales de la estación de la red de contaminación de fondo EMEP/VAG/CAMP pasada por parámetro, de temperatura, presión, humedad, viento (dirección y velocidad), radiación global, precipitación y 4 componentes químicos: O3,SO2,NO,NO2 y PM10. Los datos se encuentran en formato FINN (propio del Ministerio de Medio Ambiente). Periodicidad: cada hora.
        */


        /*
         Cadena que viene del documento - api

        18-01-2023 00:10 SO2(001): +00000.42 ug/m3 CV: V FC: 2.66 NO(007):
        +00000.20 ug/m3 CV: V FC: 1.248 NO2(008): +00000.24 ug/m3 CV: V FC: 1.91 O3(014): +00089.69 ug/m3 CV: V FC: 1.99 VEL(081): +00002.72 m/s CV: V FC: 1 DIR(082): +00275.95 GRA CV: V FC: 1 TEM(083): +00010.82 GC CV: V FC: 1 HUM(086): +00083.67 % CV: V FC: 1 PRE(087): +01016.82 hPa CV: V FC: 1 RAD(088): +00000.93 W/m2 CV: V FC: 1 LLU(089): +00000.00 mm CV: V FC: 1 PM10(010): +00000.00 ug/m3 CV: N FC: 1
         */

        $fieldNames = ['timestamp' => 'SO2(001)',
            'temperature', 'pressure', 'humidity', 'wind', 'radiation', 'precipitation', 'o3', 'so2', 'no', 'no2', 'pm10'];


        // 1 - Obtener metadatos
        // 2 - Acceder a "campos" -> "id" -> comprobar "requerido" == true
        // 3 - Acceder a "campos" -> posicion_txt
        // 4 - En cada línea, substr de posición_txt y longitud


        $positionsValuesRange = [];

        if ($curl && isset($curl['metadatos']) && $curl['metadatos']) {
            $metadatas = $this->getCurl($curl['metadatos']);

            if ($metadatas && isset($metadatas['campos']) && $metadatas['campos']) {
                foreach ($metadatas['campos'] as $field) {
                    if ($field['requerido']) {

                        if (isset($field['posición_txt'])) {
                            $position = $field['posición_txt'];
                        } else if (isset($field['posicion_txt'])) {
                            $position = $field['posicion_txt'];
                        } else {
                            $position = null;

                            // Busca cuando hay problemas en la clave, a
                            // veces viene con tilde "posición_txt" pero no es
                            // utf8 que se pueda convertir a json.
                            foreach ($field as $key => $f) {
                                $contains = str_contains($key, '_txt');

                                if ($contains) {
                                    $position = $f;
                                    break;
                                }
                            }

                            if (!$position) {
                                continue;
                            }
                        }

                        $range = explode('-', $position);

                        if (count($range) === 2) {
                            $positionsValuesRange[ trim($field['id']) ] = [
                                'start' => (int)( $range[0] - 1 ),
                                'end' => (int)$range[1],
                                'size' => (int)( $range[1] - $range[0] + 1 ),
                            ];
                        } else if (count($range) === 1) {

                            $id = trim($field['id']);

                            // ¡Corrige la chapuza de los ID duplicados!!!!
                            if ($id === 'Codigo_validacion_O3') {
                                $checkApiFailName = false;

                                if (isset($field['descripcion'])) {
                                    $checkApiFailName = str_contains($field['descripcion'], 'medida de PM10');
                                }

                                if ($checkApiFailName) {
                                    $id = 'Codigo_validacion_PM';
                                }
                            }

                            $positionsValuesRange[ $id ] = [
                                'start' => (int)( $range[0] - 1 ),
                                'end' => (int)$range[0],
                                'size' => 1,
                            ];
                        } else {
                            continue;
                        }

                    }
                }
            }
        }


        // Aquí tendría todas las posiciones para sacar valores por línea
        if ($positionsValuesRange && count($positionsValuesRange)) {

            $validationArraySuccess = ['V', 'O', 'J'];

            $rangeIdConversion = [
                'Fecha' => 'date', // Fecha dd-mm-aaaa
                'Hora' => 'time', // Hora (UTC)  hh:mm
                'SO2' => 'so2', // Componente químico, SO2 en microgramos/m3
                'Codigo_validacion_SO2' => 'so2_validation', // V (válido), O (corregido), J (calma).
                'NO' => 'no', //Componente químico, NO en microgramos/m3
                'Codigo_validacion_NO' => 'no_validation', // V (válido), O (corregido), J (calma)
                'NO2' => 'no2', // Componente químico, NO2 en microgramos/m3
                'Codigo_validacion_NO2' => 'no2_validation', // V (válido), O (corregido), J (calma)
                'O3' => 'o3', // Componente químico, O3 en microgramos/m3
                'Codigo_validacion_O3' => 'o3_validation', // V (válido), O (corregido), J (calma)
                'Velocidad_viento' => 'wind_speed', // Velocidad del viento en m/s
                'Codigo_validacion_velocidad_viento' => 'wind_speed_validation', //V (válido), O (corregido), J (calma)
                'Direccion_viento' => 'wind_direction', // Dirección del viento en grados
                'Codigo_validacion_direccion_viento' => 'wind_direction_validation', // V (válido), O (corregido), J (calma)
                'Temperatura' => 'temperature', //Temperatura en grados celsius
                'Codigo_validacion_temperatura' => 'temperature_validation', // V (válido), O (corregido), J (calma)
                'Humedad' => 'humidity', //Humedad en %
                'Codigo_validacion_humedad' => 'humidity_validation', //V (válido), O (corregido), J (calma)
                'Presion' => 'pressure', // Presión en hPa
                'Codigo_validacion_presion' => 'pressure_validation', // V (válido), O (corregido), J (calma)
                'Raciacion_global' => 'radiation_global', // Radiación global en W/m2
                'Codigo_validacion_radiacion' => 'radiation_global_validation', // V (válido), O (corregido), J (calma)
                'Precipitacion' => 'rain', // Precipitación en mm
                'Codigo_validacion_precipitacion' => 'rain_validation', // V (válido), O (corregido), J (calma)
                'PM10' => 'pm10', // M10 en microgramos/m3
                'Codigo_validacion_PM' => 'pm10_validation', // V (válido), O (corregido), J (calma)
            ];


            $finalResponseArray = [];

            $realDataArray = explode("\n", $curl2);

            if (!$realDataArray) {
                return null;
            }

            function getCharsRangeFromString(string $string, int $start,
                                                    $size)
            {
                return substr($string, $start, $size);
            }


            foreach ($realDataArray as $position => $realDataLine) {

                foreach ($positionsValuesRange as $idx => $range) {
                    $realField = $rangeIdConversion[ $idx ];

                    if (str_contains($realField, '_validation')) {
                        continue;
                    }

                    $start = $range['start'];
                    $end = $range['end'];
                    $size = $range['size'];

                    $subString = getCharsRangeFromString($realDataLine, $start, $size);

                    if (!$subString) {
                        continue;
                    }

                    if ($realField === 'date' || $realField === 'time') {
                        $finalResponseArray[ $position ][ $realField ] = $subString;
                        continue;
                    }

                    $contains = in_array($realField . '_validation',
                        array_values($rangeIdConversion));

                    if (!$contains) {
                        return;
                    }

                    $validationRangeKey = array_search($realField . '_validation',
                        $rangeIdConversion);

                    $validationRange = $positionsValuesRange[ $validationRangeKey ];

                    if (!$validationRange) {
                        continue;
                    }

                    $start2 = $validationRange['start'];
                    $end2 = $validationRange['end'];
                    $size2 = $validationRange['size'];


                    $subStringValidation = getCharsRangeFromString($realDataLine,
                        $start2, $size2);

                    $validation = in_array($subStringValidation,
                        $validationArraySuccess);

                    if ($validation) {
                        $finalResponseArray[ $position ][ $realField ] = (float)$subString;
                    }
                }
            }
        }

        return isset($finalResponseArray) ? $finalResponseArray : null;
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


    /**
     * Últimos Avisos de Fenómenos Meteorológicos adversos elaborado para el
     * área seleccionada. (Andalucía)
     *
     *
     * [
     * [
     * 'name' => 'Nombre de la zona'
     * 'slug' => 'Slug, nombre de la zona con barras bajas "_"'
     * 'polygon' => 'String con las geoposiciones'
     * 'created_at' => Fecha que nos devuelve el documento para el envío de los
     * datos.
     * 'others_fields_json' => 'Campos no identificados, para observar las
     * respuestas durante un tiempo.
     * ]
     * ]
     *
     *
     *
     *
     * @return array|void
     */
    public function getAvisosCap()
    {
        //$tempDir = sys_get_temp_dir();
        $tempDir = '/tmp/api-fryntiz/weather-station/aemet';
        $fileName = 'pack_avisos_cap.tar';
        $tempPath = $tempDir . '/' . $fileName;

        $url = $this->getUrl('avisos_cap');
        $curl = $this->getCurl($url);

        $zoneValidSlugs = [
            'estrecho_de_gibraltar',
            'costa_estrecho',
            'litoral_de_huelva',
            'litoral_gaditano',
            'costa_litoral_gaditano',
            'campina_gaditana'
        ];

        $avisos = [];

        if ($curl && isset($curl['datos'])) {
            if ($curl) {

                $curl2 = file_get_contents($curl['datos']);

                ## Compruebo si existe el directorio temporal para crearlo
                if (!file_exists($tempDir)) {
                    if (!mkdir($tempDir, 0777, true)) {
                        die('Failed to create directories...');
                    }
                }


                ## Guardo el contenido en un fichero temporal
                $save = file_put_contents($tempPath, $curl2);

                if ($save && file_exists($tempPath)) {
                    $tar = new \PharData($tempPath);
                    $tar->extractTo($tempDir, null, true);

                    $files = scandir($tempDir);

                    ## Recorro cada archivo xml para sacar los datos que necesito.
                    foreach ($files as $file) {
                        $jsonFromXml = null;

                        if (str_contains($file, '.xml')) {
                            $content = file_get_contents($tempDir . '/' . $file);

                            $xml = simplexml_load_string($content);
                            $json = json_encode($xml);
                            $jsonFromXml = json_decode($json, true);

                            if (file_exists($tempDir . '/' . $file)) {
                                unlink($tempDir . '/' . $file);
                            }
                        }

                        if (!$jsonFromXml || !count($jsonFromXml)) {
                            continue;
                        }


                        $sentTimestampRaw = $jsonFromXml['sent'];

                        if (!$sentTimestampRaw) {
                            continue;
                        }

                        $sentTimestampParse = strtotime($sentTimestampRaw);
                        $sentTimestamp = Carbon::parse($sentTimestampParse);

                        if (!$sentTimestamp) {
                            continue;
                        }

                        $sentTimestampSlug = $sentTimestamp->format('Y-m-d_H-i-s');


                        ##Recorre cada Info
                        foreach ($jsonFromXml['info'] as $info) {

                            ##Recorre cada área
                            foreach ($info['area'] as $area) {


                                // TODO: comprobar que haya más información de la que se muestra, buscar también en metadatos de la api.

                                $name = isset($area['areaDesc']) ? $area['areaDesc'] : null;
                                $polygon = isset($area['polygon']) ? $area['polygon'] : null;
                                $geocode = isset($area['geocode']['value']) ? $area['geocode']['value'] : null;


                                if (!$name) {
                                    continue;
                                }


                                $slug = Str::slug($name, '_');


                                $otherFields = [];

                                foreach ($area as $f_idx => $f) {
                                    if (!in_array($f_idx, ['areaDesc', 'polygon', 'geocode'])) {
                                        $otherFields[ $f_idx ] = $f;
                                    }
                                }


                                $otherFieldsJson = null;

                                if ($otherFields && count($otherFields)) {
                                    $otherFieldsJson = json_encode(array_filter($otherFields), true);
                                }

                                if (in_array($slug, $zoneValidSlugs)) {


                                    // TODO: Comprobar en unas semanas si ha quedado registrando datos que aún no conozco. Faltaría "type" de alerta y grado de peligro.

                                    $avisos[] = [
                                        'name' => $name,
                                        'slug' => $slug,
                                        'polygon' => $polygon,
                                        'geocode' => $geocode,
                                        'created_at' => $sentTimestamp,
                                        'others_fields_json' => $otherFieldsJson
                                    ];

                                }

                            }
                        }

                    }


                    return $avisos;
                }

            }
        }


    }

    public function test()
    {


        // 1 - Obtener el fichero $response['datos']
        // 2 - almacenar en directorio temporal
        // 3 - Descomprimir "tar"
        // 4 - Listar todos los archivos del directorio tar descomprimido
        // 5 - Recorrer todos los archivos
        // 6 - Obtener el contenido de cada archivo
        // 7 - Parsear el contenido de cada archivo, formato:

        /*
        <alert xmlns = "urn:oasis:names:tc:emergency:cap:1.2">
  <identifier>2.49.0.0.724.0.ES.20230119225002.61VV61ATTA22231674168602</identifier>
  <sender>http://www.aemet.es</sender>
  <sent>2023-01-19T22:50:02-00:00</sent>
  <status>Actual</status>
  <msgType>Alert</msgType>
  <scope>Public</scope>
  <info>
    <language>es-ES</language>
    <category>Met</category>
    <event>Aviso de temperaturas máximas de nivel verde</event>
    <responseType>None</responseType>
    <urgency>Future</urgency>
    <severity>Minor</severity>
    <certainty>Likely</certainty>
    <eventCode>
      <valueName>AEMET-Meteoalerta fenomeno</valueName>
      <value>AT;Temperaturas máximas</value>
    </eventCode>
    <effective>2023-01-19T23:50:02+01:00</effective>
    <onset>2023-01-22T00:00:00+01:00</onset>
    <expires>2023-01-22T23:59:59+01:00</expires>
    <senderName>AEMET. Agencia Estatal de Meteorología</senderName>
    <headline>Aviso de temperaturas máximas de nivel verde. CCAA</headline>
    <web>http://www.aemet.es/es/eltiempo/prediccion/avisos</web>
    <contact>AEMET</contact>
    <parameter>
      <valueName>AEMET-Meteoalerta nivel</valueName>
      <value>verde</value>
    </parameter>
    <area>
      <areaDesc>Valle del Almanzora y Los Vélez</areaDesc>
      <polygon>37.92,-2.21 37.89,-2.17 37.9,-2.12 37.88,-2.1 37.88,-2.06 37.87,-2.02 37.87,-1.97 37.84,-1.99 37.78,-2.01 37.73,-1.99 37.67,-2.01 37.59,-1.95 37.46,-1.84 37.43,-1.81 37.39,-1.84 37.36,-1.9 37.3,-1.95 37.27,-1.89 37.22,-1.89 37.19,-1.92 37.21,-1.96 37.23,-1.98 37.19,-2.02 37.16,-2.03 37.18,-2.11 37.21,-2.13 37.24,-2.2 37.21,-2.27 37.25,-2.27 37.27,-2.3 37.26,-2.34 37.26,-2.4 37.23,-2.42 37.21,-2.5 37.22,-2.55 37.24,-2.55 37.24,-2.62 37.23,-2.66 37.29,-2.66 37.34,-2.64 37.39,-2.63 37.43,-2.58 37.45,-2.57 37.51,-2.46 37.52,-2.36 37.61,-2.37 37.62,-2.31 37.7,-2.32 37.78,-2.28 37.81,-2.3 37.89,-2.29 37.92,-2.21</polygon>
      <geocode>
        <valueName>AEMET-Meteoalerta zona</valueName>
        <value>610401</value>
      </geocode>
    </area>

         */


        $url = 'https://opendata.aemet.es/opendata/api/avisos_cap/ultimoelaborado/area/61';

        $response = $this->getCurl($url);

        return $response;


    }

}
