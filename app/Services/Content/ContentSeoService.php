<?php

namespace App\Services\Content;

use App\Models\Content\Content;
use App\Models\Content\ContentSeo;

class ContentSeoService
{
    public function upsert(Content $content, array $data): ContentSeo
    {
        return ContentSeo::updateOrCreate(
            ['content_id' => $content->id],
            $data
        );
    }
}
