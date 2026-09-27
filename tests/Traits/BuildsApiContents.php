<?php

declare(strict_types=1);

namespace Tests\Traits;

use App\Enums\ContentPageFormatEnum;
use App\Enums\UserRoleEnum;
use App\Models\Category;
use App\Models\Content\Content;
use App\Models\Content\ContentAvailableType;
use App\Models\Content\ContentCategory;
use App\Models\Content\ContentFile;
use App\Models\Content\ContentMetadata;
use App\Models\Content\ContentPage;
use App\Models\Content\ContentSeo;
use App\Models\Content\ContentTag;
use App\Models\File;
use App\Models\Gallery;
use App\Models\GalleryImage;
use App\Models\Platform;
use App\Models\PlatformCategory;
use App\Models\PlatformTag;
use App\Models\Tag;
use App\Models\Technology;
use App\Models\User;
use App\Services\Content\ContentContributorService;
use App\Services\Content\ContentFileService;
use App\Services\Content\ContentPageFormatService;
use Database\Seeders\ContentAvailablePageRawSeeder;
use Database\Seeders\ContentAvailableTypesSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

/**
 * Contenidos publicados con todas sus partes, para los tests de la API de
 * contenidos (F9 del plan del 2026-09-24).
 *
 * Necesita `SeedsProductionContentStatuses` y `UsesTemporaryStorage`.
 */
trait BuildsApiContents
{
    protected User $admin;

    protected Platform $platform;

    protected function prepareApiContents(): void
    {
        $this->seedContentStatusesAsProduction();
        (new ContentAvailableTypesSeeder)->run();
        (new ContentAvailablePageRawSeeder)->run();
        $this->useTemporaryStorage();

        $this->admin = User::factory()->create(['role_id' => UserRoleEnum::Admin->value, 'is_active' => true]);
        $this->platform = Platform::factory()->create(['user_id' => $this->admin->id]);
    }

