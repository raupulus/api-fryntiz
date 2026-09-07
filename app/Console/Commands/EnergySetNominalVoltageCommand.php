<?php

declare(strict_types=1);

namespace App\Console\Commands;

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
 *  - el de **generación** mide el lado del **panel** → 24 V nominales
 *    (aunque en vacío llegue a 35-40 V);
 *  - el de **consumo** mide la salida hacia la **batería** → 12 V
 *    (entre 11,8 y 13,8 V según carga y sol).
 *
 * Poner 12 V en los dos sería meter otra vez el error que se acaba de quitar de
 * `/hardware/energy`: comparar magnitudes de lados distintos.
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
        {--panel=24 : Tensión nominal de los paneles}
        {--battery=12 : Tensión nominal del banco de baterías}';

    protected $description = 'Rellena `nominal_voltage` y el rango plausible de los elementos de `hardware_energy`';

    public function handle(): int
    {
        $panel = (float) $this->option('panel');
        $bateria = (float) $this->option('battery');
        $escribir = (bool) $this->option('write');

        $elementos = HardwareEnergy::query()->orderBy('id')->get();

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
                mb_substr((string) $elemento->name, 0, 28),
                $elemento->is_generator ? 'genera' : 'consume',
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
            ['#', 'Elemento', 'Lado', 'Antes', 'Nominal', 'Rango plausible', ''],
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
     * La tensión del lado que mide este elemento, con su rango creíble.
     *
     * @return array{nominal_voltage: float, voltage_min: float, voltage_max: float}
     */
    private function valoresPara(HardwareEnergy $elemento, float $panel, float $bateria): array
    {
        if ($elemento->is_generator) {
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

        return [
            'nominal_voltage' => $bateria,
            // Una batería de plomo de 12 V por debajo de 10 V está destrozada, y
            // por encima de 15,5 no la carga nadie: fuera de ahí es el sensor.
            'voltage_min' => round($bateria * 0.83, 1),
            'voltage_max' => round($bateria * 1.29, 1),
        ];
    }
}
