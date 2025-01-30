<?php

namespace App\Helpers;

use App\Models\Content\Content;
use Illuminate\Support\Collection;

class ContentHelper
{
    public static function contentPrepare(Content $content): Object
    {
        return (Object) [
            'title' => $content->title,
            'slug' => $content->slug,
            'excerpt' => $content->excerpt,
            'is_featured' => $content->is_featured,
            'has_image' => (bool) $content->image_id,
            'urlImageSmall' => $content->urlImageSmall,
            'urlImageMedium' => $content->urlImageMedium,
            'urlImage' => $content->urlImageNormal,
            'created_at' => $content->created_at,
            'updated_at' => $content->updated_at,
            'created_at_human' => $content->created_at->translatedFormat('d F Y'),
            'total_pages' => $content->pages()->count(),
            'categories' => $content->categoriesQuery()->select(['categories.name', 'categories.slug'])->get(),
            'subcategories' => $content->subcategoriesQuery()->select(['categories.name', 'categories.slug', 'content_categories.is_main', 'parent_id'])->get()->map(function ($subcat) {
                $subcat->parent = $subcat->parentCategory?->slug;
                unset($subcat->parent_id);
                unset($subcat->parentCategory);

                return $subcat;
            }),
            'tags' => $content->tagsQuery()->pluck('name'),
            'metadata' => [
                'web' => $content->metadata?->web,
                'telegram_channel' => $content->metadata?->telegram_channel,
                'youtube_channel' => $content->metadata?->youtube_channel,
                'youtube_video' => $content->metadata?->youtube_video,
                'youtube_video_id' => $content->metadata?->youtube_video_id,
                'gitlab' => $content->metadata?->gitlab,
                'github' => $content->metadata?->github,
                'mastodon' => $content->metadata?->mastodon,
                'twitter' => $content->metadata?->urlTwitter,
            ],
            'technologies' => $content->technologies->map(function ($tech) {
                return [
                    'name' => $tech->name,
                    'slug' => $tech->slug,
                    'urlImageSmall' => $tech->urlImageSmall,
                ];
            }),
            'pages_slug' => $content->pages()->pluck('slug'),
        ];
    }

    public static function contentPrepareAll(Collection $contents): Collection
    {
        return $contents->map(function ($ele) {
            return self::contentPrepare($ele);
        });
    }

    /**
     * Procesa un contenido destacado para obtener solo los datos necesarios en una previsualización.
     *
     * @param Content $content
     * @return Content
     */
    public static function contentFeaturedPrepare(Content $content): Object
    {
        return (Object) [
            'title' => $content->title,
            'slug' => $content->slug,
            'excerpt' => $content->excerpt,
            'has_image' => (bool) $content->image_id,
            'urlImageSmall' => $content->urlImageSmall,
            'urlImageMedium' => $content->urlImageMedium,
            'published_at' => $content->published_at,
            'updated_at' => $content->updated_at,
            'categories' => $content->categoriesQuery()->select(['categories.name', 'categories.slug'])->get(),
            'subcategories' => $content->subcategoriesQuery()->select(['categories.name', 'categories.slug', 'content_categories.is_main', 'parent_id'])->get()->map(function ($subcat) {
                $subcat->parent = $subcat->parentCategory?->slug;
                unset($subcat->parent_id);
                unset($subcat->parentCategory);

                return $subcat;
            }),
        ];
    }

    public static function contentFeaturedPrepareAll(Collection $contents): Collection
    {
        return $contents->map(function ($ele) {
            return self::contentFeaturedPrepare($ele);
        });
    }
}
