<?php

declare(strict_types=1);

namespace Tests\Feature\Console;

use App\Models\WeatherStation\AEMET\AEMETDailyPrediction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * `aemet:daily-prediction`.
 *
 * El fixture es real, capturado en directo el 2026-09-16 (ejecutado desde un
 * VPS: la máquina de desarrollo tenía la cuota de este endpoint agotada — ver
 * docs/future/archived/revisar-aemet.md). Recorta a tres de los siete días de la
 * respuesta real para cubrir los tres patrones de tramos que trae AEMET:
 *
 *  - 2026-09-16: 7 tramos (día más próximo), con `uvMax`.
 *  - 2026-09-19: 3 tramos, con `uvMax`.
 *  - 2026-09-22: sin `periodo` (un único elemento por campo), sin `uvMax`
 *    (verificado: AEMET no lo manda para los días más lejanos del rango).
 */
class AemetDailyPredictionCommandTest extends TestCase
{
    use RefreshDatabase;

    private const DAILY_BODY = <<<'JSON'
        [ {
          "origen" : {
            "productor" : "AEMET",
            "copyright" : "AEMET",
            "notaLegal" : "https://www.aemet.es/es/nota_legal"
          },
          "elaborado" : "2026-09-16T12:39:07",
          "nombre" : "Chipiona",
          "provincia" : "Cádiz",
          "prediccion" : {
            "dia" : [ {
              "probPrecipitacion" : [
                {"value": 0, "periodo": "00-24"},
                {"value": 0, "periodo": "00-12"},
                {"value": 0, "periodo": "12-24"},
                {"value": 0, "periodo": "00-06"},
                {"value": 0, "periodo": "06-12"},
                {"value": 0, "periodo": "12-18"},
                {"value": 0, "periodo": "18-24"}
              ],
              "cotaNieveProv" : [
                {"value": "", "periodo": "00-24"},
                {"value": "", "periodo": "00-12"},
                {"value": "", "periodo": "12-24"},
                {"value": "", "periodo": "00-06"},
                {"value": "", "periodo": "06-12"},
                {"value": "", "periodo": "12-18"},
                {"value": "", "periodo": "18-24"}
              ],
              "estadoCielo" : [
                {"value": "", "periodo": "00-24", "descripcion": ""},
                {"value": "", "periodo": "00-12", "descripcion": ""},
                {"value": "12", "periodo": "12-24", "descripcion": "Poco nuboso"},
                {"value": "", "periodo": "00-06", "descripcion": ""},
                {"value": "11", "periodo": "06-12", "descripcion": "Despejado"},
                {"value": "12", "periodo": "12-18", "descripcion": "Poco nuboso"},
                {"value": "12", "periodo": "18-24", "descripcion": "Poco nuboso"}
              ],
              "viento" : [
                {"direccion": "", "velocidad": 0, "periodo": "00-24"},
                {"direccion": "", "velocidad": 0, "periodo": "00-12"},
                {"direccion": "O", "velocidad": 25, "periodo": "12-24"},
                {"direccion": "NO", "velocidad": 20, "periodo": "00-06"},
                {"direccion": "O", "velocidad": 25, "periodo": "06-12"},
                {"direccion": "SO", "velocidad": 25, "periodo": "12-18"},
                {"direccion": "S", "velocidad": 15, "periodo": "18-24"}
              ],
              "rachaMax" : [
                {"value": "", "periodo": "00-24"},
                {"value": "", "periodo": "00-12"},
                {"value": "40", "periodo": "12-24"},
                {"value": "", "periodo": "00-06"},
                {"value": "40", "periodo": "06-12"},
                {"value": "35", "periodo": "12-18"},
                {"value": "", "periodo": "18-24"}
              ],
              "temperatura" : {"maxima": 27, "minima": 20, "dato": []},
              "sensTermica" : {"maxima": 27, "minima": 20, "dato": []},
              "humedadRelativa" : {"maxima": 90, "minima": 65, "dato": []},
              "uvMax" : 6,
              "fecha" : "2026-09-16T00:00:00"
            }, {
              "probPrecipitacion" : [
                {"value": 10, "periodo": "00-24"},
                {"value": 0, "periodo": "00-12"},
                {"value": 10, "periodo": "12-24"}
              ],
              "cotaNieveProv" : [
                {"value": "", "periodo": "00-24"},
                {"value": "", "periodo": "00-12"},
                {"value": "", "periodo": "12-24"}
              ],
              "estadoCielo" : [
                {"value": "13", "periodo": "00-24", "descripcion": "Intervalos nubosos"},
                {"value": "12", "periodo": "00-12", "descripcion": "Poco nuboso"},
                {"value": "13", "periodo": "12-24", "descripcion": "Intervalos nubosos"}
              ],
              "viento" : [
                {"direccion": "E", "velocidad": 20, "periodo": "00-24"},
                {"direccion": "E", "velocidad": 20, "periodo": "00-12"},
                {"direccion": "SE", "velocidad": 20, "periodo": "12-24"}
              ],
              "rachaMax" : [
                {"value": "", "periodo": "00-24"},
                {"value": "", "periodo": "00-12"},
                {"value": "", "periodo": "12-24"}
              ],
              "temperatura" : {"maxima": 31, "minima": 20, "dato": []},
              "sensTermica" : {"maxima": 31, "minima": 20, "dato": []},
              "humedadRelativa" : {"maxima": 90, "minima": 40, "dato": []},
              "uvMax" : 6,
              "fecha" : "2026-09-19T00:00:00"
            }, {
              "probPrecipitacion" : [{"value": 0}],
              "cotaNieveProv" : [{"value": ""}],
              "estadoCielo" : [{"value": "11", "descripcion": "Despejado"}],
              "viento" : [{"direccion": "C", "velocidad": 0}],
              "rachaMax" : [{"value": ""}],
              "temperatura" : {"maxima": 29, "minima": 20, "dato": []},
              "sensTermica" : {"maxima": 29, "minima": 20, "dato": []},
              "humedadRelativa" : {"maxima": 75, "minima": 35, "dato": []},
              "fecha" : "2026-09-22T00:00:00"
            } ]
          },
          "id" : 11016,
          "version" : 1.0
        } ]
        JSON;

