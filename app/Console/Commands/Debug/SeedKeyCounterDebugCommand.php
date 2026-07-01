<?php

declare(strict_types=1);

namespace App\Console\Commands\Debug;

use App\Console\Commands\Debug\Concerns\ResolvesDebugDefaults;
use App\Models\KeyCounter\Keyboard;
use App\Models\KeyCounter\Mouse;
use Carbon\Carbon;
use Illuminate\Console\Command;

/**
 * Comando de debug para insertar registros de prueba en KeyCounter.
 * NO usar en producción. Solo para desarrollo/depuración manual.
 */
class SeedKeyCounterDebugCommand extends Command
{
    use ResolvesDebugDefaults;

    protected $signature = 'debug:seed-keycounter
        {--count=50 : Número de registros por tipo}
        {--days=7 : Número de días distintos (hacia atrás) sobre los que repartir los registros}';

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
        $days = max(1, (int) $this->option('days'));
        $now = Carbon::now();

        $this->info("Insertando {$count} registros de Keyboard repartidos en {$days} días...");

        for ($i = 0; $i < $count; $i++) {
            $startAt = $this->randomMomentInPastDays($now, $days, $i);
            $duration = fake()->numberBetween(30, 1800);
            $endAt = $startAt->copy()->addSeconds($duration);

            // `created_at` no está en $fillable del modelo, así que se usa forceFill()
            // para poder fijarlo manualmente (create() lo ignoraría y Eloquent lo
            // sobreescribiría con la fecha actual al guardar).
            (new Keyboard)->forceFill([
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
                'updated_at' => $startAt,
            ])->save();
        }

        $this->info("Insertando {$count} registros de Mouse repartidos en {$days} días...");

        for ($i = 0; $i < $count; $i++) {
            $startAt = $this->randomMomentInPastDays($now, $days, $i);
            $duration = fake()->numberBetween(30, 1800);
            $endAt = $startAt->copy()->addSeconds($duration);

            (new Mouse)->forceFill([
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
                'updated_at' => $startAt,
            ])->save();
        }

        $this->info("✅ {$count} registros de Keyboard y {$count} de Mouse insertados.");

        return self::SUCCESS;
    }

    /**
     * Genera una fecha/hora aleatoria dentro de uno de los últimos $days días.
     *
     * Reparte los registros de forma cíclica entre los días disponibles (día 0 =
     * hoy, día 1 = ayer, ...) para que cada día del rango reciba registros,
     * evitando que la gráfica del frontend (que agrupa por DATE(created_at))
     * muestre un único punto.
     */
    private function randomMomentInPastDays(Carbon $now, int $days, int $index): Carbon
    {
        $dayOffset = $index % $days;

        return $now->copy()
            ->subDays($dayOffset)
            ->startOfDay()
            ->addSeconds(fake()->numberBetween(8 * 3600, 22 * 3600));
    }
}
