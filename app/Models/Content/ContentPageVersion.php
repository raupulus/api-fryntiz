<?php

declare(strict_types=1);

namespace App\Models\Content;

use App\Enums\ContentPageFormatEnum;
use App\Enums\ContentPageVersionReasonEnum;
use App\Models\BaseModels\BaseModel;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Una versión anterior de una página: lo que había antes de un cambio (G5 de
 * la auditoría de contenidos del 2026-09-24). Quien las crea y las poda es
 * `ContentPageHistoryService`.
 *
 * @property int $id
 * @property int $content_page_id
 * @property int|null $user_id Quién guardó el cambio que la dejó aquí.
 * @property ContentPageFormatEnum $format
 * @property string|null $title
 * @property string $content
 * @property string $content_hash
 * @property ContentPageVersionReasonEnum $reason
 * @property Carbon|null $created_at
 * @property-read ContentPage|null $page
 * @property-read User|null $user
 */
class ContentPageVersion extends BaseModel
{
    public const UPDATED_AT = null;

    protected $table = 'content_page_versions';

    protected $fillable = [
        'content_page_id',
        'user_id',
        'format',
        'title',
        'content',
        'content_hash',
        'reason',
    ];

    protected function casts(): array
    {
        return [
            'format' => ContentPageFormatEnum::class,
            'reason' => ContentPageVersionReasonEnum::class,
        ];
    }

    /**
     * @return BelongsTo<ContentPage, $this>
     */
    public function page(): BelongsTo
    {
        return $this->belongsTo(ContentPage::class, 'content_page_id', 'id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
