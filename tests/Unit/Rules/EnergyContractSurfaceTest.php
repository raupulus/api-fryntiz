<?php

declare(strict_types=1);

namespace Tests\Unit\Rules;

use App\Rules\EnergyTelemetryPayload;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * La superficie del contrato de subida, en los dos sentidos.
 *
 * Este módulo se ha roto dos veces por el mismo sitio, y las dos en silencio:
 *
 * 1. **Campos que el servicio leía sin estar declarados.** Laravel no rechaza lo
 *    que no conoce, así que entraban tal cual y se casteaban: un `"hola"` en
 *    `total_operating_days` acababa siendo un 0.
 * 2. **Campos que el firmware enviaba y nadie leía.** El contrato de la V1
 *    recibía los amperios-hora de carga y de descarga del Renogy Rover; al
 *    reescribir el módulo se quedaron fuera, el servidor siguió respondiendo 201
 *    y el dato desaparecía. Eso no se nota hasta meses después.
 *
 * Estas pruebas fijan que las dos listas —lo declarado y lo leído— sean la
 * misma, y que los tres bloques ofrezcan los mismos acumuladores. No prueban
 * comportamiento; prueban que el contrato no se descuelgue del código.
 */
class EnergyContractSurfaceTest extends TestCase
{
    /**
     * Los tres bloques del contrato.
     *
     * @var list<string>
     */
    private const BLOQUES = ['generator', 'battery', 'loads.*'];

    /**
     * Lo que el aparato puede declarar, y que el servidor guarda tal cual en
     * vez de calcularlo.
     *
     * @var list<string>
     */
    private const DECLARABLES = [
        'today_energy_wh',
        'today_energy_ah',
        'today_voltage_min',
        'today_voltage_max',
        'today_amperage_max',
        'today_power_max',
        'historical_energy_wh',
        'historical_energy_ah',
        'battery_full_charges',
        'battery_over_discharges',
        'total_operating_days',
        'days_operating',
    ];

    /**
     * Los campos que declara la validación, por bloque.
     *
     * @return array<string, list<string>>
     */
    private function declarados(): array
    {
        $porBloque = [];

        foreach (array_keys(EnergyTelemetryPayload::rules()) as $clave) {
            foreach (self::BLOQUES as $bloque) {
                if (str_starts_with($clave, $bloque.'.')) {
                    $porBloque[$bloque][] = substr($clave, strlen($bloque) + 1);
                }
            }
        }

        return $porBloque;
    }

    /**
     * Los campos que el servicio lee de un bloque de la telemetría.
     *
     * Se saca del propio fuente porque es la única forma de detectar que
     * alguien añade una lectura y se olvida de declararla.
     *
     * @return list<string>
     */
    private function leidosPorElServicio(string $variable): array
    {
        $fuente = file_get_contents(base_path('app/Services/Hardware/HardwareService.php'));

        // Accesos directos del bloque: $genData['voltage'], $batData['soc']…
        preg_match_all('/\$'.$variable."\['([a-z_]+)'\]/", $fuente, $directos);

        // Y los de los dos ayudantes comunes, que reciben el bloque entero y
        // valen para los tres.
        preg_match_all("/\\\$numero\('([a-z_]+)'\)/", $fuente, $comunes);
        preg_match_all("/\\\$bloque\['([a-z_]+)'\]/", $fuente, $comunesDirectos);

        return array_values(array_unique(array_merge(
            $directos[1],
            $comunes[1],
            $comunesDirectos[1]
        )));
    }

    #[Test]
    public function every_field_the_service_reads_is_validated(): void
    {
        $declarados = $this->declarados();

        $variables = [
            'generator' => 'genData',
            'battery' => 'batData',
            'loads.*' => 'loadData',
        ];

        foreach ($variables as $bloque => $variable) {
            $sinDeclarar = array_diff(
                $this->leidosPorElServicio($variable),
                $declarados[$bloque] ?? []
            );

            $this->assertSame(
                [],
                array_values($sinDeclarar),
                "El servicio lee campos de `{$bloque}` que la validación no declara: "
                .'entran sin validar y se castean en silencio.'
            );
        }
    }

    #[Test]
    public function every_validated_field_is_read_by_the_service(): void
    {
        $variables = [
            'generator' => 'genData',
            'battery' => 'batData',
            'loads.*' => 'loadData',
        ];

        foreach ($this->declarados() as $bloque => $campos) {
            // `channel` y `sensor_position` los resuelve el propio bucle de
            // consumos antes de llamar a nada, con su alias.
            $campos = array_diff($campos, ['channel', 'sensor_position']);

            $sinLeer = array_diff($campos, $this->leidosPorElServicio($variables[$bloque]));

            $this->assertSame(
                [],
                array_values($sinLeer),
                "La validación declara campos de `{$bloque}` que el servicio no lee: "
                .'el firmware los manda creyendo que se guardan y no se guardan.'
            );
        }
    }

