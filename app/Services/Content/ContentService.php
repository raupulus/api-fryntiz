<?php

declare(strict_types=1);

namespace App\Services\Content;

use App\Models\Content\Content;
use App\Models\Platform;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class ContentService
{
    public function getBySlug(string $platformSlug, string $contentSlug): ?Content
    {
        return Content::with(['pages', 'seo', 'metadata', 'technologies'])
            ->whereHas('platform', fn ($q) => $q->where('slug', $platformSlug))
            ->where('slug', $contentSlug)
            ->published()
            ->first();
    }

    public function getFeaturedForPlatform(Platform $platform, int $limit = 10): Collection
    {
        return Content::with(['seo', 'metadata'])
            ->forPlatform($platform->id)
            ->published()
            ->featured()
            ->orderByDesc('published_at')
            ->limit($limit)
            ->get();
    }

    public function getByTypeForPlatform(Platform $platform, int $typeId, int $perPage = 15): LengthAwarePaginator
    {
        return Content::with(['seo'])
            ->forPlatform($platform->id)
            ->ofType($typeId)
            ->published()
            ->orderByDesc('published_at')
            ->paginate($perPage);
    }

    public function getRelated(Content $content, int $limit = 5): Collection
    {
        return Content::with(['seo'])
            ->where('id', '!=', $content->id)
            ->forPlatform($content->platform_id)
            ->ofType($content->type_id)
            ->published()
            ->orderByDesc('published_at')
            ->limit($limit)
            ->get();
    }

    public function publishScheduled(): int
    {
        return Content::scheduled()
            ->where('published_at', '<=', now())
            ->update(['status_id' => 2]);
    }
}
