<?php

namespace App\Actions;

use App\Models\Content\Content;
use App\Enums\ContentStatusEnum;

class PublishContentAction
{
    public function execute(): int
    {
        return Content::where('status_id', ContentStatusEnum::Scheduled)
            ->where('published_at', '<=', now())
            ->update(['status_id' => ContentStatusEnum::Published]);
    }
}