    #[Test]
    public function the_three_blocks_offer_the_same_accumulators(): void
    {
        // Un generador, una batería y un consumo describen lo mismo de distinta
        // manera. Tener `today_energy_ah` sólo en unos sitios fue lo que hizo
        // que los amperios-hora de descarga del Rover se perdieran.
        $declarados = $this->declarados();

        foreach (self::BLOQUES as $bloque) {
            foreach (self::DECLARABLES as $campo) {
                $this->assertContains(
                    $campo,
                    $declarados[$bloque] ?? [],
                    "El bloque `{$bloque}` no acepta `{$campo}`."
                );
            }
        }
    }

    #[Test]
    public function the_three_blocks_offer_the_same_instant_measurements(): void
    {
        $declarados = $this->declarados();

        foreach (['voltage', 'amperage', 'power', 'temperature', 'energy_wh', 'energy_ah'] as $campo) {
            foreach (self::BLOQUES as $bloque) {
                $this->assertContains(
                    $campo,
                    $declarados[$bloque] ?? [],
                    "El bloque `{$bloque}` no acepta `{$campo}`."
                );
            }
        }
    }

    #[Test]
    public function the_per_block_table_of_the_contract_matches_the_validation(): void
    {
        // La tabla «Lo que se mide ahora» del contrato marca con ✓ qué bloques
        // aceptan cada campo instantáneo. Es lo que lee quien escribe el
        // firmware, así que tiene que decir exactamente lo que hace el servidor.
        $doc = file_get_contents(base_path('docs/info/api/v2/energy.md'));
        $declarados = $this->declarados();

        $bloques = ['generator', 'battery', 'loads.*'];
        $filas = 0;

        foreach (explode("\n", $doc) as $linea) {
            // | `campo` | tipo | ✓ | — | ✓ | descripción |
            if (! preg_match('/^\| `([a-z_]+)` \| [^|]+ \| ([✓—]) \| ([✓—]) \| ([✓—]) \|/u', $linea, $m)) {
                continue;
            }

            $filas++;
            [, $campo, $gen, $bat, $load] = $m;

            foreach ([$gen, $bat, $load] as $i => $marca) {
                $bloque = $bloques[$i];
                $aceptado = in_array($campo, $declarados[$bloque] ?? [], true);

                // `battery_percentage` es el alias de `soc`: el contrato lo
                // documenta como alias y la validación declara los dos.
                if ($campo === 'soc' && ! $aceptado) {
                    $aceptado = in_array('battery_percentage', $declarados[$bloque] ?? [], true);
                }

                $this->assertSame(
                    $marca === '✓',
                    $aceptado,
                    "El contrato y la validación no coinciden en `{$bloque}.{$campo}`."
                );
            }
        }

        $this->assertGreaterThanOrEqual(10, $filas, 'No se ha encontrado la tabla por bloque del contrato.');
    }

    #[Test]
    public function the_documented_contract_matches_the_validation(): void
    {
        // La documentación es lo que se copia a los clientes IoT. Si promete un
        // campo que no existe, el firmware lo manda y se pierde; si se deja uno,
        // nadie lo usa nunca.
        $doc = file_get_contents(base_path('docs/info/api/v2/energy.md'));

        foreach ($this->declarados() as $bloque => $campos) {
            foreach ($campos as $campo) {
                $this->assertStringContainsString(
                    '`'.$campo.'`',
                    $doc,
                    "`docs/info/api/v2/energy.md` no documenta `{$campo}` del bloque `{$bloque}`."
                );
            }
        }
    }

    #[Test]
    public function the_battery_accepts_negative_energy_and_the_rest_does_not(): void
    {
        // En una batería la energía del intervalo es el neto y puede ser
        // negativa mientras descarga. En un generador o un consumo, no.
        $reglas = EnergyTelemetryPayload::rules();

        $this->assertNotContains('min:0', $reglas['battery.energy_wh']);
        $this->assertNotContains('min:0', $reglas['battery.energy_ah']);
        $this->assertNotContains('min:0', $reglas['battery.today_energy_ah']);

        $this->assertContains('min:0', $reglas['generator.energy_wh']);
        $this->assertContains('min:0', $reglas['loads.*.energy_wh']);

        // Un acumulado de por vida no puede bajar de cero en ningún caso.
        $this->assertContains('min:0', $reglas['battery.historical_energy_ah']);
        $this->assertContains('min:0', $reglas['battery.historical_energy_wh']);
    }
}
