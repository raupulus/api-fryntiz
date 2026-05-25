<?php

namespace App\Services\Platform;

use App\Models\Platform;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

class PlatformService
{
    public function getAll(): Collection
    {
        return Cache::remember('platforms.all', 3600, function () {
            return Platform::with(['tags', 'categories'])->get();
        });
    }

    public function getBySlug(string $slug): ?Platform
    {
        return Platform::with(['tags', 'categories'])
            ->where('slug', $slug)
            ->first();
    }
}