    protected function typeId(string $slug): int
    {
        return (int) ContentAvailableType::query()->where('slug', $slug)->value('id');
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    protected function published(array $attributes = []): Content
    {
        return Content::factory()->create([
            'platform_id' => $this->platform->id,
            'author_id' => $this->admin->id,
            'type_id' => $this->typeId('blog'),
            ...$attributes,
        ]);
    }

    protected function addPage(Content $content, int $order, string $title, string $html): ContentPage
    {
        $page = ContentPage::query()->create([
            'content_id' => $content->id,
            'title' => $title,
            'slug' => str($title)->slug()->value(),
            'order' => $order,
        ]);

        app(ContentPageFormatService::class)->save($page, ContentPageFormatEnum::Html, $html);

        return $page->refresh();
    }

    /**
     * Una imagen de verdad subida al contenido, con sus miniaturas.
     */
    protected function upload(Content $content, string $name = 'foto.jpg'): File
    {
        $this->actingAs($this->admin);
        $payload = app(ContentFileService::class)->store($content, UploadedFile::fake()->image($name, 800, 600));

        return File::query()->findOrFail($payload['file_id']);
    }

    protected function category(string $name, ?Category $parent = null): Category
    {
        $category = Category::query()->create([
            'name' => $name,
            'slug' => str($name)->slug()->value(),
            'parent_id' => $parent?->id,
            'color' => '#123456',
            'icon' => 'category',
            'priority' => 0,
        ]);

        PlatformCategory::query()->create(['platform_id' => $this->platform->id, 'category_id' => $category->id]);

        return $category;
    }

    protected function tag(string $name): Tag
    {
        $tag = Tag::query()->create(['name' => $name, 'slug' => str($name)->slug()->value(), 'color' => '#654321']);
        PlatformTag::query()->create(['platform_id' => $this->platform->id, 'tag_id' => $tag->id]);

        return $tag;
    }

    protected function technology(string $name): Technology
    {
        return Technology::query()->create(['name' => $name, 'slug' => str($name)->slug()->value(), 'color' => '#abcdef']);
    }

    protected function linkCategory(Content $content, Category $category, bool $main = false): ContentCategory
    {
        return ContentCategory::query()->create([
            'content_id' => $content->id,
            'platform_category_id' => PlatformCategory::query()->where('platform_id', $this->platform->id)->where('category_id', $category->id)->value('id'),
            'is_main' => $main,
        ]);
    }

    protected function linkTag(Content $content, Tag $tag): ContentTag
    {
        return ContentTag::query()->create([
            'content_id' => $content->id,
            'platform_tag_id' => PlatformTag::query()->where('platform_id', $this->platform->id)->where('tag_id', $tag->id)->value('id'),
        ]);
    }

    protected function linkRelated(Content $content, Content $related, bool $deleted = false): void
    {
        DB::table('content_related')->insert([
            'content_id' => $content->id,
            'content_related_id' => $related->id,
            'created_at' => now(),
            'updated_at' => now(),
            'deleted_at' => $deleted ? now() : null,
        ]);
    }

    protected function gallery(string $name, int $photos = 2): Gallery
    {
        $gallery = Gallery::factory()->create(['user_id' => $this->admin->id, 'name' => $name]);

        foreach (range(1, $photos) as $order) {
            GalleryImage::query()->create([
                'gallery_id' => $gallery->id,
                'image_id' => $this->photo("foto-{$gallery->id}-{$order}.jpg")->id,
                'order' => $photos - $order + 1,
                'caption' => "Foto {$order}",
            ]);
        }

        return $gallery;
    }

    /**
     * Una foto de galería, sin fichero en el disco (la factoría la crea sin
     * `alt` ni `title`, que la base no admite).
     */
    protected function photo(string $name): File
    {
        return File::query()->create([
            'module' => 'galleries',
            'path' => 'galleries',
            'storage_path' => 'public/galleries',
            'name' => $name,
            'original_name' => $name,
            'alt' => $name,
            'title' => $name,
            'width' => 1600,
            'height' => 900,
            'size' => 2048,
            'is_private' => false,
        ]);
    }

    protected function editor(string $name): User
    {
        return User::factory()->create(['role_id' => UserRoleEnum::Editor->value, 'is_active' => true, 'name' => $name]);
    }

    /**
     * Un contenido con todo: tres páginas, SEO, metadatos, categoría principal
     * y subcategoría, etiqueta, tecnología, colaborador, galería, fichero y
     * relacionado; y además un pivote borrado de cada tipo, que no debe salir.
     *
     * @return array<string, mixed>
     */
    protected function richContent(): array
    {
        $content = $this->published(['title' => 'Estación meteorológica', 'slug' => 'estacion', 'excerpt' => 'Una estación casera']);

        $pages = [
            $this->addPage($content, 1, 'Introducción', '<p>Texto de la primera página</p>'),
            $this->addPage($content, 2, 'Montaje', '<p>Texto de la segunda página</p>'),
            $this->addPage($content, 3, 'Resultados', '<p>Texto de la tercera página</p>'),
        ];

        $seo = ContentSeo::query()->create(['content_id' => $content->id, 'og_title' => 'Para compartir', 'description' => 'Para buscadores', 'keywords' => 'clima']);
        $metadata = ContentMetadata::query()->create(['content_id' => $content->id, 'github' => 'https://github.com/raupulus/estacion']);

        $weather = $this->category('Meteorología');
        $sensors = $this->category('Sensores', $weather);
        $gone = $this->category('Borrada');
        $this->linkCategory($content, $weather, main: true);
        $this->linkCategory($content, $sensors);
        $this->linkCategory($content, $gone)->delete();

        $raspberry = $this->tag('Raspberry');
        $oldTag = $this->tag('Vieja');
        $this->linkTag($content, $raspberry);
        $this->linkTag($content, $oldTag)->delete();

        $php = $this->technology('PHP');
        $content->technologies()->attach($php->id);

        $contributor = $this->editor('Colaboradora');
        app(ContentContributorService::class)->add($content, $contributor);

        $gallery = $this->gallery('Montaje en fotos');
        $content->galleries()->attach($gallery->id, ['order' => 1]);

        $file = $this->upload($content);
        $unused = $this->upload($content, 'vieja.jpg');
        // Portada con miniaturas: así las consultas de la imagen cuentan.
        $content->update(['image_id' => $file->id]);
        ContentFile::query()->where('file_id', $unused->id)->update(['unused_since' => now()]);

        // De otro tipo: sólo sale por estar elegido, no al completar.
        $related = $this->published(['title' => 'Otro proyecto', 'slug' => 'otro', 'type_id' => $this->typeId('project')]);
        $related->update(['image_id' => $this->upload($related, 'otra.jpg')->id]);
        $this->linkRelated($content, $related);

        return compact('content', 'pages', 'seo', 'metadata', 'weather', 'sensors', 'raspberry', 'php', 'contributor', 'gallery', 'file', 'unused', 'related');
    }

    protected function contentUrl(Content $content, string $suffix = ''): string
    {
        return "/api/v2/platforms/{$this->platform->slug}/contents/{$content->slug}{$suffix}";
    }
}
