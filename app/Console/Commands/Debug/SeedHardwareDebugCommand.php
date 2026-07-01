<?php

declare(strict_types=1);

namespace App\Console\Commands\Debug;

use App\Console\Commands\Debug\Concerns\ResolvesDebugDefaults;
use App\Models\Hardware\HardwareDevice;
use App\Models\Hardware\HardwareType;
use Illuminate\Console\Command;

class SeedHardwareDebugCommand extends Command
{
    use ResolvesDebugDefaults;

    protected $signature = 'debug:seed-hardware {--count=5 : Número de dispositivos a crear}';

    protected $description = 'Crea dispositivos hardware de prueba (solo desarrollo)';

    public function handle(): int
    {
        if (! $this->guardEnvironment()) {
            return self::FAILURE;
        }

        $userId = $this->resolveUserId();
        if (! $userId) {
            return self::FAILURE;
        }

        $count = (int) $this->option('count');
        $hardwareType = HardwareType::query()->orderBy('id')->first()
            ?? HardwareType::create(['name' => 'Generic', 'description' => 'Tipo genérico de prueba']);

        // Faker no dispone de un proveedor "Lorem" en español, así que se
        // evita fake()->words()/sentence() (generan texto en latín) para
        // estos campos que se muestran directamente en el frontend.
        $friendlyNames = [
            'Estación meteorológica', 'Nodo de sensores', 'Servidor doméstico',
            'Placa de desarrollo', 'Cámara de vigilancia', 'Router principal',
            'Sensor de temperatura', 'Panel de control', 'Sonda de humedad',
            'Contador de pulsaciones',
        ];

        $descriptions = [
            'Dispositivo de prueba generado para depuración local.',
            'Equipo de pruebas usado en el entorno de desarrollo.',
            'Dispositivo ficticio destinado a pruebas internas.',
            'Registrado automáticamente para poblar datos de ejemplo.',
        ];

        $this->info("Creando {$count} dispositivos hardware de prueba...");

        for ($i = 0; $i < $count; $i++) {
            HardwareDevice::create([
                'user_id' => $userId,
                'hardware_type_id' => $hardwareType->id,
                'name' => 'debug-device-'.fake()->unique()->bothify('???-####'),
                'name_friendly' => fake()->randomElement($friendlyNames).' '.fake()->numberBetween(1, 99),
                'ref' => strtoupper(fake()->bothify('REF-#####')),
                'model' => fake()->word(),
                'brand' => fake()->company(),
                'software_version' => fake()->semver(),
                'hardware_version' => fake()->semver(),
                'serial_number' => fake()->uuid(),
                'description' => fake()->randomElement($descriptions),
                'buy_at' => fake()->dateTimeBetween('-3 years', 'now'),
                'last_seen_at' => now(),
                'ip_local' => fake()->localIpv4(),
                'ip_public' => fake()->ipv4(),
            ]);
        }

        $this->info("✅ {$count} dispositivos hardware creados.");

        return self::SUCCESS;
    }
}
