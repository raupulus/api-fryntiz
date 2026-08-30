<?php

declare(strict_types=1);

namespace App\Models\Content;

use App\Models\BaseModels\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * Class ContentGallery
 *
 * @property int $id
 * @property int|null $file_id FK al al archivo
 * @property int|null $content_id FK al contenido que se asocia con el archivo
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContentFile newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContentFile newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContentFile query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContentFile whereContentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContentFile whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContentFile whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContentFile whereFileId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContentFile whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContentFile whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class ContentFile extends BaseModel
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'content_files';

    protected $fillable = [
        'content_id',
        'file_id',
    ];
}
