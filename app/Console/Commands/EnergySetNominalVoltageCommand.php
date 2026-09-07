<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Hardware\EnergySystem;
use App\Models\Hardware\HardwareEnergy;
use Illuminate\Console\Command;

/**
 * Rellena la tensión nominal y el rango plausible de cada elemento de energía.
 *
 * **Qué es `nominal_voltage` y qué no es.** No es el dato con el que se calcula:
 * mientras el aparato mande su tensión medida, se usa la medida. Es el
 * **respaldo** de {@see HardwareEnergy::resolveVoltage()} para cuando no la
 * manda o manda una que no se cree. Hoy los ocho elementos lo tienen a `NULL`,
 * así que en ese caso no habría vatios para esa lectura.
 *
 * **El criterio: la tensión del lado que ese elemento mide.** Un controlador
 * solar tiene dos elementos, uno de generación y otro de consumo, y **no están
 * a la misma tensión**:
 *
 *  - el de **generación** mide el lado del **panel**;
 *  - el de **consumo** mide la salida hacia la **batería**.
 *
 * Poner lo mismo en los dos sería meter otra vez el error que se acaba de quitar
 * de `/hardware/energy`: comparar magnitudes de lados distintos.
 *
 * **Y la tensión del panel no es la misma en todas las instalaciones**, así que
 * no puede ser un valor global del comando:
 *
 * | Instalación | Panel | Batería |
 * |---|---|---|
 * | Renogy Rover 20 LI | **24 V** (medido hasta 43,9 V) | 12 V (11,0-14,3 V) |
 * | Sunix 20A | **12 V** (llega a 18-20 V) | 12 V (hasta 13,8 V) |
 *
 * Sale de `energy_systems.pv_nominal_voltage`, que es donde vive ese dato desde
 * que se añadió la columna; `--panel` es sólo el respaldo para un elemento sin
 * sistema asignado. Equivocarse aquí no da error: sólo hace que los vatios de
 * respaldo del Sunix salgan al doble el día que deje de mandar su tensión.
 *
 * **Y el rango importa tanto como la nominal.** Sin `voltage_min`/`voltage_max`,
 * `voltageIsPlausible()` los deduce de la nominal con un margen de ×0,5 a ×2,0.
 * Para una batería eso vale (12 V → 6-24 V, y las lecturas reales van de 11 a
 * 14,3). **Para un panel solar no**: 24 V daría 12-48 V, y el 44,7 % de las
 * lecturas del Renogy están por debajo de 12 V —de noche el panel no da nada—,
 * así que se sustituirían por la nominal. Hoy no haría daño porque en esas
 * lecturas la corriente es cero, pero el día que el panel reporte 8 V con 0,3 A
 * al amanecer, eso serían 7,2 W en vez de 2,4. Por eso a los generadores se les
 * pone el rango a mano.
 *
 *     php artisan energy:set-nominal-voltage            # enseña lo que haría
 *     php artisan energy:set-nominal-voltage --write    # lo aplica
 */
class EnergySetNominalVoltageCommand extends Command
{
    protected $signature = 'energy:set-nominal-voltage
        {--write : Aplicar los cambios. Sin esto sólo se enseña la tabla}
        {--pv=* : Declara la tensión del campo solar de una instalación: --pv=3:24}
        {--panel=24 : Tensión de los paneles para elementos sin sistema asignado}
        {--battery=12 : Tensión del banco de baterías para elementos sin sistema}';

    protected $description = 'Rellena `nominal_voltage` y el rango plausible de los elementos de `hardware_energy`';

    public function handle(): int
    {
        $panel = (float) $this->option('panel');
        $bateria = (float) $this->option('battery');
        $escribir = (bool) $this->option('write');

        if (! $this->declararTensionesDeCampoSolar($escribir)) {
            return self::FAILURE;
        }

        $elementos = HardwareEnergy::query()->with('system')->orderBy('id')->get();

        if ($elementos->isEmpty()) {
            $this->info('No hay elementos de energía.');

            return self::SUCCESS;
        }

        $filas = [];
        $cambios = 0;

        foreach ($elementos as $elemento) {
            $valores = $this->valoresPara($elemento, $panel, $bateria);

            $yaEsta = (float) ($elemento->nominal_voltage ?? 0) === $valores['nominal_voltage']
                && (float) ($elemento->voltage_min ?? -1) === $valores['voltage_min']
                && (float) ($elemento->voltage_max ?? -1) === $valores['voltage_max'];

            $filas[] = [
                $elemento->id,
                mb_substr((string) $elemento->name, 0, 26),
                $elemento->is_generator ? 'genera' : 'consume',
                $this->nombreDeLaInstalacion($elemento),
                $elemento->nominal_voltage ?? '—',
                $valores['nominal_voltage'],
                $valores['voltage_min'].' – '.$valores['voltage_max'],
                $yaEsta ? 'ya está' : ($escribir ? 'aplicado' : 'cambiaría'),
            ];

            if ($yaEsta) {
                continue;
            }

            $cambios++;

            if ($escribir) {
                $elemento->forceFill($valores)->save();
            }
        }

        $this->table(
            ['#', 'Elemento', 'Lado', 'Instalación', 'Antes', 'Nominal', 'Rango plausible', ''],
            $filas,
        );

        if ($cambios === 0) {
            $this->info('Todo estaba ya en su sitio.');

            return self::SUCCESS;
        }

        if ($escribir) {
            $this->info("Actualizados {$cambios} elementos.");
        } else {
            $this->warn("Modo seco: no se ha escrito nada. Cambiarían {$cambios} elementos.");
            $this->line('Añade --write para aplicarlo.');
        }

        return self::SUCCESS;
    }

