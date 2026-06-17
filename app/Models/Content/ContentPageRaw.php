<?php

declare(strict_types=1);

namespace App\Models\Content;

use App\Models\BaseModels\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Carbon;

/**
 * Class ContentRelated
 *
 * @property int $id
 * @property int|null $content_page_id FK a la página.
 * @property int|null $available_page_raw_id FK al tipo de contenido y su formato.
 * @property string|null $content Contenido de la página en este formato.
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContentPageRaw newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContentPageRaw newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContentPageRaw query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContentPageRaw whereAvailablePageRawId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContentPageRaw whereContent($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContentPageRaw whereContentPageId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContentPageRaw whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContentPageRaw whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContentPageRaw whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContentPageRaw whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class ContentPageRaw extends BaseModel
{
    use HasFactory;

    protected $table = 'content_page_raw';

    protected $fillable = [
        'content_page_id',
        'available_page_raw_id',
        'content',
    ];
}
