<?php

declare(strict_types=1);

namespace App\Console\Commands\Energy;

use App\Models\Hardware\HardwareEnergy;
use App\Models\Hardware\HardwareEnergyHistorical;
use App\Models\Hardware\HardwareEnergyReading;
use App\Models\Hardware\HardwareEnergyToday;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Consolida y recalcula los agregados diarios e históricos de energía para
 * elementos con recálculo automático (`auto_calculate_history = true`).
 *
 * Corre típicamente a las 00:05 para cerrar el día anterior con todas sus lecturas,
 * o bajo demanda para cualquier fecha/elemento específico.
 */
class AggregateDailyEnergyCommand extends Command
{
    protected $signature = 'energy:aggregate-daily
        {--date= : Fecha específica a consolidar en formato YYYY-MM-DD. Por defecto, ayer}
        {--today : Consolidar la fecha de hoy en curso}
        {--element= : ID específico de HardwareEnergy a consolidar}
        {--all : Forzar consolidación incluso en elementos con auto_calculate_history=false}';

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

        $startOfDay = Carbon::parse($date)->startOfDay();
        $endOfDay = Carbon::parse($date)->endOfDay();
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
            return Carbon::today()->toDateString();
        }

        $dateOption = $this->option('date');
        if (is_string($dateOption) && $dateOption !== '') {
            try {
                return Carbon::createFromFormat('Y-m-d', $dateOption)?->toDateString();
            } catch (\Exception) {
                return null;
            }
        }

        return Carbon::yesterday()->toDateString();
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
                /** @var HardwareEnergyToday $todayRecord */
                $todayRecord = HardwareEnergyToday::firstOrNew([
                    'hardware_device_id' => $element->hardware_device_id,
                    'hardware_energy_id' => $element->id,
                    'date' => $date,
                ]);

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

            // 2. Reconciliación de HardwareEnergyHistorical para este elemento
            /** @var \stdClass|null $historicalAgg */
            $historicalAgg = HardwareEnergyToday::query()
                ->forElement($element->id)
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

            $totalDays = (int) ($historicalAgg->days_operating ?? 0);
            if ($historicalAgg !== null && $totalDays > 0) {
                /** @var HardwareEnergyHistorical $historicalRecord */
                $historicalRecord = HardwareEnergyHistorical::query()
                    ->where('hardware_device_id', $element->hardware_device_id)
                    ->where('hardware_energy_id', $element->id)
                    ->orderByDesc('session_index')
                    ->first() ?? new HardwareEnergyHistorical([
                        'hardware_device_id' => $element->hardware_device_id,
                        'hardware_energy_id' => $element->id,
                        'session_index' => 1,
                    ]);

                $historicalRecord->days_operating = max((int) ($historicalRecord->days_operating ?? 0), $totalDays);
                $historicalRecord->readings_count = max((int) ($historicalRecord->readings_count ?? 0), (int) ($historicalAgg->total_readings ?? 0));
                $historicalRecord->energy_wh = max((float) ($historicalRecord->energy_wh ?? 0), (float) ($historicalAgg->sum_wh ?? 0));
                $historicalRecord->energy_ah = max((float) ($historicalRecord->energy_ah ?? 0), (float) ($historicalAgg->sum_ah ?? 0));

                if ($historicalAgg->min_voltage !== null) {
                    $historicalRecord->voltage_min = $historicalRecord->voltage_min !== null
                        ? min((float) $historicalRecord->voltage_min, (float) $historicalAgg->min_voltage)
                        : (float) $historicalAgg->min_voltage;
                }
                if ($historicalAgg->max_voltage !== null) {
                    $historicalRecord->voltage_max = $historicalRecord->voltage_max !== null
                        ? max((float) $historicalRecord->voltage_max, (float) $historicalAgg->max_voltage)
                        : (float) $historicalAgg->max_voltage;
                }

                if ($historicalAgg->min_amperage !== null) {
                    $historicalRecord->amperage_min = $historicalRecord->amperage_min !== null
                        ? min((float) $historicalRecord->amperage_min, (float) $historicalAgg->min_amperage)
                        : (float) $historicalAgg->min_amperage;
                }
                if ($historicalAgg->max_amperage !== null) {
                    $historicalRecord->amperage_max = $historicalRecord->amperage_max !== null
                        ? max((float) $historicalRecord->amperage_max, (float) $historicalAgg->max_amperage)
                        : (float) $historicalAgg->max_amperage;
                }

                if ($historicalAgg->min_power !== null) {
                    $historicalRecord->power_min = $historicalRecord->power_min !== null
                        ? min((float) $historicalRecord->power_min, (float) $historicalAgg->min_power)
                        : (float) $historicalAgg->min_power;
                }
                if ($historicalAgg->max_power !== null) {
                    $historicalRecord->power_max = $historicalRecord->power_max !== null
                        ? max((float) $historicalRecord->power_max, (float) $historicalAgg->max_power)
                        : (float) $historicalAgg->max_power;
                }

                if ($historicalAgg->min_temperature !== null) {
                    $historicalRecord->temperature_min = $historicalRecord->temperature_min !== null
                        ? min((float) $historicalRecord->temperature_min, (float) $historicalAgg->min_temperature)
                        : (float) $historicalAgg->min_temperature;
                }
                if ($historicalAgg->max_temperature !== null) {
                    $historicalRecord->temperature_max = $historicalRecord->temperature_max !== null
                        ? max((float) $historicalRecord->temperature_max, (float) $historicalAgg->max_temperature)
                        : (float) $historicalAgg->max_temperature;
                }

                if ($historicalAgg->min_battery_voltage !== null) {
                    $historicalRecord->battery_min = $historicalRecord->battery_min !== null
                        ? min((float) $historicalRecord->battery_min, (float) $historicalAgg->min_battery_voltage)
                        : (float) $historicalAgg->min_battery_voltage;
                }
                if ($historicalAgg->max_battery_voltage !== null) {
                    $historicalRecord->battery_max = $historicalRecord->battery_max !== null
                        ? max((float) $historicalRecord->battery_max, (float) $historicalAgg->max_battery_voltage)
                        : (float) $historicalAgg->max_battery_voltage;
                }

                if ($historicalAgg->min_fan !== null) {
                    $historicalRecord->fan_min = $historicalRecord->fan_min !== null
                        ? min((int) $historicalRecord->fan_min, (int) $historicalAgg->min_fan)
                        : (int) $historicalAgg->min_fan;
                }
                if ($historicalAgg->max_fan !== null) {
                    $historicalRecord->fan_max = $historicalRecord->fan_max !== null
                        ? max((int) $historicalRecord->fan_max, (int) $historicalAgg->max_fan)
                        : (int) $historicalAgg->max_fan;
                }

                $historicalRecord->save();
            }
        });
    }
}
