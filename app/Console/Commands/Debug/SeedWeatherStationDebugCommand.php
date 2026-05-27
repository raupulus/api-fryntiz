<?php

namespace App\Console\Commands\Debug;

use App\Console\Commands\Debug\Concerns\ResolvesDebugDefaults;
use App\Models\WeatherStation\AirQuality;
use App\Models\WeatherStation\Eco2;
use App\Models\WeatherStation\Humidity;
use App\Models\WeatherStation\Light;
use App\Models\WeatherStation\Lightning;
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
                'hardware_device_id' => $hardwareDeviceId,
                'value' => fake()->randomFloat(2, 5, 42),
                'created_at' => $timestamp,
            ]);

            Humidity::create([
                'hardware_device_id' => $hardwareDeviceId,
                'value' => fake()->randomFloat(2, 20, 98),
                'created_at' => $timestamp,
            ]);

            Pressure::create([
                'hardware_device_id' => $hardwareDeviceId,
                'value' => fake()->randomFloat(2, 990, 1040),
                'created_at' => $timestamp,
            ]);

            Light::create([
                'hardware_device_id' => $hardwareDeviceId,
                'lumens' => fake()->randomFloat(2, 0, 1000),
                'index' => fake()->randomFloat(2, 0, 11),
                'lux' => fake()->randomFloat(2, 0, 1000),
                'uva' => fake()->randomFloat(2, 0, 500),
                'uvb' => fake()->randomFloat(2, 0, 500),
                'created_at' => $timestamp,
            ]);

            Wind::create([
                'hardware_device_id' => $hardwareDeviceId,
                'speed' => $speed = fake()->randomFloat(2, 0, 50),
                'average' => $speed * 0.8,
                'min' => $speed * 0.4,
                'max' => $speed * 1.5,
                'created_at' => $timestamp,
            ]);

            $grades = fake()->numberBetween(0, 360);
            WindDirection::create([
                'hardware_device_id' => $hardwareDeviceId,
                'resistance' => WindDirection::getResistance($grades),
                'direction' => WindDirection::getDirection($grades),
                'grades' => $grades,
                'created_at' => $timestamp,
            ]);

            Rain::create([
                'hardware_device_id' => $hardwareDeviceId,
                'rain' => fake()->randomFloat(2, 0, 10),
                'rain_intensity' => fake()->randomFloat(2, 0, 20),
                'rain_month' => fake()->randomFloat(2, 0, 150),
                'moisture' => fake()->randomFloat(2, 0, 100),
                'created_at' => $timestamp,
            ]);

            Eco2::create([
                'hardware_device_id' => $hardwareDeviceId,
                'value' => fake()->numberBetween(400, 2000),
                'created_at' => $timestamp,
            ]);

            Tvoc::create([
                'hardware_device_id' => $hardwareDeviceId,
                'value' => fake()->numberBetween(0, 1000),
                'created_at' => $timestamp,
            ]);

            AirQuality::create([
                'hardware_device_id' => $hardwareDeviceId,
                'gas_resistance' => fake()->randomFloat(2, 1000, 50000),
                'air_quality' => fake()->randomFloat(2, 0, 100),
                'created_at' => $timestamp,
            ]);

            Lightning::create([
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
        $this->info("✅ {$count} registros insertados por cada sensor (11 sensores).");

        return self::SUCCESS;
    }
}
