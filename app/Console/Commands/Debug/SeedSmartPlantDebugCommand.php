<?php

namespace App\Console\Commands\Debug;

use App\Models\SmartPlant\SmartPlantPlant;
use App\Models\SmartPlant\SmartPlantRegister;
use Carbon\Carbon;
use Illuminate\Console\Command;

/**
 * Comando de debug para insertar plantas y registros de sensores.
 * NO usar en producción. Solo para desarrollo/depuración manual.
 */
class SeedSmartPlantDebugCommand extends Command
{
    protected $signature = 'debug:seed-smartplant {--plants=5 : Número de plantas} {--registers=50 : Número de registros}';

    protected $description = 'Inserta plantas y registros de sensores para debug (solo desarrollo)';

    public function handle(): int
    {
        $plantsCount = (int) $this->option('plants');
        $registersCount = (int) $this->option('registers');
        $now = Carbon::now();

        $plantNames = ['Albahaca', 'Tomate Cherry', 'Menta', 'Lavanda', 'Romero', 'Cactus', 'Aloe Vera', 'Perejil'];

        $this->info("Insertando {$plantsCount} plantas...");

        $plants = [];
        for ($i = 0; $i < $plantsCount; $i++) {
            $plant = SmartPlantPlant::create([
                'user_id' => 1,
                'hardware_device_id' => 1,
                'name' => $plantNames[$i % count($plantNames)] . ' #' . ($i + 1),
                'description' => 'Planta de debug generada automáticamente',
                'start_at' => $now->copy()->subDays(fake()->numberBetween(30, 365)),
                'created_at' => $now,
            ]);
            $plants[] = $plant;
        }

        $this->info("Insertando {$registersCount} registros...");

        for ($i = 0; $i < $registersCount; $i++) {
            $timestamp = $now->copy()->subMinutes(($registersCount - $i) * 5);
            $plant = $plants[array_rand($plants)];

            SmartPlantRegister::create([
                'plant_id' => $plant->id,
                'hardware_device_id' => 1,
                'uv' => fake()->randomFloat(2, 0, 15),
                'pressure' => fake()->randomFloat(2, 990, 1040),
                'temperature' => fake()->randomFloat(2, 15, 38),
                'humidity' => fake()->randomFloat(2, 30, 90),
                'soil_humidity' => fake()->randomFloat(2, 10, 85),
                'soil_humidity_raw' => fake()->numberBetween(200, 800),
                'full_water_tank' => fake()->boolean(80),
                'waterpump_enabled' => fake()->boolean(20),
                'vaporizer_enabled' => fake()->boolean(10),
                'created_at' => $timestamp,
            ]);
        }

        $this->info("✅ {$plantsCount} plantas y {$registersCount} registros insertados.");

        return self::SUCCESS;
    }
}
