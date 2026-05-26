<?php

namespace App\Console\Commands\Debug;

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
    protected $signature = 'debug:seed-weatherstation {--count=20 : Número de registros por sensor}';

    protected $description = 'Inserta registros de debug para todos los sensores de WeatherStation (solo desarrollo)';

    public function handle(): int
    {
        $count = (int) $this->option('count');
        $now = Carbon::now();

        $this->info("Insertando {$count} registros por sensor...");

        $bar = $this->output->createProgressBar($count);
        $bar->start();

        for ($i = 0; $i < $count; $i++) {
            $timestamp = $now->copy()->subMinutes($count - $i);

            Temperature::create([
                'hardware_device_id' => 1,
                'value' => fake()->randomFloat(2, 5, 42),
                'created_at' => $timestamp,
            ]);

            Humidity::create([
                'hardware_device_id' => 1,
                'value' => fake()->randomFloat(2, 20, 98),
                'created_at' => $timestamp,
            ]);

            Pressure::create([
                'hardware_device_id' => 1,
                'value' => fake()->randomFloat(2, 990, 1040),
                'created_at' => $timestamp,
            ]);

            Light::create([
                'hardware_device_id' => 1,
                'value' => fake()->numberBetween(0, 100000),
                'created_at' => $timestamp,
            ]);

            Wind::create([
                'hardware_device_id' => 1,
                'value' => fake()->randomFloat(2, 0, 120),
                'created_at' => $timestamp,
            ]);

            WindDirection::create([
                'hardware_device_id' => 1,
                'value' => fake()->numberBetween(0, 360),
                'created_at' => $timestamp,
            ]);

            Rain::create([
                'hardware_device_id' => 1,
                'value' => fake()->randomFloat(2, 0, 50),
                'created_at' => $timestamp,
            ]);

            Eco2::create([
                'hardware_device_id' => 1,
                'value' => fake()->numberBetween(400, 2000),
                'created_at' => $timestamp,
            ]);

            Tvoc::create([
                'hardware_device_id' => 1,
                'value' => fake()->numberBetween(0, 1000),
                'created_at' => $timestamp,
            ]);

            AirQuality::create([
                'hardware_device_id' => 1,
                'value' => fake()->randomFloat(2, 0, 100),
                'created_at' => $timestamp,
            ]);

            Lightning::create([
                'hardware_device_id' => 1,
                'value' => fake()->numberBetween(0, 5),
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
