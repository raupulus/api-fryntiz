<?php

use Carbon\Carbon;
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
}
