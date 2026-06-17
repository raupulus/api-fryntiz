<?php

declare(strict_types=1);

namespace App\Console\Commands\Debug;

use App\Console\Commands\Debug\Concerns\ResolvesDebugDefaults;
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
    use ResolvesDebugDefaults;

    protected $signature = 'debug:seed-smartplant {--plants=5 : Número de plantas} {--registers=50 : Número de registros}';

    protected $description = 'Inserta plantas y registros de sensores para debug (solo desarrollo)';

    public function handle(): int
    {
        if (! $this->guardEnvironment()) {
            return self::FAILURE;
        }

        $userId = $this->resolveUserId();
        if (! $userId) {
            return self::FAILURE;
        }

        $hardwareDeviceId = $this->resolveHardwareDeviceId();
        if (! $hardwareDeviceId) {
            return self::FAILURE;
        }

        $plantsCount = (int) $this->option('plants');
        $registersCount = (int) $this->option('registers');
        $now = Carbon::now();

        $plantNames = [
            'Albahaca', 'Tomate Cherry', 'Menta', 'Lavanda', 'Romero',
            'Cactus', 'Aloe Vera', 'Perejil', 'Orégano', 'Cilantro',
            'Pimiento', 'Fresa', 'Girasol', 'Rosa', 'Suculenta',
            'Helecho', 'Pothos', 'Monstera', 'Ficus', 'Bambú',
        ];

        $scientificNames = [
            'Ocimum basilicum', 'Solanum lycopersicum var. cerasiforme', 'Mentha spicata',
            'Lavandula angustifolia', 'Salvia rosmarinus', 'Cactaceae', 'Aloe barbadensis',
            'Petroselinum crispum', 'Origanum vulgare', 'Coriandrum sativum',
            'Capsicum annuum', 'Fragaria × ananassa', 'Helianthus annuus', 'Rosa gallica',
            'Echeveria elegans', 'Nephrolepis exaltata', 'Epipremnum aureum',
            'Monstera deliciosa', 'Ficus elastica', 'Bambusa vulgaris',
        ];

        $this->info("Insertando {$plantsCount} plantas...");

        $plants = [];
        for ($i = 0; $i < $plantsCount; $i++) {
            $plant = SmartPlantPlant::create([
                'user_id' => $userId,
                'name' => $plantNames[$i % count($plantNames)].' #'.($i + 1),
                'name_scientific' => $scientificNames[$i % count($scientificNames)],
                'description' => 'Planta de debug: '.$plantNames[$i % count($plantNames)],
                'details' => 'Detalles avanzados de la planta de debug',
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
                'hardware_device_id' => $hardwareDeviceId,
                'uv' => fake()->numberBetween(0, 15),
                'pressure' => fake()->randomFloat(2, 990, 1040),
                'temperature' => fake()->randomFloat(2, 15, 38),
                'humidity' => fake()->randomFloat(2, 30, 90),
                'soil_humidity' => fake()->numberBetween(10, 85),
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
