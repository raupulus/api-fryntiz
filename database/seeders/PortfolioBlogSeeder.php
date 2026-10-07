<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\ContentPageFormatEnum;
use App\Enums\ContentStatusEnum;
use App\Models\Category;
use App\Models\Content\Content;
use App\Models\Content\ContentAvailableType;
use App\Models\Content\ContentFile;
use App\Models\Content\ContentMetadata;
use App\Models\Content\ContentPage;
use App\Models\Content\ContentRelated;
use App\Models\Content\ContentSeo;
use App\Models\Content\ContentTechnology;
use App\Models\File;
use App\Models\Gallery;
use App\Models\GalleryImage;
use App\Models\Platform;
use App\Models\PlatformCategory;
use App\Models\Tag;
use App\Models\Technology;
use App\Models\User;
use App\Services\Content\ContentFileUsageService;
use App\Services\Content\ContentPageFormatService;
use Database\Seeders\Data\PortfolioBlogPosts;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * 20 entradas de blog de ejemplo en la plataforma `portfolio`, para desarrollar
 * y probar un cliente de la API (solo desarrollo, nunca en producción).
 *
 * Cada entrada se crea con todo lo que puede tener un contenido: tipo «Blog»,
 * autor, colaboradores, imagen de portada, páginas en Editor.js (guardadas con
 * `ContentPageFormatService`, igual que el panel), categorías (una principal),
 * etiquetas, tecnologías, SEO, metadatos, ficheros en uso, galerías, contenidos
 * relacionados y visitas diarias. Hay entradas destacadas y con varias
 * páginas, y también una oculta, un borrador y una programada, para ver qué
 * hace el cliente con lo que la API no devuelve.
 *
 * Las imágenes son ficheros que ya existen en la base: no se sube nada.
 *
 * Es idempotente por slug y **no borra nada**: una entrada que ya existe se
 * salta. Uso: `php artisan db:seed --class=PortfolioBlogSeeder`.
 */
class PortfolioBlogSeeder extends Seeder
{
    public const PLATFORM_SLUG = 'portfolio';

    /** @var list<int> */
    private array $imagePool = [];

    /** @var list<int> */
    private array $seoImagePool = [];

    /** @var list<int> */
    private array $pageImagePool = [];

    /** @var array<int, Content> */
    private array $created = [];

    public function run(): void
    {
        if (app()->isProduction()) {
            $this->command?->error('Este seeder es solo para desarrollo.');

            return;
        }

        $platform = Platform::query()->where('slug', self::PLATFORM_SLUG)->first();

        if ($platform === null) {
            $this->command?->error('No existe la plataforma «'.self::PLATFORM_SLUG.'».');

            return;
        }

        $author = User::query()->find($platform->user_id) ?? User::query()->orderBy('id')->first();
        $type = ContentAvailableType::query()->where('slug', 'blog')->first();

        if ($author === null || $type === null) {
            $this->command?->error('Faltan el autor o el tipo de contenido «blog» (ContentAvailableTypesSeeder).');

            return;
        }

        $this->loadImagePools();

        $posts = PortfolioBlogPosts::all();
        $new = 0;

        foreach ($posts as $number => $post) {
            $position = $number + 1;
            $slug = Str::slug($post['title']);

            $existing = Content::withTrashed()->where('platform_id', $platform->id)->where('slug', $slug)->first();

            if ($existing !== null) {
                $this->created[$position] = $existing;
                $this->command?->line("  = {$position}. ya existe: {$slug}");

                continue;
            }

            $this->created[$position] = DB::transaction(fn (): Content => $this->createPost($platform, $author, $type, $post, $position, $slug));
            $new++;
            $this->command?->info("  + {$position}. {$post['title']}");
        }

        $this->linkRelated($posts);

        $this->command?->info("Blog del portfolio: {$new} entradas nuevas, ".(count($posts) - $new).' que ya estaban.');
    }

