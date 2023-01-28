<?php

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AMETHelper
{
    private static $URL = 'https://opendata.aemet.es/opendata/api';

    private static $API_KEY;

    private static $CITY = 'id11016'; // Chipiona

    private static $PATHS = [
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
    public static function getCurl(string $url, bool $json = true)
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
                'api_key: ' . config('aemet.AEMET_API_KEY'),
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
    public static function getUrl(string $path): string
    {
        return self::$URL . '/' . self::$PATHS[ $path ];
    }

    /**
     * Últimos Avisos de Fenómenos Meteorológicos adversos elaborado para el
     * área seleccionada. (Andalucía)
     *
     * Model: AEMETAdverseEvents
     *
     * @return array|void
     */
    public static function getAvisosCap()
    {
        $tempDir = '/tmp/api-fryntiz/weather-station/aemet';
        $fileName = 'pack_avisos_cap.tar';
        $tempPath = $tempDir . '/' . $fileName;

        $url = self::getUrl('avisos_cap');
        $curl = self::getCurl($url);

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
                                        'read_at' => $sentTimestamp,
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
    public static function getPredictionBeachById(int $beachId)
    {
        if ($beachId == 1101602) {
            $url = self::getUrl('playaCruzDelMarChipiona');
        } else if ($beachId == 1101604) {
            $url = self::getUrl('playaReglaChipiona');
        } else {
            \Log::error('No existe la playa con ID ' . $beachId);
            return null;
        }

        $curl1 = self::getCurl($url);

        if ($curl1 && isset($curl1['datos']) && $curl1['datos']) {
            $url2 = $curl1['datos'];
            $curl2 = self::getCurl($url2);

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
                        'read_at' => $sendAt,

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

    public static function getUviInfo()
    {
        $url = self::getUrl('predictionUvi');
        $curl1 = self::getCurl($url);

        try {

            if ($curl1 && isset($curl1['datos']) && $curl1['datos']) {
                $url2 = $curl1['datos'];

                $rawResponse = self::getCurl($url2, false);

                $arrayMonthsTranslation = [
                    'enero' => 'january',
                    'febrero' => 'february',
                    'marzo' => 'march',
                    'abril' => 'april',
                    'mayo' => 'may',
                    'junio' => 'june',
                    'julio' => 'july',
                    'agosto' => 'august',
                    'septiembre' => 'september',
                    'octubre' => 'october',
                    'noviembre' => 'november',
                    'diciembre' => 'december',
                ];

                $rawResponseToArray = explode("\n", $rawResponse);

                $cadiz = '';
                $timestamp = null;

                foreach ($rawResponseToArray as $key => $value) {
                    if (str_contains($value, 'diz')) {
                        $cadiz = $value;

                        break;
                    } else if (str_contains($value, 'Validez')) {

                        $tmpTimestampArray = explode(',', $value);

                        $tmpTimestamp = str_replace(['"', "\r"], '', $tmpTimestampArray[1]);
                        $tmpTimestamp = trim($tmpTimestamp);
                        $tmpTimestamp = mb_strtolower($tmpTimestamp);
                        $tmpTimestampTranslated = str_replace(
                            array_keys($arrayMonthsTranslation),
                            array_values($arrayMonthsTranslation),
                            $tmpTimestamp
                        );

                        $timestamp = Carbon::parse($tmpTimestampTranslated);
                    }
                }

                $cadizClean = explode(',', $cadiz)[1];
                $cadizClean = str_replace(['"', "\r"], '', $cadizClean);

                if (!$cadizClean || !$timestamp) {
                    return null;
                }

                return [
                    'valid_until_at' => $timestamp,
                    'province_uv_radiation_max' => $cadizClean,
                ];
            }
        } catch (\Exception $e) {
            \Log::error('Error al obtener la información de la UV: ' . $e->getMessage());

            return null;
        }

        return null;
    }

    /**
     * Predicción para altamar en la zona del Atlántico Norte.
     *
     * @return bool|mixed|string|null
     */
    public static function getAltamarPrediction()
    {
        $url = self::getUrl('altamarPrediction');
        $curl = self::getCurl($url);

        if ($curl && isset($curl['datos']) && $curl['datos']) {
            $curl2 = self::getCurl($curl['datos']);

            $cadiz = null;

            $start_at = $curl2[0]['situacion']['inicio'];
            $end_at = $curl2[0]['situacion']['fin'];

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

            return [
                'zone_code' => $cadiz['id'],
                'start_at' => Carbon::parse($start_at),
                'end_at' => Carbon::parse($end_at),
                'text' => $cadiz['texto']
            ];
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
    public static function getCostaPrediction() //shore forecast
    {
        $url = self::getUrl('costaPrediction');
        $curl = self::getCurl($url);


        if ($curl && isset($curl['datos']) && $curl['datos']) {
            $curl2 = self::getCurl($curl['datos']);

            if (!$curl2 || !isset($curl2[0]['prediccion']) || !$curl2[0]['prediccion']) {
                return null;
            }

            $predictions = $curl2[0]['prediccion'];
            $status = $curl2[0]['situacion'];

            if (!$predictions) {
                return null;
            }

            $finalArray = [];

            $generalText = $status['texto'];
            $generalId = $status['id'];
            $generalName = $status['nombre'];
            $generalSlug = Str::slug($generalName);

            ## Recorro todas las predicciones.

            if (!isset($predictions['zona'])) {
                return null;
            }


            $startAt = $predictions['inicio'];
            $endAt = $predictions['fin'];

            $zones = $predictions['zona'];


            foreach ($zones as $zone) {
                $zoneId = $zone['id'];
                $zoneName = $zone['nombre'];
                $zoneSlug = Str::slug($zoneName);

                if (!isset($zone['subzona'])) {
                    continue;
                }

                $subzones = $zone['subzona'];

                foreach ($subzones as $subzone) {
                    $subzoneText = $subzone['texto'];
                    $subzoneId = $subzone['id'];
                    $subzoneName = $subzone['nombre'];
                    $subzoneSlug = Str::slug($subzoneName);


                    // TODO: Textos y slugs dan problemas.

                    //TODO: Revisar curl!!!

                    // TODO: ChekZone == Cádiz o Chipiona!!
                    if ($zoneName || $subzoneName) {
                        $finalArray[] = [
                            'start_at' => $startAt,
                            'end_at' => $endAt,
                            'general_id' => $generalId,
                            'general_name' => $generalName,
                            'general_slug' => $generalSlug,
                            'general_text' => $generalText,
                            'zone_id' => $zoneId,
                            'zone_name' => $zoneName,
                            'zone_slug' => $zoneSlug,
                            'subzone_id' => $subzoneId,
                            'subzone_name' => $subzoneName,
                            'subzone_slug' => $subzoneSlug,
                            'subzone_text' => $subzoneText,
                        ];
                    }
                }
            }

            return $finalArray;

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
    public static function getContamination()
    {
        $url = self::getUrl('contamination');
        $curl = self::getCurl($url);

        if ($curl && isset($curl['datos']) && $curl['datos']) {
            $curl2 = self::getCurl($curl['datos'], false);

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


        $positionsValuesRange = [];

        if ($curl && isset($curl['metadatos']) && $curl['metadatos']) {
            $metadatas = self::getCurl($curl['metadatos']);

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

            /**
             * Devuelve un rango desde el string.
             *
             * @param string $string
             * @param int    $start
             * @param        $size
             *
             * @return string
             */
            function getCharsRangeFromString(string $string, int $start,
                                                    $size): string
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


        if (isset($finalResponseArray) && $finalResponseArray) {
            $finalResponseArray = array_map(function ($ele) {

                if (isset($ele['date']) && $ele['date'] &&
                    isset($ele['time']) && $ele['time']) {

                    $timeStr = $ele['date'] . ' ' . $ele['time'];
                    $timeCarbon = Carbon::createFromFormat('d-m-Y H:i',
                        $timeStr);


                    $ele['date'] = (clone($timeCarbon))->format('Y-m-d');
                    $ele['time'] = (clone($timeCarbon))->format('H:i:s');

                    $ele['read_at'] = $timeCarbon;

                    return $ele;
                }

            }, $finalResponseArray);
        }

        return isset($finalResponseArray) ? $finalResponseArray : null;
    }



    /**
     * Devuelve los datos registrados para la capa de ozono.
     *
     * De la API vuelve un archivo que proceso para devolver un array con los
     * datos.
     *
     * Al parecer, se lanza un globo al cielo cada unos días, este registra
     * una serie de datos que se devuelven en el archivo.
     *
     * Por lo que he deducido, dura aproximadamente 100 minutos en el aire.
     *
     * @return null|array
     */
    public static function getOzone()
    {
        $url = self::getUrl('ozono');
        $curl = self::getCurl($url);

        $fieldNames = [
            'time_min', // Minutos desde el lanzamiento del sondeo
            'time_s', // Segundos desde el lanzamiento del sondeo
            'pressure', // Presión atmosférica en hPa
            'height', // Altura en metros alcanzada por el globo en metros geopotenciales.
            'temperature', // Temperatura en el aire en grados centígrados ºC
            'humidity', // Humedad relativa en %
            'temperature_virtual', // Temperatura virtual en ºC
            'diff_temperature_dew_point', // Diferencia entre la temperatura y el punto de rocío en ºC
            'diff_temperature_per_height_km', // Temperatura entre 2 puntos a 1 km de diferencia en altura ascendente, unidad de medida ºC/km (grados centígrados por kilómetro subido)
            'rate_of_elevation', // Velocidad de ascenso en m/s de la ozonosonda
            'ozone_partial_pressure', // Presión parcial de ozono en mPa, presión de ozono si se eliminaran todos los componentes de la mezcla y sin variación de temperatura
            'device_internal_temperature', // Temperatura interna del dispositivo en ºC
        ];

        $nFieldNames = count($fieldNames);

        try {
            if ($curl && isset($curl['datos']) && $curl['datos']) {
                $curl2 = self::getCurl($curl['datos'], false);

                if ($curl2) {
                    $documentToArray = explode("\r\n", $curl2);

                    $arrayClean = [];

                    ## Obtengo fecha del lanzamiento de la ozonosonda
                    $ozoneProbeLaunchAt = null;

                    ## Ozono integrado (Concentración de ozono medido en Dobson)
                    $ozoneIntegrated = null;

                    ## Ozono residual (Ozono residual de la columna)
                    $ozoneResidual = null;

                    ## Extraigo datos generales como inicio, integrated/residual ozono
                    foreach ($documentToArray as $idx => $line) {
                        $cleanLine = mb_strtolower(trim($line));
                        $cleanLine = preg_replace('/\s+/', ' ', $cleanLine);

                        if (str_contains($cleanLine, 'started at')) {
                            $dateRaw = preg_replace('/started at/', '', $cleanLine);

                            $dateClean = preg_replace('/utc/', '', $dateRaw);
                            $dateClean = trim($dateClean);

                            $ozoneProbeLaunchAt = Carbon::parse($dateClean);
                        }

                        if (str_contains($cleanLine, 'integrated ozone')) {
                            $dataArray = explode(':', $cleanLine);

                            if ($dataArray && is_array($dataArray) && count($dataArray) === 2) {
                                $ozoneIntegrated = (float)trim($dataArray[1]);
                            }
                        }

                        if (str_contains($cleanLine, 'residual ozone')) {
                            $dataArray = explode(':', $cleanLine);

                            if ($dataArray && is_array($dataArray) && count($dataArray) === 2) {
                                $ozoneResidual = (float)trim($dataArray[1]);
                            }
                        }

                        if ($ozoneProbeLaunchAt instanceof Carbon &&
                            $ozoneIntegrated &&
                            $ozoneResidual
                        ) {
                            break;
                        }
                    }


                    if (! ($ozoneProbeLaunchAt instanceof Carbon)) {
                        Log::error('AEMET getOzone() No se ha podido parsear la fecha de lanzamiento para la ozonosonda');

                        return null;
                    }

                    foreach ($documentToArray as $value) {
                        $lineTmp = trim($value);
                        $lineTmp = preg_replace('/\s+/', ';', $lineTmp);

                        if ($lineTmp) {
                            $tmpArray = explode(';', $lineTmp);

                            if (count($tmpArray) === $nFieldNames) {
                                $fArrayClean = array_combine(
                                    $fieldNames,
                                    $tmpArray
                                );

                                $fArrayClean['time_min'] = (int)$fArrayClean['time_min'];
                                $fArrayClean['time_s'] = (int)$fArrayClean['time_s'];
                                $fArrayClean['pressure'] = (float)$fArrayClean['pressure'];
                                $fArrayClean['height'] = (int)$fArrayClean['height'];
                                $fArrayClean['temperature'] = (float)$fArrayClean['temperature'];
                                $fArrayClean['humidity'] = (int)$fArrayClean['humidity'];
                                $fArrayClean['temperature_virtual'] = (float)$fArrayClean['temperature_virtual'];
                                $fArrayClean['diff_temperature_dew_point'] = (float)$fArrayClean['diff_temperature_dew_point'];
                                $fArrayClean['diff_temperature_per_height_km'] = (float)$fArrayClean['diff_temperature_per_height_km'];
                                $fArrayClean['rate_of_elevation'] = (float)$fArrayClean['rate_of_elevation'];
                                $fArrayClean['ozone_partial_pressure'] = (float)$fArrayClean['ozone_partial_pressure'];
                                $fArrayClean['device_internal_temperature'] = (float)$fArrayClean['device_internal_temperature'];

                                $ozoneProbeReadAt = clone($ozoneProbeLaunchAt);
                                $ozoneProbeReadAt->addMinutes($fArrayClean['time_min']);
                                $ozoneProbeReadAt->addSeconds($fArrayClean['time_s']);

                                $fArrayClean['ozone_probe_launch_at'] = $ozoneProbeLaunchAt;
                                $fArrayClean['ozone_probe_read_at'] = $ozoneProbeReadAt; // Calculo el momento de la lectura
                                $fArrayClean['ozone_integrated'] = $ozoneIntegrated;
                                $fArrayClean['ozone_residual'] = $ozoneResidual;

                                $arrayClean[] = $fArrayClean;

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

}