    private function fakeDailyPrediction(string $body): void
    {
        $dataUrl = 'https://opendata.aemet.es/opendata/sh/fake-daily';

        Http::fake([
            'https://opendata.aemet.es/opendata/api/prediccion/especifica/municipio/diaria/11016' => Http::response(
                json_encode(['descripcion' => 'exito', 'estado' => 200, 'datos' => $dataUrl]),
                200,
                ['Content-Type' => 'application/json']
            ),
            $dataUrl => Http::response($body, 200, ['Content-Type' => 'application/json;charset=ISO-8859-15']),
        ]);
    }

    #[Test]
    public function it_saves_one_row_per_day_with_the_whole_day_tramo(): void
    {
        $this->fakeDailyPrediction(self::DAILY_BODY);

        $this->artisan('aemet:daily-prediction')->assertExitCode(0);

        $this->assertSame(3, AEMETDailyPrediction::count());

        // Día con 7 tramos: coge el "00-24", que aquí viene vacío para cielo
        // (los tramos con dato están repartidos en sub-franjas), con lluvia y
        // viento sí presentes en ese tramo concreto.
        $day1 = AEMETDailyPrediction::where('date', '2026-09-16')->first();
        $this->assertNotNull($day1);
        $this->assertSame(0, $day1->rain_prob);
        $this->assertNull($day1->sky_status);
        $this->assertNull($day1->wind_direction);
        $this->assertSame(0.0, $day1->wind_speed);
        $this->assertSame(27.0, $day1->temperature_max);
        $this->assertSame(20.0, $day1->temperature_min);
        $this->assertSame(6, $day1->uv_max);
    }

    #[Test]
    public function a_day_with_three_tramos_also_resolves_to_the_whole_day_value(): void
    {
        $this->fakeDailyPrediction(self::DAILY_BODY);

        $this->artisan('aemet:daily-prediction');

        $day4 = AEMETDailyPrediction::where('date', '2026-09-19')->first();
        $this->assertNotNull($day4);
        $this->assertSame(10, $day4->rain_prob);
        $this->assertSame('Intervalos nubosos', $day4->sky_status);
        $this->assertSame('E', $day4->wind_direction);
        $this->assertSame(20.0, $day4->wind_speed);
        $this->assertNull($day4->wind_gust);
    }

    /**
     * Día sin `periodo` (un único elemento) y sin `uvMax` — verificado que
     * AEMET no lo manda para los días más lejanos del rango.
     */
    #[Test]
    public function a_day_without_periodo_or_uv_max_is_handled(): void
    {
        $this->fakeDailyPrediction(self::DAILY_BODY);

        $this->artisan('aemet:daily-prediction');

        $day7 = AEMETDailyPrediction::where('date', '2026-09-22')->first();
        $this->assertNotNull($day7);
        $this->assertSame(0, $day7->rain_prob);
        $this->assertSame('Despejado', $day7->sky_status);
        $this->assertSame('C', $day7->wind_direction);
        $this->assertNull($day7->uv_max);
    }

    #[Test]
    public function running_it_twice_updates_instead_of_duplicating(): void
    {
        $this->fakeDailyPrediction(self::DAILY_BODY);

        $this->artisan('aemet:daily-prediction')->assertExitCode(0);
        $this->artisan('aemet:daily-prediction')->assertExitCode(0);

        $this->assertSame(3, AEMETDailyPrediction::count());
    }
}
