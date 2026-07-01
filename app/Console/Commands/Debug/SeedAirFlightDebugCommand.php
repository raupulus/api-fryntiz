<?php

declare(strict_types=1);

namespace App\Console\Commands\Debug;

use App\Console\Commands\Debug\Concerns\ResolvesDebugDefaults;
use App\Models\AirFlight\AirFlightAirPlane;
use App\Models\AirFlight\AirFlightRoute;
use Carbon\Carbon;
use Illuminate\Console\Command;

/**
 * Comando de debug para insertar aviones y trayectorias de vuelo de ejemplo.
 * NO usar en producción. Solo para desarrollo/depuración manual.
 */
class SeedAirFlightDebugCommand extends Command
{
    use ResolvesDebugDefaults;

    protected $signature = 'debug:seed-airflight {--planes=10 : Número de aviones} {--routes=100 : Puntos de ruta a repartir entre los aviones (trayectoria)}';

    protected $description = 'Inserta aviones y trayectorias de vuelo realistas para debug (solo desarrollo)';

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

        $planesCount = max(1, (int) $this->option('planes'));
        $routesCount = (int) $this->option('routes');
        $now = Carbon::now();

        $this->info("Insertando {$planesCount} aviones...");

        $planes = [];
        for ($i = 0; $i < $planesCount; $i++) {
            $planes[] = AirFlightAirPlane::create([
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
                // Se recalculan al final a partir de la trayectoria real generada.
                'seen_first_at' => $now,
                'seen_last_at' => $now,
                'route_last_at' => $now,
            ]);
        }

        $this->info("Insertando trayectorias ({$routesCount} puntos repartidos entre {$planesCount} aviones)...");

        $pointsPerPlane = intdiv($routesCount, $planesCount);
        $remainder = $routesCount % $planesCount;
        $totalInserted = 0;

        if ($pointsPerPlane < 2) {
            $this->warn("⚠️  --routes ({$routesCount}) es demasiado bajo para --planes ({$planesCount}): a cada avión le tocará ~{$pointsPerPlane} punto(s).");
            $this->warn('   Con menos de 2 puntos por avión no hay forma de trazar una línea en el mapa (matemáticamente).');
            $this->warn('   Usa --routes bastante mayor que --planes, p.ej.: --planes=1 --routes=25');
        }

        foreach ($planes as $index => $plane) {
            $points = $pointsPerPlane + ($index < $remainder ? 1 : 0);

            if ($points < 1) {
                continue;
            }

            $totalInserted += $this->seedPlaneTrajectory($plane, $points, $userId, $hardwareDeviceId, $now);
        }

        // seen_first_at/seen_last_at/route_last_at a partir de la trayectoria real insertada.
        foreach ($planes as $plane) {
            $first = $plane->routes()->min('seen_at');
            $last = $plane->routes()->max('seen_at');

            if ($first && $last) {
                $plane->update([
                    'seen_first_at' => $first,
                    'seen_last_at' => $last,
                    'route_last_at' => $last,
                ]);
            }
        }

        $this->info("✅ {$planesCount} aviones y {$totalInserted} puntos de ruta insertados (trayectorias con rumbo coherente).");

        return self::SUCCESS;
    }

    /**
     * Genera una trayectoria coherente para un avión: cada punto avanza desde el
     * anterior según su rumbo y velocidad (fórmula de destino geodésico), en vez
     * de puntos sueltos con coordenadas aleatorias sin relación entre sí. Así el
     * mapa puede trazar una línea real con el recorrido del avión.
     */
    private function seedPlaneTrajectory(
        AirFlightAirPlane $plane,
        int $points,
        int $userId,
        int $hardwareDeviceId,
        Carbon $now
    ): int {
        $intervalSeconds = fake()->numberBetween(6, 15);

        // Posición y rumbo iniciales, cerca de Chipiona (36.7417, -6.4376).
        $lat = fake()->randomFloat(6, 36.55, 36.95);
        $lon = fake()->randomFloat(6, -6.65, -6.15);
        $track = (float) fake()->numberBetween(0, 359);
        $speed = (float) fake()->numberBetween(50, 260); // m/s
        $altitude = (float) fake()->numberBetween(300, 11000); // metros
        $vertRatePerStep = fake()->randomElement([0.0, fake()->randomFloat(1, 2, 8), fake()->randomFloat(1, -8, -2)]);
        $squawk = fake()->randomElement([null, '7000', (string) fake()->numberBetween(1000, 9999)]);
        $flight = strtoupper(fake()->bothify('???####'));
        $messages = fake()->numberBetween(20, 150);

        // El punto más reciente de la trayectoria queda cerca de "ahora".
        $endTime = $now->copy()->subSeconds(fake()->numberBetween(0, 90));

        for ($i = 0; $i < $points; $i++) {
            $seenAt = $endTime->copy()->subSeconds(($points - 1 - $i) * $intervalSeconds);

            if ($i > 0) {
                // Pequeño zigzag realista (ruido de ADS-B), no un giro brusco.
                $track = fmod($track + fake()->randomFloat(1, -4, 4) + 360, 360);
                $speed = max(20, $speed + fake()->randomFloat(1, -5, 5));
                $altitude = max(0, $altitude + $vertRatePerStep * $intervalSeconds);

                [$lat, $lon] = $this->destinationPoint($lat, $lon, $track, $speed * $intervalSeconds);
            }

            AirFlightRoute::create([
                'airplane_id' => $plane->id,
                'hardware_device_id' => $hardwareDeviceId,
                'user_id' => $userId,
                'squawk' => $squawk,
                'flight' => $flight,
                'lat' => $lat,
                'lon' => $lon,
                'altitude' => $altitude,
                'vert_rate' => $vertRatePerStep,
                'track' => (int) round($track),
                'speed' => $speed,
                'seen_at' => $seenAt,
                'messages' => $messages + $i * fake()->numberBetween(1, 5),
                'rssi' => fake()->randomFloat(1, -30, -5),
                'emergency' => null,
                'created_at' => $seenAt,
                'updated_at' => $seenAt,
            ]);
        }

        return $points;
    }

    /**
     * Punto destino dado un origen, rumbo (grados) y distancia (metros),
     * mediante la fórmula de destino geodésico esférico (haversine inverso).
     *
     * @return array{0: float, 1: float} [lat, lon]
     */
    private function destinationPoint(float $lat, float $lon, float $bearingDeg, float $distanceMeters): array
    {
        $earthRadius = 6371000.0;
        $angularDistance = $distanceMeters / $earthRadius;
        $bearing = deg2rad($bearingDeg);

        $lat1 = deg2rad($lat);
        $lon1 = deg2rad($lon);

        $lat2 = asin(sin($lat1) * cos($angularDistance) + cos($lat1) * sin($angularDistance) * cos($bearing));
        $lon2 = $lon1 + atan2(
            sin($bearing) * sin($angularDistance) * cos($lat1),
            cos($angularDistance) - sin($lat1) * sin($lat2)
        );

        return [round(rad2deg($lat2), 6), round(rad2deg($lon2), 6)];
    }
}
