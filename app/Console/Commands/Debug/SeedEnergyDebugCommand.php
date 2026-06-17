<?php

namespace App\Console\Commands\Debug;

use App\Console\Commands\Debug\Concerns\ResolvesDebugDefaults;
use App\Models\Hardware\HardwareDevice;
use App\Models\Hardware\HardwareEnergy;
use App\Models\Hardware\HardwarePowerGenerator;
use App\Models\Hardware\HardwarePowerGeneratorHistorical;
use App\Models\Hardware\HardwarePowerGeneratorToday;
use App\Models\Hardware\HardwarePowerLoad;
use App\Models\Hardware\HardwarePowerLoadHistorical;
use App\Models\Hardware\HardwarePowerLoadToday;
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
            $device = HardwareDevice::create([
                'user_id' => $userId,
                'hardware_type_id' => 1,
                'name' => ($deviceNames[$i % count($deviceNames)]).' #'.($i + 1),
                'name_friendly' => 'Debug Device '.($i + 1),
                'description' => 'Dispositivo de debug para pruebas de energía',
                'created_at' => $now,
            ]);
            $devices[] = $device;
        }

        $this->info("Insertando {$recordsCount} registros de generación y consumo por dispositivo...");

        $bar = $this->output->createProgressBar($recordsCount * $devicesCount);
        $bar->start();

        foreach ($devices as $device) {
            for ($i = 0; $i < $recordsCount; $i++) {
                $readAt = $now->copy()->subMinutes(($recordsCount - $i) * 15);

                // Generador
                HardwarePowerGenerator::create([
                    'hardware_device_id' => $device->id,
                    'battery_voltage' => fake()->randomFloat(2, 11.5, 14.8),
                    'battery_temperature' => fake()->randomFloat(1, 20, 55),
                    'battery_percentage' => fake()->numberBetween(10, 100),
                    'charging_status' => fake()->numberBetween(0, 3),
                    'charging_status_label' => fake()->randomElement(['Charging', 'Float', 'Bulk', 'Off']),
                    'amperage' => fake()->randomFloat(2, 0, 5),
                    'voltage' => fake()->randomFloat(2, 12, 24),
                    'power' => fake()->randomFloat(2, 0, 100),
                    'light_status' => fake()->boolean(70),
                    'light_brightness' => fake()->numberBetween(0, 100),
                    'read_at' => $readAt,
                    'created_at' => $readAt,
                ]);

                // Consumo
                HardwarePowerLoad::create([
                    'hardware_device_id' => $device->id,
                    'fan' => fake()->boolean(30),
                    'temperature' => fake()->randomFloat(1, 25, 50),
                    'voltage' => fake()->randomFloat(2, 12, 24),
                    'amperage' => fake()->randomFloat(2, 0, 3),
                    'power' => fake()->randomFloat(2, 0, 60),
                    'battery_voltage' => fake()->randomFloat(2, 11.5, 14.8),
                    'battery_percentage' => fake()->numberBetween(10, 100),
                    'read_at' => $readAt,
                    'created_at' => $readAt,
                ]);

                $bar->advance();
            }
        }

        $bar->finish();
        $this->newLine();

        // === Agregados *_today y *_historical (fix_10 / fase 02) ===
        // Sin esto la vista /hardware/energy queda a 0.
        $this->info('Generando agregados today + historical...');
        $today = $now->copy()->startOfDay();
        foreach ($devices as $device) {
            HardwarePowerLoadToday::updateOrCreate(
                ['hardware_device_id' => $device->id, 'date' => $today->toDateString()],
                [
                    'fan_min' => 0, 'fan_max' => 1,
                    'temperature_min' => 25, 'temperature_max' => 48,
                    'voltage_min' => 11.5, 'voltage_max' => 14.8,
                    'battery_min' => 11.5, 'battery_max' => 14.8,
                    'battery_percentage_min' => 20, 'battery_percentage_max' => 100,
                    'read_at' => $now,
                ]
            );

            HardwarePowerGeneratorToday::updateOrCreate(
                ['hardware_device_id' => $device->id, 'date' => $today->toDateString()],
                [
                    'temperature_min' => 22, 'temperature_max' => 50,
                    'voltage_min' => 12.0, 'voltage_max' => 24.0,
                    'battery_min' => 11.5, 'battery_max' => 14.8,
                    'battery_percentage_min' => 20, 'battery_percentage_max' => 100,
                    'amperage_max' => 4.8,
                    'power' => 45.0, 'power_max' => 95.0,
                    'read_at' => $now,
                ]
            );

            for ($d = 0; $d < 30; $d++) {
                $day = $now->copy()->subDays($d);
                HardwarePowerLoadHistorical::updateOrCreate(
                    ['hardware_device_id' => $device->id, 'read_at' => $day],
                    [
                        'fan_min' => 0, 'fan_max' => 1,
                        'temperature_min' => fake()->randomFloat(2, 22, 28),
                        'temperature_max' => fake()->randomFloat(2, 40, 50),
                        'voltage_min' => fake()->randomFloat(2, 11, 12),
                        'voltage_max' => fake()->randomFloat(2, 13, 15),
                        'battery_min' => fake()->randomFloat(2, 11, 12),
                        'battery_max' => fake()->randomFloat(2, 13, 15),
                        'amperage_min' => fake()->randomFloat(2, 0, 0.5),
                        'amperage_max' => fake()->randomFloat(2, 2, 3),
                        'amperage' => fake()->randomFloat(2, 0.5, 2),
                        'power_min' => fake()->randomFloat(2, 0, 5),
                        'power_max' => fake()->randomFloat(2, 50, 60),
                        'power' => fake()->randomFloat(2, 10, 40),
                        'days_operating' => $d + 1,
                    ]
                );

                HardwarePowerGeneratorHistorical::updateOrCreate(
                    ['hardware_device_id' => $device->id, 'read_at' => $day],
                    [
                        'days_operating' => $d + 1,
                        'number_battery_over_discharges' => fake()->numberBetween(0, 3),
                        'number_battery_full_charges' => fake()->numberBetween(0, 5),
                        'power' => fake()->randomFloat(2, 10, 90),
                    ]
                );
            }
        }

        // === Asociaciones en hardware_energy ===
        // Define qué dispositivo monitoriza a cuál (necesario para las vistas de energía).
        $this->info('Asociando dispositivos en hardware_energy...');

        foreach ($devices as $index => $device) {
            // Caso 1: Auto-monitorización — cada dispositivo se monitoriza a sí mismo.
            HardwareEnergy::firstOrCreate(
                [
                    'hardware_device_id' => $device->id,
                    'hardware_device_monitorized_id' => $device->id,
                ],
                [
                    'is_generator' => $index % 2 === 0,
                    'sensor_position' => 0,
                ]
            );
        }

        // Caso 2: Monitorización cruzada — el primer dispositivo monitoriza a los demás.
        if (count($devices) >= 2) {
            $monitorDevice = $devices[0];

            for ($i = 1; $i < count($devices); $i++) {
                HardwareEnergy::firstOrCreate(
                    [
                        'hardware_device_id' => $monitorDevice->id,
                        'hardware_device_monitorized_id' => $devices[$i]->id,
                    ],
                    [
                        'is_generator' => $i % 2 === 0,
                        'sensor_position' => $i,
                    ]
                );
            }

            // Caso 3: El segundo dispositivo también monitoriza al primero (bidireccional).
            HardwareEnergy::firstOrCreate(
                [
                    'hardware_device_id' => $devices[1]->id,
                    'hardware_device_monitorized_id' => $devices[0]->id,
                ],
                [
                    'is_generator' => true,
                    'sensor_position' => 0,
                ]
            );
        }

        $this->info("✅ {$devicesCount} dispositivos con {$recordsCount} registros cada uno + agregados today/historical + asociaciones hardware_energy insertados.");

        return self::SUCCESS;
    }
}
