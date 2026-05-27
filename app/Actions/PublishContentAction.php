<?php

namespace App\Actions;

use App\Enums\ContentStatusEnum;
use App\Models\Content\Content;

class PublishContentAction
{
    public function execute(): int
    {
        return Content::where('status_id', ContentStatusEnum::Scheduled)
            ->where('published_at', '<=', now())
            ->update(['status_id' => ContentStatusEnum::Published]);
    }
}
