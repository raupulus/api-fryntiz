<?php

declare(strict_types=1);

namespace App\Console\Commands\Energy;

use App\Models\Hardware\HardwareEnergy;
use App\Models\Hardware\HardwareEnergyHistorical;
use App\Models\Hardware\HardwareEnergyReading;
use App\Models\Hardware\HardwareEnergyToday;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Consolida y recalcula los agregados diarios e históricos de energía para
 * elementos con recálculo automático (`auto_calculate_history = true`).
 *
 * Corre a las 00:05 para cerrar el día anterior con todas sus lecturas, o bajo
 * demanda para cualquier fecha o elemento.
 *
 * **Todo en UTC**, igual que `created_at` de las lecturas y `date` de los
 * resúmenes: lo que se guarda va en UTC y a `Europe/Madrid` se traduce sólo al
 * pintarlo. Cuando esto cortaba el día en hora de Madrid y la ingesta lo
 * cortaba en UTC, las lecturas de las dos primeras horas del día local caían en
 * la fila de un día y se contaban en la del otro.
 */
class AggregateDailyEnergyCommand extends Command
{
    protected $signature = 'energy:aggregate-daily
        {--date= : Fecha UTC a consolidar en formato YYYY-MM-DD. Por defecto, ayer}
        {--today : Consolidar la fecha de hoy en curso}
        {--element= : ID específico de HardwareEnergy a consolidar}
        {--all : Forzar consolidación incluso en elementos con auto_calculate_history=false}
        {--rebuild : Reconstruir el acumulado aunque los resúmenes diarios no cubran toda su historia (DESTRUCTIVO)}';

    protected $description = 'Consolida y recalcula los agregados diarios e históricos de energía';

    public function handle(): int
    {
        $date = $this->resolveTargetDate();

        if ($date === null) {
            $this->error('Formato de fecha inválido. Utilice el formato YYYY-MM-DD.');

            return self::FAILURE;
        }

        $elementsQuery = HardwareEnergy::query()->where('is_active', true);

        if (! $this->option('all')) {
            $elementsQuery->where('auto_calculate_history', true);
        }

        if ($elementId = $this->option('element')) {
            $elementsQuery->where('id', (int) $elementId);
        }

        $elements = $elementsQuery->get();

        if ($elements->isEmpty()) {
            $this->info("No hay elementos energéticos que procesar para la fecha {$date}.");

            return self::SUCCESS;
        }

        $this->info("Consolidando agregados de energía para la fecha {$date} ({$elements->count()} elementos)...");
        $this->line('El día se corta en UTC, igual que se guardan las lecturas.');

        $startOfDay = Carbon::parse($date, 'UTC')->startOfDay();
        $endOfDay = Carbon::parse($date, 'UTC')->endOfDay();
        $processedCount = 0;

        foreach ($elements as $element) {
            $this->aggregateElementForDate($element, $date, $startOfDay, $endOfDay);
            $processedCount++;
        }

        $this->info("Consolidación finalizada con éxito. {$processedCount} elementos procesados.");

        return self::SUCCESS;
    }

    private function resolveTargetDate(): ?string
    {
        if ($this->option('today')) {
            return Carbon::today('UTC')->toDateString();
        }

        $dateOption = $this->option('date');
        if (is_string($dateOption) && $dateOption !== '') {
            try {
                return Carbon::createFromFormat('Y-m-d', $dateOption, 'UTC')?->toDateString();
            } catch (\Exception) {
                return null;
            }
        }

        return Carbon::yesterday('UTC')->toDateString();
    }

