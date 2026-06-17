<?php

namespace App\Console\Commands\Debug;

use App\Console\Commands\Debug\Concerns\ResolvesDebugDefaults;
use App\Models\Category;
use App\Models\Content\Content;
use App\Models\Content\ContentAvailableStatus;
use App\Models\Content\ContentAvailableType;
use App\Models\Content\ContentCategory;
use App\Models\Content\ContentMetadata;
use App\Models\Content\ContentPage;
use App\Models\Content\ContentSeo;
use App\Models\Platform;
use App\Models\PlatformCategory;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SeedContentDebugCommand extends Command
{
    use ResolvesDebugDefaults;

    protected $signature = 'debug:seed-content {--count=10 : Número de contenidos a crear}';

    protected $description = 'Crea contenidos (artículos, tutoriales, proyectos) de prueba (solo desarrollo)';

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

        // Asegurar plataforma
        $platform = Platform::query()->first() ?? Platform::create([
            'name' => 'Raupulus Platform',
            'slug' => 'raupulus-platform',
            'url' => 'https://raupulus.dev',
            'description' => 'Plataforma de prueba',
        ]);

        // Asegurar categoría y platform_category (las categorías se gestionan en CategoriesSeeder)
        $category = Category::query()->first();
        if (! $category) {
            $this->error('No hay categorías en la BD. Ejecuta primero: php artisan db:seed --class=CategoriesSeeder');

            return self::FAILURE;
        }

        $platformCategory = PlatformCategory::query()
            ->where('platform_id', $platform->id)
            ->where('category_id', $category->id)
            ->first() ?? PlatformCategory::create([
                'platform_id' => $platform->id,
                'category_id' => $category->id,
            ]);

        // Asegurar type y status
        $type = ContentAvailableType::query()->first() ?? ContentAvailableType::create([
            'name' => 'Artículo',
            'plural_name' => 'Artículos',
            'slug' => 'article',
            'description' => 'Artículos de prueba',
        ]);

        $status = ContentAvailableStatus::query()->where('slug', 'published')->first()
            ?? ContentAvailableStatus::create([
                'name' => 'Publicado',
                'slug' => 'published',
                'description' => 'Publicado de prueba',
            ]);

        $this->info("Creando {$count} contenidos de prueba...");

        for ($i = 0; $i < $count; $i++) {
            $title = fake()->sentence(6);
            $content = Content::create([
                'author_id' => $userId,
                'platform_id' => $platform->id,
                'type_id' => $type->id,
                'status_id' => $status->id,
                'title' => $title,
                'slug' => Str::slug($title).'-'.Str::random(5),
                'excerpt' => fake()->paragraph(),
                'is_active' => true,
                'published_at' => fake()->dateTimeBetween('-1 year', 'now'),
            ]);

            // Crear página de contenido
            ContentPage::create([
                'content_id' => $content->id,
                'title' => 'Page 1',
                'slug' => 'page-1',
                'content' => '<p>'.fake()->paragraphs(3, true).'</p>',
                'order' => 1,
            ]);

            // Categoría (pivote)
            ContentCategory::create([
                'content_id' => $content->id,
                'platform_category_id' => $platformCategory->id,
            ]);

            // Metadata y SEO básicos
            ContentMetadata::create([
                'content_id' => $content->id,
                'web' => fake()->url(),
                'github' => 'https://github.com/'.fake()->userName().'/'.fake()->word(),
            ]);

            ContentSeo::create([
                'content_id' => $content->id,
                'og_title' => $title,
                'description' => fake()->sentence(15),
                'keywords' => implode(',', fake()->words(5)),
            ]);

            // Vistas diarias (última semana + hoy)
            $today = Carbon::today();
            for ($d = 0; $d <= 7; $d++) {
                $date = $today->copy()->subDays($d);
                DB::table('content_daily_views')->insert([
                    'content_id' => $content->id,
                    'date' => $date->toDateString(),
                    'views' => fake()->numberBetween(1, $d === 0 ? 500 : 200),
                    'created_at' => $date,
                    'updated_at' => now(),
                ]);
            }
        }

        $this->info("✅ {$count} contenidos creados con páginas, metadata y SEO.");

        return self::SUCCESS;
    }
}
