<?php

declare(strict_types=1);

namespace App\Models;

use App\Http\Traits\ImageTrait;
use App\Models\BaseModels\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int|null $image_id Relación con la imagen asociada
 * @property string $name Nombre de la tecnología.
 * @property string $slug Slug para el URL.
 * @property string|null $description Descripción breve de esta tecnología.
 * @property string $color Código Hexadecimal del color.
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 * @property-read string $url_image
 * @property-read string $url_image_large
 * @property-read string $url_image_medium
 * @property-read string $url_image_micro
 * @property-read string $url_image_normal
 * @property-read string $url_image_small
 * @property-read File|null $image
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Technology newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Technology newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Technology query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Technology whereColor($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Technology whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Technology whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Technology whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Technology whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Technology whereImageId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Technology whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Technology whereSlug($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Technology whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class Technology extends BaseModel
{
    use ImageTrait;

    protected $table = 'technologies';

    protected $fillable = ['name', 'slug', 'description', 'color', 'image_id'];

    /**
     * Asocia con la imagen de la tecnología.
     */
    public function image(): BelongsTo
    {
        return $this->belongsTo(File::class, 'image_id', 'id');
    }

    /**
     * Elimina de forma segura la instancia actual.
     */
    public function safeDelete(): bool
    {
        return (bool) $this->delete();
    }
}
