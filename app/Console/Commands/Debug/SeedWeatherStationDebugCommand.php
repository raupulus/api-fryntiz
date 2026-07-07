<?php

declare(strict_types=1);

namespace App\Console\Commands\Debug;

use App\Console\Commands\Debug\Concerns\ResolvesDebugDefaults;
use App\Enums\HardwareLocationTypeEnum;
use App\Models\Hardware\HardwareDevice;
use App\Models\Hardware\HardwareType;
use App\Models\WeatherStation\AirQuality;
use App\Models\WeatherStation\Eco2;
use App\Models\WeatherStation\Humidity;
use App\Models\WeatherStation\Light;
use App\Models\WeatherStation\Lightning;
use App\Models\WeatherStation\MeteorologyResumeHistorical;
use App\Models\WeatherStation\MeteorologyResumeToday;
use App\Models\WeatherStation\MeteorologyUva;
use App\Models\WeatherStation\MeteorologyUvb;
use App\Models\WeatherStation\MeteorologyUvIndex;
use App\Models\WeatherStation\Pressure;
use App\Models\WeatherStation\Rain;
use App\Models\WeatherStation\Temperature;
use App\Models\WeatherStation\Tvoc;
use App\Models\WeatherStation\Wind;
use App\Models\WeatherStation\WindDirection;
use Carbon\Carbon;
use Database\Seeders\HardwareTypesSeeder;
use Illuminate\Console\Command;

/**
 * Comando de debug para insertar registros de prueba en WeatherStation.
 * NO usar en producción. Solo para desarrollo/depuración manual.
 *
 * Genera datos para varias estaciones de interior y exterior (con distintas
 * zonas) para poder probar la agrupación y el filtrado por estación. Cada
 * estación recibe rangos realistas según su ubicación (interior/exterior).
 */
class SeedWeatherStationDebugCommand extends Command
{
    use ResolvesDebugDefaults;

    protected $signature = 'debug:seed-weatherstation {--count=20 : Número de registros por sensor}';

    protected $description = 'Inserta registros de debug para varias estaciones (interior/exterior) de WeatherStation (solo desarrollo)';

    /**
     * Rangos de generación por tipo de ubicación. El interior evita valores de
     * intemperie (viento, lluvia, rayos, UV alto) y usa temperaturas/humedad
     * más templadas para que el contraste con el exterior sea evidente.
     */
    private const PROFILES = [
        'outdoor' => [
            'temperature' => [5, 42],
            'humidity' => [20, 98],
            'light_max' => 1000.0,
            'uv_index_max' => 11.0,
            'uva_max' => 500.0,
            'uvb_max' => 300.0,
            'has_wind' => true,
            'has_rain' => true,
            'has_lightning' => true,
        ],
        'indoor' => [
            'temperature' => [16, 28],
            'humidity' => [30, 70],
            'light_max' => 400.0,
            'uv_index_max' => 1.0,
            'uva_max' => 50.0,
            'uvb_max' => 30.0,
            'has_wind' => false,
            'has_rain' => false,
            'has_lightning' => false,
        ],
    ];

    public function handle(): int
    {
        if (! $this->guardEnvironment()) {
            return self::FAILURE;
        }

        $userId = $this->resolveUserId();
        if (! $userId) {
            return self::FAILURE;
        }

        $stations = $this->resolveWeatherStations($userId);
        $count = (int) $this->option('count');
        $now = Carbon::now();

        $this->info(sprintf('Insertando %d registros por sensor en %d estaciones...', $count, count($stations)));

        foreach ($stations as ['device' => $device, 'profile' => $profileKey]) {
            $profile = self::PROFILES[$profileKey];
            $label = HardwareLocationTypeEnum::from($profileKey)->label();

            $this->line(sprintf('  → %s [%s / %s]', $device->display_name, $label, $device->zone));

            $this->seedSensors($userId, $device->id, $profile, $count, $now);
            $this->seedResumes($userId, $device->id, $profile, $count, $now);
        }

        $this->info("✅ Datos insertados para {$count} lecturas por sensor en cada estación + resúmenes y UV.");

        return self::SUCCESS;
    }

