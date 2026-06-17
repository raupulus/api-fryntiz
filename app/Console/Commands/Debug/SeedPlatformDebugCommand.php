<?php

namespace App\Console\Commands\Debug;

use App\Console\Commands\Debug\Concerns\ResolvesDebugDefaults;
use App\Models\Category;
use App\Models\Platform;
use App\Models\Tag;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class SeedPlatformDebugCommand extends Command
{
    use ResolvesDebugDefaults;

    protected $signature = 'debug:seed-platform {--count=3 : Número de plataformas a crear}';

    protected $description = 'Crea plataformas de prueba con categorías y etiquetas (solo desarrollo)';

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

        // Las categorías y etiquetas deben provenir de sus seeders, nunca de un
        // comando de debug. Si no existen, abortamos pidiendo ejecutar el seed.
        if (Category::query()->doesntExist() || Tag::query()->doesntExist()) {
            $this->error('No hay categorías/etiquetas. Ejecuta primero los seeders (php artisan db:seed).');

            return self::FAILURE;
        }

        $this->info("Creando {$count} plataformas de prueba...");

        for ($i = 0; $i < $count; $i++) {
            $title = fake()->unique()->company();
            $platform = Platform::create([
                'user_id' => $userId,
                'title' => $title,
                'slug' => Str::slug($title).'-'.Str::random(5),
                'description' => fake()->sentence(),
                'domain' => fake()->domainName(),
            ]);

            // Asociar categoría existente (creadas por el seeder, nunca aquí)
            $category = Category::query()->inRandomOrder()->first();
            $platform->categories()->syncWithoutDetaching([$category->id]);

            // Asociar etiqueta existente (creadas por el seeder, nunca aquí)
            $tag = Tag::query()->inRandomOrder()->first();
            $platform->tags()->syncWithoutDetaching([$tag->id]);
        }

        $this->info("✅ {$count} plataformas creadas con categorías y etiquetas.");

        return self::SUCCESS;
    }
}