    /**
     * El nombre de la instalación del elemento, recortado para la tabla.
     */
    private function nombreDeLaInstalacion(HardwareEnergy $elemento): string
    {
        if ($elemento->energy_system_id === null) {
            return '—';
        }

        $sistema = $elemento->getRelationValue('system');

        return $sistema instanceof EnergySystem
            ? mb_substr((string) $sistema->name, 0, 22)
            : '—';
    }

    /**
     * La tensión que se acaba de declarar para una instalación, si es que se ha
     * declarado. Así el modo seco enseña lo que de verdad quedaría.
     */
    private function tensionDeclarada(?int $sistemaId): ?float
    {
        if ($sistemaId === null) {
            return null;
        }

        foreach ((array) $this->option('pv') as $declaracion) {
            if (preg_match('/^(\d+):(\d+(?:\.\d+)?)$/', trim((string) $declaracion), $partes) === 1
                && (int) $partes[1] === $sistemaId) {
                return (float) $partes[2];
            }
        }

        return null;
    }

    /**
     * Aplica los `--pv=<sistema>:<voltios>` que se hayan pasado.
     *
     * La tensión del campo solar vive en `energy_systems.pv_nominal_voltage`,
     * que es donde tiene que estar y donde se edita desde el panel. Esto es
     * para poder dejarlo puesto de una sola pasada, sobre todo en el servidor.
     *
     * @return bool `false` si algún argumento no vale, para no seguir a medias.
     */
    private function declararTensionesDeCampoSolar(bool $escribir): bool
    {
        /** @var list<string> $declaraciones */
        $declaraciones = (array) $this->option('pv');

        if ($declaraciones === []) {
            return true;
        }

        foreach ($declaraciones as $declaracion) {
            if (preg_match('/^(\d+):(\d+(?:\.\d+)?)$/', trim($declaracion), $partes) !== 1) {
                $this->error("«{$declaracion}» no vale. El formato es --pv=<id del sistema>:<voltios>, por ejemplo --pv=3:24");

                return false;
            }

            [$_, $id, $voltios] = $partes;

            $sistema = EnergySystem::find((int) $id);

            if ($sistema === null) {
                $this->error("No hay ninguna instalación con id {$id}.");

                return false;
            }

            $this->line("▶ Campo solar de «{$sistema->name}» (#{$id}): {$voltios} V");

            if ($escribir) {
                $sistema->forceFill(['pv_nominal_voltage' => (float) $voltios])->save();
            }
        }

        $this->newLine();

        return true;
    }

    /**
     * La tensión del lado que mide este elemento, con su rango creíble.
     *
     * @return array{nominal_voltage: float, voltage_min: float, voltage_max: float}
     */
    private function valoresPara(HardwareEnergy $elemento, float $panel, float $bateria): array
    {
        // `energy_system_id` es nullable, así que un elemento puede no estar
        // asignado a ninguna instalación. En ese caso mandan los flags.
        $sistema = $elemento->relationLoaded('system') || $elemento->energy_system_id !== null
            ? $elemento->getRelationValue('system')
            : null;

        if ($elemento->is_generator) {
            // La del campo solar de SU instalación. El flag `--panel` es sólo
            // el respaldo para un elemento que no esté asignado a ninguna.
            if ($sistema instanceof EnergySystem) {
                $panel = $this->tensionDeclarada($sistema->id)
                    ?? $sistema->pv_nominal_voltage
                    ?? $panel;
            }

            return [
                'nominal_voltage' => $panel,
                // Casi cero: de noche el panel no da nada y esa lectura es
                // correcta, no un fallo del sensor. Con el margen automático
                // (×0,5) se descartarían y se sustituirían por la nominal.
                'voltage_min' => 0.1,
                // En vacío un panel de 24 V nominales sube bastante por encima:
                // el máximo medido en el Renogy es 43,9 V.
                'voltage_max' => round($panel * 2.1, 1),
            ];
        }

        // La del banco de baterías, que es la que define la instalación.
        if ($sistema instanceof EnergySystem) {
            $bateria = $sistema->nominal_voltage ?? $bateria;
        }

        return [
            'nominal_voltage' => $bateria,
            // Una batería de plomo de 12 V por debajo de 10 V está destrozada, y
            // por encima de 15,5 no la carga nadie: fuera de ahí es el sensor.
            'voltage_min' => round($bateria * 0.83, 1),
            'voltage_max' => round($bateria * 1.29, 1),
        ];
    }
}
