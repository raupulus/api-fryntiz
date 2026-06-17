<?php

declare(strict_types=1);

namespace App\Console\Commands\Debug;

use App\Console\Commands\Debug\Concerns\ResolvesDebugDefaults;
use App\Models\AirFlight\AirFlightAirPlane;
use App\Models\AirFlight\AirFlightRoute;
use Carbon\Carbon;
use Illuminate\Console\Command;

/**
 * Comando de debug para insertar aviones y registros de vuelo.
 * NO usar en producción. Solo para desarrollo/depuración manual.
 */
class SeedAirFlightDebugCommand extends Command
{
    use ResolvesDebugDefaults;

    protected $signature = 'debug:seed-airflight {--planes=10 : Número de aviones} {--routes=100 : Número de registros de ruta}';

    protected $description = 'Inserta aviones y registros de vuelo para debug (solo desarrollo)';

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

        $planesCount = (int) $this->option('planes');
        $routesCount = (int) $this->option('routes');
        $now = Carbon::now();

        $this->info("Insertando {$planesCount} aviones...");

        $planes = [];
        for ($i = 0; $i < $planesCount; $i++) {
            $plane = AirFlightAirPlane::create([
                'user_id' => $userId,
                'hardware_device_id' => $hardwareDeviceId,
                'icao' => strtoupper(fake()->bothify('######')),
                'country' => fake()->randomElement(['Spain', 'France', 'UK', 'Germany', 'USA', 'Morocco', 'Portugal', 'Italy', 'Netherlands']),
                'category' => fake()->randomElement(['A1', 'A2', 'A3', 'A5', 'B2', null]),
                'flag' => fake()->randomElement([
                    'https://flagcdn.com/w40/es.png',
                    'https://flagcdn.com/w40/fr.png',
                    'https://flagcdn.com/w40/gb.png',
                    'https://flagcdn.com/w40/de.png',
                    'https://flagcdn.com/w40/us.png',
                ]),
                'seen_first_at' => $now->copy()->subHours(fake()->numberBetween(1, 48)),
                'seen_last_at' => $now->copy()->subMinutes(fake()->numberBetween(1, 30)),
                'route_last_at' => $now->copy()->subMinutes(fake()->numberBetween(1, 30)),
            ]);
            $planes[] = $plane;
        }

        $this->info("Insertando {$routesCount} registros de ruta...");

        // Coordenadas base: Chipiona (36.7417, -6.4376)
        for ($i = 0; $i < $routesCount; $i++) {
            $seenAt = $now->copy()->subMinutes($routesCount - $i);
            $plane = $planes[array_rand($planes)];

            AirFlightRoute::create([
                'airplane_id' => $plane->id,
                'hardware_device_id' => $hardwareDeviceId,
                'user_id' => $userId,
                'squawk' => fake()->randomElement([null, '7000', '7500', '7600', '7700', (string) fake()->numberBetween(1000, 9999)]),
                'flight' => strtoupper(fake()->bothify('???####')),
                'lat' => fake()->randomFloat(6, 36.4, 37.1),
                'lon' => fake()->randomFloat(6, -6.8, -6.0),
                'altitude' => fake()->numberBetween(150, 12000),
                'vert_rate' => fake()->numberBetween(-2000, 2000),
                'track' => fake()->numberBetween(0, 360),
                'speed' => fake()->numberBetween(50, 500),
                'seen_at' => $seenAt,
                'messages' => fake()->numberBetween(1, 500),
                'rssi' => fake()->randomFloat(1, -30, -5),
                'emergency' => null,
                'created_at' => $seenAt,
                'updated_at' => $seenAt,
            ]);
        }

        // Re-calcular route_last_at por avión basado en sus rutas reales (fix_10 / fase 02).
        foreach ($planes as $plane) {
            $last = $plane->routes()->max('seen_at');
            if ($last) {
                $plane->update(['route_last_at' => $last]);
            }
        }

        $this->info("✅ {$planesCount} aviones y {$routesCount} rutas insertadas.");

        return self::SUCCESS;
    }
}