    /**
     * Resuelve las estaciones meteorológicas a poblar. Si ya existen
     * dispositivos de tipo "Estación Meteorológica", los reutiliza; si no,
     * crea un conjunto de ejemplo: 2 de exterior (zonas distintas) y 1 interior.
     *
     * @return array<int, array{device: HardwareDevice, profile: string}>
     */
    private function resolveWeatherStations(int $userId): array
    {
        $typeId = $this->resolveWeatherStationTypeId();

        $existing = HardwareDevice::weatherStations()
            ->orderBy('id')
            ->get();

        if ($existing->isNotEmpty()) {
            return $existing->map(fn (HardwareDevice $device) => [
                'device' => $device,
                'profile' => $device->location_type->value,
            ])->all();
        }

        $definitions = [
            ['name_friendly' => 'Estación Azotea', 'location_type' => 'outdoor', 'zone' => 'Azotea'],
            ['name_friendly' => 'Estación Jardín', 'location_type' => 'outdoor', 'zone' => 'Jardín'],
            ['name_friendly' => 'Estación Salón', 'location_type' => 'indoor', 'zone' => 'Salón'],
        ];

        $stations = [];

        foreach ($definitions as $def) {
            $device = HardwareDevice::create([
                'user_id' => $userId,
                'hardware_type_id' => $typeId,
                'name' => $def['name_friendly'],
                'name_friendly' => $def['name_friendly'],
                'location_type' => $def['location_type'],
                'zone' => $def['zone'],
            ]);

            $stations[] = ['device' => $device, 'profile' => $def['location_type']];
        }

        $this->warn('   No había estaciones: se han creado 3 de ejemplo (2 exterior, 1 interior).');

        return $stations;
    }

    /**
     * Devuelve el id del tipo de hardware "Estación Meteorológica", creándolo
     * mediante el seeder oficial si aún no existe.
     */
    private function resolveWeatherStationTypeId(): int
    {
        $type = HardwareType::where('name', HardwareType::WEATHER_STATION)->first();

        if (! $type) {
            $this->call(HardwareTypesSeeder::class);
            $type = HardwareType::where('name', HardwareType::WEATHER_STATION)->firstOrFail();
        }

        return $type->id;
    }