    /**
     * @param  array<string, mixed>  $post
     */
    private function createPost(Platform $platform, User $author, ContentAvailableType $type, array $post, int $position, string $slug): Content
    {
        $state = (string) $post['state'];
        $publishedAt = Carbon::now()->subDays((int) $post['days_ago'])->setTime(9 + $position % 8, ($position * 7) % 60);
        $featured = (bool) $post['featured'];

        $content = Content::query()->create([
            'author_id' => $author->id,
            'platform_id' => $platform->id,
            'type_id' => $type->id,
            'status_id' => match ($state) {
                'draft' => ContentStatusEnum::Draft->value,
                'scheduled' => ContentStatusEnum::Scheduled->value,
                default => ContentStatusEnum::Published->value,
            },
            'image_id' => $this->pick($this->imagePool, $position * 3),
            'title' => $post['title'],
            'slug' => $slug,
            'excerpt' => $post['excerpt'],
            'is_copyright_valid' => true,
            'is_comment_enabled' => true,
            'is_comment_anonymous' => $position % 4 === 0,
            'is_active' => in_array($state, ['published', 'hidden'], true),
            'is_featured' => $featured,
            'is_visible_on_home' => $featured || $position % 3 === 0,
            'is_visible_on_menu' => false,
            'is_visible_on_footer' => false,
            'is_visible_on_sidebar' => $featured,
            'is_visible_on_search' => true,
            'is_visible_on_archive' => true,
            'is_visible_on_rss' => true,
            'is_visible_on_sitemap' => true,
            'is_visible_on_sitemap_news' => false,
            'published_at' => $state === 'published' || $state === 'hidden' ? $publishedAt : null,
            'scheduled_at' => $state === 'scheduled' ? Carbon::now()->addDays((int) ($post['scheduled_in_days'] ?? 7)) : null,
        ]);

        // Publicado y oculto: al publicar, el modelo marca «Activo»; se desmarca después.
        if ($state === 'hidden') {
            $content->is_active = false;
            $content->save();
        }

        $this->syncTaxonomy($content, $platform, $post);
        $this->createSeoAndMetadata($content, $post, $position);
        $this->createPages($content, $post, $position);
        $this->attachGallery($content, $author, $post, $position);
        $this->addCollaborators($content, $post);

        if ($content->is_active && $content->isPublished()) {
            $this->createDailyViews($content, $position);
        }

        // Fechas coherentes con la de publicación (las páginas «tocan» el contenido al guardarse).
        DB::table('contents')->where('id', $content->id)->update([
            'created_at' => $publishedAt->copy()->subDays(2),
            'updated_at' => $state === 'published' ? $publishedAt->copy()->addDays(1) : now(),
        ]);

        return $content->refresh();
    }

    /**
     * Categorías (con la principal), etiquetas y tecnologías por nombre.
     *
     * @param  array<string, mixed>  $post
     */
    private function syncTaxonomy(Content $content, Platform $platform, array $post): void
    {
        $categories = Category::query()->whereIn(DB::raw('lower(name)'), array_map('mb_strtolower', $post['categories']))->get();

        foreach ($categories as $category) {
            PlatformCategory::query()->firstOrCreate(['platform_id' => $platform->id, 'category_id' => $category->id]);
        }

        $main = $categories->first(fn (Category $category): bool => mb_strtolower($category->name) === mb_strtolower((string) $post['main']));
        $content->saveCategories($categories->pluck('id')->all(), [], $main?->id);

        $content->saveTags(Tag::query()->whereIn(DB::raw('lower(name)'), array_map('mb_strtolower', $post['tags']))->pluck('id')->all());

        $technologies = Technology::query()->whereIn(DB::raw('lower(name)'), array_map('mb_strtolower', $post['techs']))->pluck('id');

        foreach ($technologies as $technologyId) {
            ContentTechnology::query()->firstOrCreate(['content_id' => $content->id, 'technology_id' => $technologyId]);
        }
    }

    /**
     * @param  array<string, mixed>  $post
     */
    private function createSeoAndMetadata(Content $content, array $post, int $position): void
    {
        ContentSeo::query()->create([
            'content_id' => $content->id,
            'image_id' => $this->pick($this->seoImagePool, $position) ?? $content->image_id,
            'image_alt' => $post['title'],
            'distribution' => 'global',
            'keywords' => implode(', ', $post['tags']),
            'revisit_after' => '7 days',
            'description' => Str::limit((string) $post['excerpt'], 155, ''),
            'robots' => $content->is_active && $content->isPublished() ? 'index, follow' : 'noindex, nofollow',
            'og_title' => $post['title'],
            'og_type' => 'article',
            'twitter_card' => 'summary_large_image',
            'twitter_creator' => 'raupulus',
        ]);

        ContentMetadata::query()->create([
            'content_id' => $content->id,
            'web' => null,
            'telegram_channel' => null,
            'youtube_channel' => null,
            'youtube_video_id' => null,
            'gitlab' => null,
            'github' => null,
            'mastodon' => null,
            'twitter' => 'raupulus',
            ...$post['metadata'],
        ]);
    }

    /**
     * Páginas en Editor.js, guardadas por el servicio del panel: regenera el
     * HTML que se sirve, las versiones en Markdown y el historial.
     *
     * @param  array<string, mixed>  $post
     */
    private function createPages(Content $content, array $post, int $position): void
    {
        $service = app(ContentPageFormatService::class);

        foreach ($post['pages'] as $index => $pageData) {
            $order = $index + 1;

            $page = ContentPage::query()->create([
                'content_id' => $content->id,
                'image_id' => $order === 1 ? $this->pick($this->pageImagePool, $position) : null,
                'title' => $pageData['title'],
                'slug' => Str::slug($pageData['title']),
                'content' => '',
                'order' => $order,
            ]);

            $blocks = $this->resolveImages($content, $pageData['blocks']);

            $service->save($page, ContentPageFormatEnum::EditorJs, (string) json_encode(
                ['time' => now()->getTimestampMs(), 'blocks' => $blocks, 'version' => '2.31.7'],
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
            ));
        }

        app(ContentFileUsageService::class)->refresh($content->id);
    }