    private function aggregateElementForDate(
        HardwareEnergy $element,
        string $date,
        Carbon $startOfDay,
        Carbon $endOfDay
    ): void {
        DB::transaction(function () use ($element, $date, $startOfDay, $endOfDay) {
            // 1. Agregados del día desde HardwareEnergyReading
            /** @var \stdClass|null $agg */
            $agg = HardwareEnergyReading::query()
                ->forElement($element->id)
                ->reliable()
                ->whereBetween('created_at', [$startOfDay, $endOfDay])
                ->toBase()
                ->selectRaw('
                    COUNT(*) as readings_count,
                    COALESCE(SUM(energy_wh), 0) as sum_wh,
                    COALESCE(SUM(energy_ah), 0) as sum_ah,
                    MIN(voltage) as min_voltage,
                    MAX(voltage) as max_voltage,
                    MIN(amperage) as min_amperage,
                    MAX(amperage) as max_amperage,
                    MIN(power) as min_power,
                    MAX(power) as max_power,
                    MIN(temperature) as min_temperature,
                    MAX(temperature) as max_temperature,
                    MIN(battery_voltage) as min_battery_voltage,
                    MAX(battery_voltage) as max_battery_voltage,
                    MIN(battery_percentage) as min_battery_percentage,
                    MAX(battery_percentage) as max_battery_percentage,
                    MIN(fan) as min_fan,
                    MAX(fan) as max_fan
                ')
                ->first();

            $readingsCount = (int) ($agg->readings_count ?? 0);

            // Si hay lecturas en ese día, actualizamos o creamos HardwareEnergyToday
            if ($agg !== null && $readingsCount > 0) {
                // La fila del día la identifican el elemento y la fecha, que es
                // la clave del índice único. Añadir el dispositivo hacía que una
                // fila con otro `hardware_device_id` —la migración atribuyó
                // algunas al aparato monitorizado— no se encontrara, y el
                // `save()` posterior chocara contra el índice.
                /** @var HardwareEnergyToday $todayRecord */
                $todayRecord = HardwareEnergyToday::firstOrNew([
                    'hardware_energy_id' => $element->id,
                    'date' => $date,
                ]);

                $todayRecord->hardware_device_id = $element->hardware_device_id;

                $finalWh = max((float) ($todayRecord->energy_wh ?? 0), (float) $agg->sum_wh);
                $finalAh = max((float) ($todayRecord->energy_ah ?? 0), (float) $agg->sum_ah);

                $todayRecord->fill([
                    'readings_count' => $readingsCount,
                    'energy_wh' => round($finalWh, 4),
                    'energy_ah' => round($finalAh, 4),
                    'voltage_min' => $agg->min_voltage !== null ? (float) $agg->min_voltage : $todayRecord->voltage_min,
                    'voltage_max' => $agg->max_voltage !== null ? (float) $agg->max_voltage : $todayRecord->voltage_max,
                    'amperage_min' => $agg->min_amperage !== null ? (float) $agg->min_amperage : $todayRecord->amperage_min,
                    'amperage_max' => $agg->max_amperage !== null ? (float) $agg->max_amperage : $todayRecord->amperage_max,
                    'power_min' => $agg->min_power !== null ? (float) $agg->min_power : $todayRecord->power_min,
                    'power_max' => $agg->max_power !== null ? (float) $agg->max_power : $todayRecord->power_max,
                    'temperature_min' => $agg->min_temperature !== null ? (float) $agg->min_temperature : $todayRecord->temperature_min,
                    'temperature_max' => $agg->max_temperature !== null ? (float) $agg->max_temperature : $todayRecord->temperature_max,
                    'battery_min' => $agg->min_battery_voltage !== null ? (float) $agg->min_battery_voltage : $todayRecord->battery_min,
                    'battery_max' => $agg->max_battery_voltage !== null ? (float) $agg->max_battery_voltage : $todayRecord->battery_max,
                    'battery_percentage_min' => $agg->min_battery_percentage !== null ? (int) $agg->min_battery_percentage : $todayRecord->battery_percentage_min,
                    'battery_percentage_max' => $agg->max_battery_percentage !== null ? (int) $agg->max_battery_percentage : $todayRecord->battery_percentage_max,
                    'fan_min' => $agg->min_fan !== null ? (int) $agg->min_fan : $todayRecord->fan_min,
                    'fan_max' => $agg->max_fan !== null ? (int) $agg->max_fan : $todayRecord->fan_max,
                ]);
                $todayRecord->save();
            }

            // 2. Reconciliación del acumulado, sesión a sesión.
            if ($element->auto_calculate_history) {
                $this->reconcileHistorical($element);
            }
        });
    }

    /**
     * Rehace el acumulado de un elemento a partir de sus resúmenes diarios,
     * **una sesión cada vez**.
     *
     * Antes esto sumaba todos los días del elemento —de todas sus sesiones— y
     * metía el total en la última, así que el acumulado real del elemento
     * (la suma de sus sesiones) se duplicaba con cada reinicio del odómetro.
     * El parche fue saltarse en silencio cualquier elemento con más de una
     * sesión, que deja sin reconciliar justo el caso para el que existe la
     * tabla. Aquí cada sesión se reconcilia con los días que le tocan: desde
     * que se abrió hasta que se abrió la siguiente.
     */
    private function reconcileHistorical(HardwareEnergy $element): void
    {
        // Por elemento, no por elemento y dispositivo: el índice único es
        // (hardware_energy_id, session_index) y una fila con el dispositivo
        // desalineado quedaba invisible, así que se abría una sesión 1 nueva
        // encima de la que ya existía.
        /** @var Collection<int, HardwareEnergyHistorical> $sesiones */
        $sesiones = HardwareEnergyHistorical::query()
            ->where('hardware_energy_id', $element->id)
            ->orderBy('session_index')
            ->get();

        if ($sesiones->isEmpty()) {
            $sesiones = HardwareEnergyHistorical::query()->newModelInstance()->newCollection([
                new HardwareEnergyHistorical([
                    'hardware_device_id' => $element->hardware_device_id,
                    'hardware_energy_id' => $element->id,
                    'session_index' => 1,
                ]),
            ]);
        }

        foreach ($sesiones as $indice => $sesion) {
            $siguiente = $sesiones->get($indice + 1);

            $agregado = HardwareEnergyToday::query()
                ->forElement($element->id)
                // La primera sesión se queda con todo lo anterior a ella: son
                // los días que venían del esquema viejo, que no tienen por qué
                // ser posteriores a la fila que los resume.
                ->when(
                    $indice > 0 && $sesion->created_at !== null,
                    static fn ($q) => $q->where('date', '>=', $sesion->created_at->toDateString())
                )
                ->when(
                    $siguiente?->created_at !== null,
                    static fn ($q) => $q->where('date', '<', $siguiente->created_at->toDateString())
                )
                ->toBase()
                ->selectRaw('
                    COUNT(DISTINCT date) as days_operating,
                    COALESCE(SUM(readings_count), 0) as total_readings,
                    COALESCE(SUM(energy_wh), 0) as sum_wh,
                    COALESCE(SUM(energy_ah), 0) as sum_ah,
                    MIN(voltage_min) as min_voltage,
                    MAX(voltage_max) as max_voltage,
                    MIN(amperage_min) as min_amperage,
                    MAX(amperage_max) as max_amperage,
                    MIN(power_min) as min_power,
                    MAX(power_max) as max_power,
                    MIN(temperature_min) as min_temperature,
                    MAX(temperature_max) as max_temperature,
                    MIN(battery_min) as min_battery_voltage,
                    MAX(battery_max) as max_battery_voltage,
                    MIN(fan_min) as min_fan,
                    MAX(fan_max) as max_fan
                ')
                ->first();

            $dias = (int) ($agregado->days_operating ?? 0);

            if ($agregado === null || $dias === 0) {
                continue;
            }

            // **Sólo se baja un acumulado si los resúmenes diarios cubren toda
            // su historia.** El histórico arrastra años que llegaron del
            // esquema viejo sin un `hardware_energy_today` por día: sobrescribir
            // con la suma de los días que sí hay borraba lo demás. Un elemento
            // con 762 días y 14.000 Wh acumulados se quedaba en 1 día y 36 Wh.
            $cubreTodo = $dias >= (int) ($sesion->days_operating ?? 0);

            // Una magnitud que lleva el odómetro del aparato no se recalcula
            // desde nuestras lecturas: su total es el suyo y cubre tiempo que
            // nosotros no hemos medido. Se mira **magnitud a magnitud** porque
            // un aparato puede traer odómetro de una y no de la otra: el Renogy
            // Rover manda los amperios-hora de la batería pero no sus
            // vatios-hora, que sí hay que calcular.
            $rehacerWh = $sesion->energy_wh_source !== HardwareEnergyHistorical::SOURCE_DEVICE
                && ($cubreTodo || $this->option('rebuild'));
            $rehacerAh = $sesion->energy_ah_source !== HardwareEnergyHistorical::SOURCE_DEVICE
                && ($cubreTodo || $this->option('rebuild'));

            $algoDelAparato = $sesion->energy_wh_source === HardwareEnergyHistorical::SOURCE_DEVICE
                || $sesion->energy_ah_source === HardwareEnergyHistorical::SOURCE_DEVICE;

            if (! $algoDelAparato && ! $cubreTodo && ! $this->option('rebuild')) {
                $this->warn(sprintf(
                    'Elemento %d (sesión %d): los resúmenes diarios cubren %d de %d días; '
                    .'se conserva el acumulado y sólo se amplía. Usa --rebuild para rehacerlo igualmente.',
                    $element->id,
                    (int) $sesion->session_index,
                    $dias,
                    (int) $sesion->days_operating
                ));
            }

            // Los días y el recuento de lecturas describen la sesión entera, no
            // una magnitud. En cuanto **alguna** viene del odómetro del aparato,
            // la sesión cubre tiempo que nosotros no hemos medido: ahí sólo se
            // amplían, nunca se sustituyen por lo que digan nuestros resúmenes.
            $rehacerMetadatos = ! $algoDelAparato && ($cubreTodo || $this->option('rebuild'));

            $sesion->days_operating = $rehacerMetadatos
                ? $dias
                : max((int) $sesion->days_operating, $dias);
            $sesion->readings_count = $rehacerMetadatos
                ? (int) ($agregado->total_readings ?? 0)
                : max((int) $sesion->readings_count, (int) ($agregado->total_readings ?? 0));
            // Una magnitud de odómetro no se toca: ni se rehace ni se amplía.
            // Ampliarla con la suma de nuestros deltas sería mezclar las dos
            // fuentes, que es justo lo que estas columnas existen para impedir.
            if ($sesion->energy_wh_source !== HardwareEnergyHistorical::SOURCE_DEVICE) {
                $sesion->energy_wh = $rehacerWh
                    ? (float) ($agregado->sum_wh ?? 0)
                    : max((float) $sesion->energy_wh, (float) ($agregado->sum_wh ?? 0));
            }

            if ($sesion->energy_ah_source !== HardwareEnergyHistorical::SOURCE_DEVICE) {
                $sesion->energy_ah = $rehacerAh
                    ? (float) ($agregado->sum_ah ?? 0)
                    : max((float) $sesion->energy_ah, (float) ($agregado->sum_ah ?? 0));
            }

            // Los extremos sí se rehacen siempre: salen enteros de los
            // resúmenes del tramo y no acumulan nada de antes.
            $sesion->voltage_min = $agregado->min_voltage !== null ? (float) $agregado->min_voltage : $sesion->voltage_min;
            $sesion->voltage_max = $agregado->max_voltage !== null ? (float) $agregado->max_voltage : $sesion->voltage_max;
            $sesion->amperage_min = $agregado->min_amperage !== null ? (float) $agregado->min_amperage : $sesion->amperage_min;
            $sesion->amperage_max = $agregado->max_amperage !== null ? (float) $agregado->max_amperage : $sesion->amperage_max;
            $sesion->power_min = $agregado->min_power !== null ? (float) $agregado->min_power : $sesion->power_min;
            $sesion->power_max = $agregado->max_power !== null ? (float) $agregado->max_power : $sesion->power_max;
            $sesion->temperature_min = $agregado->min_temperature !== null ? (float) $agregado->min_temperature : $sesion->temperature_min;
            $sesion->temperature_max = $agregado->max_temperature !== null ? (float) $agregado->max_temperature : $sesion->temperature_max;
            $sesion->battery_min = $agregado->min_battery_voltage !== null ? (float) $agregado->min_battery_voltage : $sesion->battery_min;
            $sesion->battery_max = $agregado->max_battery_voltage !== null ? (float) $agregado->max_battery_voltage : $sesion->battery_max;
            $sesion->fan_min = $agregado->min_fan !== null ? (int) $agregado->min_fan : $sesion->fan_min;
            $sesion->fan_max = $agregado->max_fan !== null ? (int) $agregado->max_fan : $sesion->fan_max;

            // El dispositivo de la fila es el que el catálogo dice que mide el
            // elemento; si venía desalineado se corrige aquí.
            $sesion->hardware_device_id = $element->hardware_device_id;

            $sesion->save();
        }
    }
}
