<?php

declare(strict_types=1);

namespace App\Console\Commands\Debug;

use App\Console\Commands\Debug\Concerns\ResolvesDebugDefaults;
use App\Models\Newsletter;
use App\Models\Platform;
use Illuminate\Console\Command;

class SeedNewsletterDebugCommand extends Command
{
    use ResolvesDebugDefaults;

    protected $signature = 'debug:seed-newsletter {--count=10 : Número de suscripciones a crear}';

    protected $description = 'Crea suscripciones a newsletter de prueba (solo desarrollo)';

    public function handle(): int
    {
        if (! $this->guardEnvironment()) {
            return self::FAILURE;
        }

        $count = (int) $this->option('count');

        // Asegurar plataforma
        $platform = Platform::query()->first() ?? Platform::create([
            'name' => 'Raupulus Platform',
            'slug' => 'raupulus-platform',
            'url' => 'https://raupulus.dev',
            'description' => 'Plataforma de prueba',
        ]);

        $this->info("Creando {$count} suscripciones de newsletter de prueba...");

        for ($i = 0; $i < $count; $i++) {
            $isVerified = fake()->boolean(70);
            Newsletter::create([
                'platform_id' => $platform->id,
                'email' => fake()->unique()->safeEmail(),
                'name' => fake()->name(),
                'is_verified' => $isVerified,
                'verified_at' => $isVerified ? now() : null,
                'status' => $isVerified ? Newsletter::STATUS_ACTIVE : Newsletter::STATUS_INACTIVE,
                'language' => fake()->randomElement(['es', 'en']),
                'subscription_source' => fake()->randomElement([Newsletter::SOURCE_WEB, Newsletter::SOURCE_API]),
                'ip_address' => fake()->ipv4(),
            ]);
        }

        $this->info("✅ {$count} suscripciones newsletter creadas.");

        return self::SUCCESS;
    }
}
