<?php

namespace App\Console\Commands\Debug;

use App\Console\Commands\Debug\Concerns\ResolvesDebugDefaults;
use App\Models\Email;
use Illuminate\Console\Command;

class SeedContactDebugCommand extends Command
{
    use ResolvesDebugDefaults;

    protected $signature = 'debug:seed-contact {--count=10 : Número de mensajes a crear}';

    protected $description = 'Crea mensajes de contacto de prueba (solo desarrollo)';

    public function handle(): int
    {
        if (! $this->guardEnvironment()) {
            return self::FAILURE;
        }

        $count = (int) $this->option('count');
        $this->info("Creando {$count} mensajes de contacto de prueba...");

        for ($i = 0; $i < $count; $i++) {
            Email::create([
                'email' => fake()->safeEmail(),
                'subject' => fake()->sentence(),
                'message' => fake()->paragraph(),
                'privacity' => true,
                'contactme' => fake()->boolean(),
                'server_ip' => '127.0.0.1',
                'client_ip' => fake()->ipv4(),
                'app_name' => 'Raupulus Debug',
                'app_domain' => 'raupulus.dev',
                'client_user_agent' => fake()->userAgent(),
            ]);
        }

        $this->info("✅ {$count} mensajes de contacto creados.");
        return self::SUCCESS;
    }
}
