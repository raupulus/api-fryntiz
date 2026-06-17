<?php

namespace App\Models\Content;

use App\Models\BaseModels\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Class ContentAvailableStatus
 *
 * @property int $id
 * @property int|null $file_id FK a la imagen en la tabla files
 * @property string $name Nombre del estado
 * @property string $slug Slug para el URL
 * @property string|null $description Descripción acerca del estado
 * @property string|null $icon Clase css para el icono
 * @property string $color Código Hexadecimal del color
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $deleted_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContentAvailableStatus newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContentAvailableStatus newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContentAvailableStatus query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContentAvailableStatus whereColor($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContentAvailableStatus whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContentAvailableStatus whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContentAvailableStatus whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContentAvailableStatus whereFileId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContentAvailableStatus whereIcon($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContentAvailableStatus whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContentAvailableStatus whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContentAvailableStatus whereSlug($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContentAvailableStatus whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class ContentAvailableStatus extends BaseModel
{
    use HasFactory;

    protected $table = 'content_available_status';
}
