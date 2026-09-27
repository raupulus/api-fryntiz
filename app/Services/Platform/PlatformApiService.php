<?php

declare(strict_types=1);

namespace App\Services\Platform;

use App\Http\Resources\V2\Content\ContentRelatedResource;
use App\Http\Resources\V2\SocialImageResource;
use App\Http\Resources\V2\TechnologyResource;
use App\Models\Content\Content;
use App\Models\Content\ContentAvailableType;
use App\Models\Platform;
use App\Models\Tag;
use App\Models\Technology;
use App\Support\ApiCacheVersion;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * La ficha completa de una plataforma (P7 de la auditoría de contenidos; F9
 * del plan del 2026-09-24): lo que la v1 daba en `/v1/platform/{p}/info` y el
 * frontend usa como primera carga y para los metadatos del SSR.
 *
 * Nunca envía las columnas `*_token` de la plataforma.
 */
class PlatformApiService
{
    private const CACHE_HOURS = 2;

    /**
     * @return array<string, mixed>
     */
    public function detail(Platform $platform, Request $request): array
    {
        $key = sprintf('api:platform:%d:%d:%s', $platform->id, ApiCacheVersion::current(), now()->format('YmdH'));

        return Cache::remember($key, now()->addHours(self::CACHE_HOURS), function () use ($platform, $request): array {
            $platform->loadMissing(['image.fileType', 'image.thumbnails', 'user.details', 'user.socials.socialNetwork']);

            return [
                'id' => $platform->id,
                // `name` es la clave que consumen las webs; la columna es `title`.
                'name' => $platform->title,
                'title' => $platform->title,
                'slug' => $platform->slug,
                'description' => $platform->description,
                'domain' => $platform->domain,
                'url_about' => $platform->url_about,
                'image' => $platform->image === null ? null : (new SocialImageResource($platform->image))->resolve($request),
                'social_networks' => [
                    'youtube_channel_id' => $platform->youtube_channel_id,
                    'youtube_presentation_video_id' => $platform->youtube_presentation_video_id,
                    'twitter' => $platform->twitter,
                    'mastodon' => $platform->mastodon,
                    'twitch' => $platform->twitch,
                    'tiktok' => $platform->tiktok,
                    'instagram' => $platform->instagram,
                ],
                'author' => $platform->user?->basicInfo(),
                'technologies' => TechnologyResource::collection($this->projectTechnologies($platform))->resolve($request),
                'contents' => $this->contentCounts($platform),
                'pages' => ContentRelatedResource::collection(
                    Content::query()
                        ->with(['type', 'image.fileType', 'image.thumbnails'])
                        ->published()
                        ->where('platform_id', $platform->id)
                        ->whereHas('type', fn ($type) => $type->where('slug', 'page'))
                        ->orderBy('title')
                        ->get()
                )->resolve($request),
                'created_at' => $platform->created_at?->toISOString(),
            ];
        });
    }

    /**
     * Etiquetas de la plataforma, con cuántos contenidos publicados las usan.
     *
     * @return list<array<string, mixed>>
     */
    public function tags(Platform $platform): array
    {
        $published = Content::query()->published()->where('platform_id', $platform->id)->select('id');

        return Tag::query()
            ->join('platform_tags', 'platform_tags.tag_id', '=', 'tags.id')
            ->where('platform_tags.platform_id', $platform->id)
            ->select(['tags.id', 'tags.slug', 'tags.name', 'tags.color'])
            ->selectSub(
                DB::table('content_tags')
                    ->whereColumn('content_tags.platform_tag_id', 'platform_tags.id')
                    ->whereNull('content_tags.deleted_at')
                    ->whereIn('content_tags.content_id', $published)
                    ->selectRaw('count(*)'),
                'contents_count',
            )
            ->orderBy('tags.name')
            ->get()
            ->map(fn (Tag $tag): array => [
                'id' => $tag->id,
                'slug' => $tag->slug,
                'name' => $tag->name,
                'color' => $tag->color,
                'contents_count' => (int) $tag->getAttribute('contents_count'),
            ])
            ->all();
    }

    /**
     * Las tecnologías de sus proyectos publicados, como en `main`.
     *
     * @return Collection<int, Technology>
     */
    private function projectTechnologies(Platform $platform): Collection
    {
        return Technology::query()
            ->with(['image.fileType', 'image.thumbnails'])
            ->whereIn('id', DB::table('content_technologies')
                ->join('contents', 'contents.id', '=', 'content_technologies.content_id')
                ->join('content_available_types', 'content_available_types.id', '=', 'contents.type_id')
                ->whereNull('content_technologies.deleted_at')
                ->whereIn('contents.id', Content::query()->published()->where('platform_id', $platform->id)->select('id'))
                ->where('content_available_types.slug', 'project')
                ->select('content_technologies.technology_id'))
            ->orderBy('name')
            ->get();
    }

    /**
     * Cuántos contenidos publicados tiene, en total y por tipo.
     *
     * @return array{total: int, types: list<array<string, mixed>>}
     */
    private function contentCounts(Platform $platform): array
    {
        $counts = Content::query()
            ->published()
            ->where('platform_id', $platform->id)
            ->selectRaw('type_id, count(*) as total')
            ->groupBy('type_id')
            ->pluck('total', 'type_id');

        $types = ContentAvailableType::query()->whereIn('id', $counts->keys())->orderBy('id')->get();

        return [
            'total' => (int) $counts->sum(),
            'types' => $types->map(fn (ContentAvailableType $type): array => [
                'id' => $type->id,
                'slug' => $type->slug,
                'name' => $type->name,
                'plural_name' => $type->plural_name,
                'description' => $type->description,
                'total' => (int) ($counts[$type->id] ?? 0),
            ])->values()->all(),
        ];
    }
}
