<?php

namespace App\Console\Commands\Debug;

use App\Models\Hardware\HardwareDevice;
use App\Models\Hardware\HardwarePowerGenerator;
use App\Models\Hardware\HardwarePowerLoad;
use Carbon\Carbon;
use Illuminate\Console\Command;
use App\Console\Commands\Debug\Concerns\ResolvesDebugDefaults;

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
                'name' => ($deviceNames[$i % count($deviceNames)]) . ' #' . ($i + 1),
                'name_friendly' => 'Debug Device ' . ($i + 1),
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
        $this->info("✅ {$devicesCount} dispositivos con {$recordsCount} registros cada uno insertados.");

        return self::SUCCESS;
    }
}
