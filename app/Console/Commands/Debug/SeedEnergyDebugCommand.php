<?php

declare(strict_types=1);

namespace App\Console\Commands\Debug;

use App\Console\Commands\Debug\Concerns\ResolvesDebugDefaults;
use App\Models\Hardware\HardwareDevice;
use App\Models\Hardware\HardwareEnergy;
use App\Models\Hardware\HardwareEnergyHistorical;
use App\Models\Hardware\HardwareEnergyReading;
use App\Models\Hardware\HardwareEnergyToday;
use Carbon\Carbon;
use Illuminate\Console\Command;

/**
 * Comando de debug para insertar dispositivos y registros de energía.
 * NO usar en producción. Solo para desarrollo/depuración manual.
 */
class SeedEnergyDebugCommand extends Command
{
    use ResolvesDebugDefaults;

    protected $signature = 'debug:seed-energy {--devices=5 : Número de dispositivos} {--records=100 : Registros por dispositivo}';

    protected $description = 'Inserta dispositivos y registros de energía para debug (solo desarrollo)';

    public function handle(): int
    {
        if (! $this->guardEnvironment()) {
            return self::FAILURE;
        }

        $userId = $this->resolveUserId() ?? 1;

        $devicesCount = (int) $this->option('devices');
        $recordsCount = (int) $this->option('records');
        $now = Carbon::now();

        $this->info("Insertando {$devicesCount} dispositivos...");

        $deviceNames = ['Panel Solar Tejado', 'Panel Solar Jardín', 'Cargador USB Solar', 'Batería Principal', 'Inversor 12V'];
        $devices = [];

        for ($i = 0; $i < $devicesCount; $i++) {
            $name = ($deviceNames[$i % count($deviceNames)]).' #'.($i + 1);

            // # firstOrCreate evita duplicar dispositivos si el comando se ejecuta varias veces.
            $device = HardwareDevice::firstOrCreate(
                ['user_id' => $userId, 'name' => $name],
                [
                    'hardware_type_id' => 1,
                    'name_friendly' => 'Debug Device '.($i + 1),
                    'description' => 'Dispositivo de debug para pruebas de energía',
                    'created_at' => $now,
                ]
            );
            $devices[] = $device;
        }

        // === Asociaciones en hardware_energy ===
        $this->info('Asociando elementos en hardware_energy...');
        $elements = [];

        foreach ($devices as $index => $device) {
            $elements[] = HardwareEnergy::firstOrCreate(
                [
                    'hardware_device_id' => $device->id,
                    'hardware_device_monitorized_id' => $device->id,
                    'sensor_position' => 0,
                ],
                [
                    'role' => $index % 2 === 0
                        ? HardwareEnergy::ROLE_GENERATOR
                        : HardwareEnergy::ROLE_LOAD,
                    'sensor_position' => 0,
                    'is_active' => true,
                    'nominal_voltage' => 12.0,
                ]
            );
        }

        $this->info("Insertando {$recordsCount} lecturas unificadas de energía por dispositivo...");

        $bar = $this->output->createProgressBar($recordsCount * count($elements));
        $bar->start();

        foreach ($elements as $element) {
            for ($i = 0; $i < $recordsCount; $i++) {
                $readAt = $now->copy()->subMinutes(($recordsCount - $i) * 15);
                $isGenerator = $element->isGenerator();

                HardwareEnergyReading::create([
                    'hardware_device_id' => $element->hardware_device_id,
                    'hardware_energy_id' => $element->id,
                    'voltage' => fake()->randomFloat(2, 11.5, 14.8),
                    'amperage' => fake()->randomFloat(2, 0.1, $isGenerator ? 5.0 : 3.0),
                    'power' => fake()->randomFloat(2, 1.0, $isGenerator ? 100.0 : 50.0),
                    'energy_wh' => fake()->randomFloat(2, 5.0, 25.0),
                    'energy_ah' => fake()->randomFloat(2, 0.4, 2.0),
                    'delta_seconds' => 900,
                    'energy_source' => 'device',
                    'voltage_source' => 'measured',
                    'temperature' => fake()->randomFloat(1, 20.0, 50.0),
                    'created_at' => $readAt,
                    'updated_at' => $readAt,
                ]);

                $bar->advance();
            }
        }

        $bar->finish();
        $this->newLine();

        // === Agregados today + historical ===
        //
        // Un resumen **por día** durante 30 días y **un solo acumulado por
        // elemento**, que es la forma que tiene el esquema: `hardware_energy_today`
        // es único por (elemento, fecha) y `hardware_energy_historical` por
        // (elemento, sesión). Ninguna de las dos tiene `read_at`: la marca de
        // tiempo del resumen es su `date` y la del acumulado su `updated_at`.
        $this->info('Generando 30 días de resúmenes + un acumulado por elemento...');
        $dias = 30;

        foreach ($elements as $element) {
            $isGen = $element->isGenerator();
            $totalWh = 0.0;
            $totalAh = 0.0;
            $totalLecturas = 0;

            for ($d = 0; $d < $dias; $d++) {
                $fecha = $now->copy()->subDays($d)->format('Y-m-d');
                $whDia = fake()->randomFloat(2, 200, 1500);
                $ahDia = fake()->randomFloat(2, 15, 120);

                $totalWh += $whDia;
                $totalAh += $ahDia;
                $totalLecturas += 96;

                HardwareEnergyToday::updateOrCreate(
                    ['hardware_energy_id' => $element->id, 'date' => $fecha],
                    [
                        'hardware_device_id' => $element->hardware_device_id,
                        'voltage_min' => 11.5,
                        'voltage_max' => 14.8,
                        'amperage_min' => 0.1,
                        'amperage_max' => $isGen ? 5.0 : 3.0,
                        'power_min' => 1.0,
                        'power_max' => $isGen ? 100.0 : 50.0,
                        'temperature_min' => 20.0,
                        'temperature_max' => 50.0,
                        'energy_wh' => $whDia,
                        'energy_ah' => $ahDia,
                        'readings_count' => 96,
                    ]
                );
            }

            HardwareEnergyHistorical::updateOrCreate(
                ['hardware_energy_id' => $element->id, 'session_index' => 1],
                [
                    'hardware_device_id' => $element->hardware_device_id,
                    'days_operating' => $dias,
                    'energy_wh' => round($totalWh, 4),
                    'energy_ah' => round($totalAh, 4),
                    'voltage_min' => 11.0,
                    'voltage_max' => 15.0,
                    'amperage_min' => 0.0,
                    'amperage_max' => $isGen ? 5.0 : 3.0,
                    'power_min' => 0.0,
                    'power_max' => $isGen ? 100.0 : 50.0,
                    'temperature_min' => 20.0,
                    'temperature_max' => 50.0,
                    'readings_count' => $totalLecturas,
                    'energy_source' => HardwareEnergyHistorical::SOURCE_DERIVED,
                ]
            );
        }

        $this->info("✅ {$devicesCount} dispositivos con {$recordsCount} lecturas cada uno + {$dias} días de resúmenes + acumulado por elemento.");

        return self::SUCCESS;
    }
}