    /**
     * Cambia las imágenes pedidas por posición por bloques de imagen reales,
     * con las miniaturas del fichero y su fila en `content_files`. Sin
     * ficheros en la base, esos bloques se omiten.
     *
     * @param  list<array<string, mixed>>  $blocks
     * @return list<array<string, mixed>>
     */
    private function resolveImages(Content $content, array $blocks): array
    {
        $resolved = [];

        foreach ($blocks as $block) {
            if ($block['type'] !== 'image' || ! isset($block['data']['_pool'])) {
                $resolved[] = $block;

                continue;
            }

            $file = File::query()->find($this->pick($this->imagePool, 5 + (int) $block['data']['_pool'] * 4 + $content->id));

            if ($file === null) {
                continue;
            }

            ContentFile::query()->firstOrCreate(['content_id' => $content->id, 'file_id' => $file->id]);

            $block['data'] = [
                'file' => [
                    'url' => $file->thumbnail('normal'),
                    'url_thumbnail' => $file->thumbnail('small'),
                    'url_large' => $file->thumbnail('large'),
                    'content_id' => $content->id,
                    'file_id' => $file->id,
                    'module' => $file->module,
                    'title' => $file->title,
                    'name' => $file->name,
                    'alt' => $block['data']['caption'],
                    'size' => $file->size,
                ],
                'caption' => $block['data']['caption'],
                'withBorder' => false,
                'stretched' => false,
                'withBackground' => false,
            ];

            $resolved[] = $block;
        }

        return $resolved;
    }

    /**
     * Galería reutilizable (se crea una por nombre) con unas fotos en orden.
     *
     * @param  array<string, mixed>  $post
     */
    private function attachGallery(Content $content, User $author, array $post, int $position): void
    {
        if (empty($post['gallery']) || $this->imagePool === []) {
            return;
        }

        $gallery = Gallery::query()->firstOrCreate(
            ['name' => $post['gallery']],
            ['user_id' => $author->id, 'image_id' => $this->pick($this->imagePool, $position * 5), 'description' => 'Fotos de ejemplo para probar galerías en el cliente.', 'aspect_ratio' => '16:9'],
        );

        if ($gallery->wasRecentlyCreated) {
            foreach (range(0, 3) as $order) {
                GalleryImage::query()->create([
                    'gallery_id' => $gallery->id,
                    'image_id' => $this->pick($this->imagePool, $position * 5 + $order * 7),
                    'order' => $order,
                    'caption' => 'Foto '.($order + 1).' de '.Str::lower((string) $post['gallery']),
                ]);
            }
        }

        $content->galleries()->syncWithoutDetaching([$gallery->id => ['order' => 0]]);
        Content::markChanged($content->id);
    }

    /**
     * Colaboradores por id de usuario, si existen (el autor nunca lo es).
     *
     * @param  array<string, mixed>  $post
     */
    private function addCollaborators(Content $content, array $post): void
    {
        if ($post['collaborators'] === []) {
            return;
        }

        $content->saveContributors(User::query()->whereIn('id', $post['collaborators'])->pluck('id')->all());
    }

    /**
     * Visitas de los últimos días, con más peso en las recientes para que
     * «tendencia» (3 días) tenga un orden distinto al de «últimos».
     */
    private function createDailyViews(Content $content, int $position): void
    {
        foreach (range(0, 9) as $daysAgo) {
            DB::table('content_daily_views')->updateOrInsert(
                ['content_id' => $content->id, 'date' => Carbon::today()->subDays($daysAgo)->toDateString()],
                ['views' => random_int(1, 20) * (1 + ($position * 3 + $daysAgo) % 7), 'created_at' => now(), 'updated_at' => now()],
            );
        }
    }

    /**
     * Relacionados elegidos a mano, por posición en la lista (empieza en 1).
     *
     * @param  list<array<string, mixed>>  $posts
     */
    private function linkRelated(array $posts): void
    {
        foreach ($posts as $number => $post) {
            $from = $this->created[$number + 1] ?? null;

            foreach ($post['related'] as $target) {
                $to = $this->created[$target] ?? null;

                if ($from === null || $to === null || $from->id === $to->id) {
                    continue;
                }

                ContentRelated::query()->firstOrCreate(['content_id' => $from->id, 'content_related_id' => $to->id]);
            }

            if ($from !== null) {
                Content::markChanged($from->id);
            }
        }
    }

    /**
     * Ficheros de imagen que ya existen y se pueden reutilizar: de contenido,
     * de páginas y de SEO, con sus miniaturas hechas.
     */
    private function loadImagePools(): void
    {
        $images = fn (string $module) => File::query()
            ->where('module', $module)
            ->whereHas('fileType', fn ($query) => $query->where('type', 'image'))
            ->whereExists(fn ($query) => $query->selectRaw('1')->from('file_thumbnails')
                ->whereColumn('file_thumbnails.file_id', 'files.id')->where('file_thumbnails.key', 'large'))
            ->orderBy('id')
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->all();

        $this->imagePool = $images('content');
        $this->pageImagePool = $images('pages');
        $this->seoImagePool = $images('content_seo');
    }

    /**
     * @param  list<int>  $pool
     */
    private function pick(array $pool, int $seed): ?int
    {
        return $pool === [] ? null : $pool[$seed % count($pool)];
    }
}
