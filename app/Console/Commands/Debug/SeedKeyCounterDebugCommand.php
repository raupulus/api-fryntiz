<?php

namespace App\Console\Commands\Debug;

use App\Models\KeyCounter\Keyboard;
use App\Models\KeyCounter\Mouse;
use Carbon\Carbon;
use Illuminate\Console\Command;
use App\Console\Commands\Debug\Concerns\ResolvesDebugDefaults;

/**
 * Comando de debug para insertar registros de prueba en KeyCounter.
 * NO usar en producción. Solo para desarrollo/depuración manual.
 */
class SeedKeyCounterDebugCommand extends Command
{
    use ResolvesDebugDefaults;

    protected $signature = 'debug:seed-keycounter {--count=50 : Número de registros por tipo}';

    protected $description = 'Inserta registros de debug para Keyboard y Mouse (solo desarrollo)';

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

        $count = (int) $this->option('count');
        $now = Carbon::now();
        $weekdays = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];

        $this->info("Insertando {$count} registros de Keyboard...");

        for ($i = 0; $i < $count; $i++) {
            $startAt = $now->copy()->subMinutes(($count - $i) * 2);
            $duration = fake()->numberBetween(30, 1800);
            $endAt = $startAt->copy()->addSeconds($duration);

            Keyboard::create([
                'user_id' => $userId,
                'hardware_device_id' => $hardwareDeviceId,
                'start_at' => $startAt,
                'end_at' => $endAt,
                'duration' => $duration,
                'pulsations' => fake()->numberBetween(50, 5000),
                'pulsations_special_keys' => fake()->numberBetween(5, 500),
                'pulsation_average' => fake()->randomFloat(2, 10, 200),
                'score' => fake()->numberBetween(1, 100),
                'weekday' => $startAt->dayOfWeek,
                'created_at' => $startAt,
            ]);
        }

        $this->info("Insertando {$count} registros de Mouse...");

        for ($i = 0; $i < $count; $i++) {
            $startAt = $now->copy()->subMinutes(($count - $i) * 2);
            $duration = fake()->numberBetween(30, 1800);
            $endAt = $startAt->copy()->addSeconds($duration);

            Mouse::create([
                'user_id' => $userId,
                'hardware_device_id' => $hardwareDeviceId,
                'start_at' => $startAt,
                'end_at' => $endAt,
                'duration' => $duration,
                'clicks_left' => fake()->numberBetween(10, 1500),
                'clicks_right' => fake()->numberBetween(5, 300),
                'clicks_middle' => fake()->numberBetween(0, 50),
                'total_clicks' => fake()->numberBetween(20, 2000),
                'clicks_average' => fake()->randomFloat(2, 1, 80),
                'weekday' => $startAt->dayOfWeek,
                'created_at' => $startAt,
            ]);
        }

        $this->info("✅ {$count} registros de Keyboard y {$count} de Mouse insertados.");

        return self::SUCCESS;
    }
}
