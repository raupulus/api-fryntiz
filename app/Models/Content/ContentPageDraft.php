<?php

declare(strict_types=1);

namespace App\Models\Content;

use App\Enums\ContentPageFormatEnum;
use App\Models\BaseModels\BaseModel;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Lo que un usuario tiene escrito en una página sin guardar (D1 y P4 de la
 * auditoría de contenidos del 2026-09-24). Sólo es de quien lo escribió. Quien
 * los guarda, recupera y poda es `ContentPageDraftService`.
 *
 * @property int $id
 * @property int $user_id
 * @property int $content_id
 * @property int|null $content_page_id Vacío en una página nueva.
 * @property ContentPageFormatEnum $format
 * @property string|null $title
 * @property string|null $slug
 * @property int|null $image_id
 * @property string $content
 * @property string $content_hash
 * @property Carbon|null $base_page_updated_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read ContentPage|null $page
 * @property-read Content|null $contentModel
 * @property-read User|null $user
 */
class ContentPageDraft extends BaseModel
{
    protected $table = 'content_page_drafts';

    protected $fillable = [
        'user_id',
        'content_id',
        'content_page_id',
        'format',
        'title',
        'slug',
        'image_id',
        'content',
        'content_hash',
        'base_page_updated_at',
    ];

    protected function casts(): array
    {
        return [
            'format' => ContentPageFormatEnum::class,
            'base_page_updated_at' => 'datetime',
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
     * @return BelongsTo<Content, $this>
     */
    public function contentModel(): BelongsTo
    {
        return $this->belongsTo(Content::class, 'content_id', 'id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