    /**
     * Inserta las lecturas de sensores para una estación según su perfil.
     */
    private function seedSensors(int $userId, int $deviceId, array $profile, int $count, Carbon $now): void
    {
        $bar = $this->output->createProgressBar($count);
        $bar->start();

        for ($i = 0; $i < $count; $i++) {
            $timestamp = $now->copy()->subMinutes($count - $i);

            Temperature::create([
                'user_id' => $userId,
                'hardware_device_id' => $deviceId,
                'value' => fake()->randomFloat(2, $profile['temperature'][0], $profile['temperature'][1]),
                'created_at' => $timestamp,
            ]);

            Humidity::create([
                'user_id' => $userId,
                'hardware_device_id' => $deviceId,
                'value' => fake()->randomFloat(2, $profile['humidity'][0], $profile['humidity'][1]),
                'created_at' => $timestamp,
            ]);

            Pressure::create([
                'user_id' => $userId,
                'hardware_device_id' => $deviceId,
                'value' => fake()->randomFloat(2, 990, 1040),
                'created_at' => $timestamp,
            ]);

            Light::create([
                'user_id' => $userId,
                'hardware_device_id' => $deviceId,
                'lumens' => fake()->randomFloat(2, 0, $profile['light_max']),
                'index' => fake()->randomFloat(2, 0, $profile['uv_index_max']),
                'lux' => fake()->randomFloat(2, 0, $profile['light_max']),
                'uva' => fake()->randomFloat(2, 0, $profile['uva_max']),
                'uvb' => fake()->randomFloat(2, 0, $profile['uvb_max']),
                'created_at' => $timestamp,
            ]);

            if ($profile['has_wind']) {
                Wind::create([
                    'user_id' => $userId,
                    'hardware_device_id' => $deviceId,
                    'speed' => $speed = fake()->randomFloat(2, 0, 50),
                    'average' => $speed * 0.8,
                    'min' => $speed * 0.4,
                    'max' => $speed * 1.5,
                    'created_at' => $timestamp,
                ]);

                $grades = fake()->numberBetween(0, 360);
                WindDirection::create([
                    'user_id' => $userId,
                    'hardware_device_id' => $deviceId,
                    'resistance' => WindDirection::getResistance($grades),
                    'direction' => WindDirection::getDirection($grades),
                    'grades' => $grades,
                    'created_at' => $timestamp,
                ]);
            }

            if ($profile['has_rain']) {
                Rain::create([
                    'user_id' => $userId,
                    'hardware_device_id' => $deviceId,
                    'rain' => fake()->randomFloat(2, 0, 10),
                    'rain_intensity' => fake()->randomFloat(2, 0, 20),
                    'rain_month' => fake()->randomFloat(2, 0, 150),
                    'moisture' => fake()->randomFloat(2, 0, 100),
                    'created_at' => $timestamp,
                ]);
            }

            Eco2::create([
                'user_id' => $userId,
                'hardware_device_id' => $deviceId,
                'value' => fake()->numberBetween(400, 2000),
                'created_at' => $timestamp,
            ]);

            Tvoc::create([
                'user_id' => $userId,
                'hardware_device_id' => $deviceId,
                'value' => fake()->numberBetween(0, 1000),
                'created_at' => $timestamp,
            ]);

            AirQuality::create([
                'user_id' => $userId,
                'hardware_device_id' => $deviceId,
                'gas_resistance' => fake()->randomFloat(2, 1000, 50000),
                'air_quality' => fake()->randomFloat(2, 0, 100),
                'created_at' => $timestamp,
            ]);

            if ($profile['has_lightning']) {
                Lightning::create([
                    'user_id' => $userId,
                    'hardware_device_id' => $deviceId,
                    'distance' => fake()->numberBetween(1, 40),
                    'energy' => fake()->numberBetween(0, 1000),
                    'noise_floor' => fake()->boolean(),
                    'created_at' => $timestamp,
                ]);
            }

            MeteorologyUvIndex::create([
                'user_id' => $userId,
                'hardware_device_id' => $deviceId,
                'value' => fake()->randomFloat(4, 0, $profile['uv_index_max']),
                'created_at' => $timestamp,
            ]);

            MeteorologyUva::create([
                'user_id' => $userId,
                'hardware_device_id' => $deviceId,
                'value' => fake()->randomFloat(4, 0, $profile['uva_max']),
                'created_at' => $timestamp,
            ]);

            MeteorologyUvb::create([
                'user_id' => $userId,
                'hardware_device_id' => $deviceId,
                'value' => fake()->randomFloat(4, 0, $profile['uvb_max']),
                'created_at' => $timestamp,
            ]);

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
    }

    /**
     * Inserta el resumen del día y los resúmenes históricos (últimos 30 días)
     * para una estación según su perfil.
     */
    private function seedResumes(int $userId, int $deviceId, array $profile, int $count, Carbon $now): void
    {
        MeteorologyResumeToday::create($this->resumePayload($userId, $deviceId, $profile, now()));

        for ($d = 1; $d <= 30; $d++) {
            MeteorologyResumeHistorical::create(
                $this->resumePayload($userId, $deviceId, $profile, $now->copy()->subDays($d))
            );
        }
    }

    /**
     * Construye el payload de un resumen (hoy/histórico) según el perfil.
     *
     * @return array<string, mixed>
     */
    private function resumePayload(int $userId, int $deviceId, array $profile, Carbon $createdAt): array
    {
        return [
            'user_id' => $userId,
            'hardware_device_id' => $deviceId,
            'air_quality' => fake()->randomFloat(2, 50, 100),
            'eco2' => fake()->randomFloat(2, 400, 2000),
            'humidity' => fake()->randomFloat(2, $profile['humidity'][0], $profile['humidity'][1]),
            'light' => fake()->randomFloat(2, 0, $profile['light_max']),
            'pressure' => fake()->randomFloat(2, 990, 1040),
            'temperature' => fake()->randomFloat(2, $profile['temperature'][0], $profile['temperature'][1]),
            'tvoc' => fake()->randomFloat(2, 0, 500),
            'uv_index' => fake()->randomFloat(2, 0, $profile['uv_index_max']),
            'uva' => fake()->randomFloat(2, 0, $profile['uva_max']),
            'uvb' => fake()->randomFloat(2, 0, $profile['uvb_max']),
            'wind_speed' => $profile['has_wind'] ? fake()->randomFloat(2, 0, 40) : 0,
            'wind_speed_max' => $profile['has_wind'] ? fake()->randomFloat(2, 20, 60) : 0,
            'wind_speed_min' => $profile['has_wind'] ? fake()->randomFloat(2, 0, 10) : 0,
            'wind_direction' => $profile['has_wind'] ? fake()->randomFloat(2, 0, 360) : 0,
            'lightning' => $profile['has_lightning'] ? fake()->numberBetween(0, 10) : 0,
            'lightning_distance' => $profile['has_lightning'] ? fake()->randomFloat(2, 5, 40) : 0,
            'lightning_intensity' => $profile['has_lightning'] ? fake()->randomFloat(2, 0, 500) : 0,
            'rain' => $profile['has_rain'] ? fake()->randomFloat(2, 0, 20) : 0,
            'rain_intensity' => $profile['has_rain'] ? fake()->randomFloat(2, 0, 10) : 0,
            'created_at' => $createdAt,
        ];
    }
}
