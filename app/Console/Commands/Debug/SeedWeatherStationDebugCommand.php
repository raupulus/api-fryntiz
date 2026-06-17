<?php

namespace App\Console\Commands\Debug;

use App\Console\Commands\Debug\Concerns\ResolvesDebugDefaults;
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
use Illuminate\Console\Command;

/**
 * Comando de debug para insertar registros de prueba en WeatherStation.
 * NO usar en producción. Solo para desarrollo/depuración manual.
 */
class SeedWeatherStationDebugCommand extends Command
{
    use ResolvesDebugDefaults;

    protected $signature = 'debug:seed-weatherstation {--count=20 : Número de registros por sensor}';

    protected $description = 'Inserta registros de debug para todos los sensores de WeatherStation (solo desarrollo)';

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

        $this->info("Insertando {$count} registros por sensor...");

        $bar = $this->output->createProgressBar($count);
        $bar->start();

        for ($i = 0; $i < $count; $i++) {
            $timestamp = $now->copy()->subMinutes($count - $i);

            Temperature::create([
                'user_id' => $userId,
                'hardware_device_id' => $hardwareDeviceId,
                'value' => fake()->randomFloat(2, 5, 42),
                'created_at' => $timestamp,
            ]);

            Humidity::create([
                'user_id' => $userId,
                'hardware_device_id' => $hardwareDeviceId,
                'value' => fake()->randomFloat(2, 20, 98),
                'created_at' => $timestamp,
            ]);

            Pressure::create([
                'user_id' => $userId,
                'hardware_device_id' => $hardwareDeviceId,
                'value' => fake()->randomFloat(2, 990, 1040),
                'created_at' => $timestamp,
            ]);

            Light::create([
                'user_id' => $userId,
                'hardware_device_id' => $hardwareDeviceId,
                'lumens' => fake()->randomFloat(2, 0, 1000),
                'index' => fake()->randomFloat(2, 0, 11),
                'lux' => fake()->randomFloat(2, 0, 1000),
                'uva' => fake()->randomFloat(2, 0, 500),
                'uvb' => fake()->randomFloat(2, 0, 500),
                'created_at' => $timestamp,
            ]);

            Wind::create([
                'user_id' => $userId,
                'hardware_device_id' => $hardwareDeviceId,
                'speed' => $speed = fake()->randomFloat(2, 0, 50),
                'average' => $speed * 0.8,
                'min' => $speed * 0.4,
                'max' => $speed * 1.5,
                'created_at' => $timestamp,
            ]);

            $grades = fake()->numberBetween(0, 360);
            WindDirection::create([
                'user_id' => $userId,
                'hardware_device_id' => $hardwareDeviceId,
                'resistance' => WindDirection::getResistance($grades),
                'direction' => WindDirection::getDirection($grades),
                'grades' => $grades,
                'created_at' => $timestamp,
            ]);

            Rain::create([
                'user_id' => $userId,
                'hardware_device_id' => $hardwareDeviceId,
                'rain' => fake()->randomFloat(2, 0, 10),
                'rain_intensity' => fake()->randomFloat(2, 0, 20),
                'rain_month' => fake()->randomFloat(2, 0, 150),
                'moisture' => fake()->randomFloat(2, 0, 100),
                'created_at' => $timestamp,
            ]);

            Eco2::create([
                'user_id' => $userId,
                'hardware_device_id' => $hardwareDeviceId,
                'value' => fake()->numberBetween(400, 2000),
                'created_at' => $timestamp,
            ]);

            Tvoc::create([
                'user_id' => $userId,
                'hardware_device_id' => $hardwareDeviceId,
                'value' => fake()->numberBetween(0, 1000),
                'created_at' => $timestamp,
            ]);

            AirQuality::create([
                'user_id' => $userId,
                'hardware_device_id' => $hardwareDeviceId,
                'gas_resistance' => fake()->randomFloat(2, 1000, 50000),
                'air_quality' => fake()->randomFloat(2, 0, 100),
                'created_at' => $timestamp,
            ]);

            Lightning::create([
                'user_id' => $userId,
                'hardware_device_id' => $hardwareDeviceId,
                'distance' => fake()->numberBetween(1, 40),
                'energy' => fake()->numberBetween(0, 1000),
                'noise_floor' => fake()->boolean(),
                'created_at' => $timestamp,
            ]);

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        $this->info('Insertando resúmenes meteorológicos y datos UV...');

        // Resumen de hoy
        MeteorologyResumeToday::create([
            'user_id' => $userId,
            'hardware_device_id' => $hardwareDeviceId,
            'air_quality' => fake()->randomFloat(2, 50, 100),
            'eco2' => fake()->randomFloat(2, 400, 2000),
            'humidity' => fake()->randomFloat(2, 30, 90),
            'light' => fake()->randomFloat(2, 0, 1000),
            'pressure' => fake()->randomFloat(2, 990, 1040),
            'temperature' => fake()->randomFloat(2, 10, 38),
            'tvoc' => fake()->randomFloat(2, 0, 500),
            'uv_index' => fake()->randomFloat(2, 0, 11),
            'uva' => fake()->randomFloat(2, 0, 500),
            'uvb' => fake()->randomFloat(2, 0, 300),
            'wind_speed' => fake()->randomFloat(2, 0, 40),
            'wind_speed_max' => fake()->randomFloat(2, 20, 60),
            'wind_speed_min' => fake()->randomFloat(2, 0, 10),
            'wind_direction' => fake()->randomFloat(2, 0, 360),
            'lightning' => fake()->numberBetween(0, 5),
            'lightning_distance' => fake()->randomFloat(2, 5, 40),
            'lightning_intensity' => fake()->randomFloat(2, 0, 500),
            'rain' => fake()->randomFloat(2, 0, 20),
            'rain_intensity' => fake()->randomFloat(2, 0, 10),
            'created_at' => now(),
        ]);

        // Resúmenes históricos (últimos 30 días)
        for ($d = 1; $d <= 30; $d++) {
            $day = $now->copy()->subDays($d);
            MeteorologyResumeHistorical::create([
                'user_id' => $userId,
                'hardware_device_id' => $hardwareDeviceId,
                'air_quality' => fake()->randomFloat(2, 50, 100),
                'eco2' => fake()->randomFloat(2, 400, 2000),
                'humidity' => fake()->randomFloat(2, 30, 90),
                'light' => fake()->randomFloat(2, 0, 1000),
                'pressure' => fake()->randomFloat(2, 990, 1040),
                'temperature' => fake()->randomFloat(2, 10, 38),
                'tvoc' => fake()->randomFloat(2, 0, 500),
                'uv_index' => fake()->randomFloat(2, 0, 11),
                'uva' => fake()->randomFloat(2, 0, 500),
                'uvb' => fake()->randomFloat(2, 0, 300),
                'wind_speed' => fake()->randomFloat(2, 0, 40),
                'wind_speed_max' => fake()->randomFloat(2, 20, 60),
                'wind_speed_min' => fake()->randomFloat(2, 0, 10),
                'wind_direction' => fake()->randomFloat(2, 0, 360),
                'lightning' => fake()->numberBetween(0, 10),
                'lightning_distance' => fake()->randomFloat(2, 5, 40),
                'lightning_intensity' => fake()->randomFloat(2, 0, 500),
                'rain' => fake()->randomFloat(2, 0, 50),
                'rain_intensity' => fake()->randomFloat(2, 0, 20),
                'created_at' => $day,
            ]);
        }

        // Datos UV (últimos registros)
        for ($i = 0; $i < $count; $i++) {
            $timestamp = $now->copy()->subMinutes($count - $i);

            MeteorologyUvIndex::create([
                'user_id' => $userId,
                'hardware_device_id' => $hardwareDeviceId,
                'value' => fake()->randomFloat(4, 0, 11),
                'created_at' => $timestamp,
            ]);

            MeteorologyUva::create([
                'user_id' => $userId,
                'hardware_device_id' => $hardwareDeviceId,
                'value' => fake()->randomFloat(4, 0, 500),
                'created_at' => $timestamp,
            ]);

            MeteorologyUvb::create([
                'user_id' => $userId,
                'hardware_device_id' => $hardwareDeviceId,
                'value' => fake()->randomFloat(4, 0, 300),
                'created_at' => $timestamp,
            ]);
        }

        $this->info("✅ {$count} registros insertados por cada sensor + resúmenes y datos UV.");

        return self::SUCCESS;
    }
}
