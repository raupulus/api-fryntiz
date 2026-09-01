<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\BaseModels\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Class GalleryImage
 *
 * @property int $id
 * @property int|null $gallery_id FK a la galería a la que pertenece
 * @property int|null $image_id FK a la imagen en la tabla files
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 * @property-read Gallery|null $gallery
 * @property-read File|null $image
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GalleryImage newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GalleryImage newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GalleryImage query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GalleryImage whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GalleryImage whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GalleryImage whereGalleryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GalleryImage whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GalleryImage whereImageId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GalleryImage whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class GalleryImage extends BaseModel
{
    protected $table = 'gallery_images';

    /**
     * @var list<string> Campos que admiten asignación masiva.
     *
     * Lista explícita en lugar de `$guarded = ['id']`: con guarded, cualquier
     * columna nueva queda abierta a mass assignment el día que se añada, sin
     * que nadie tenga que decidirlo (SEC-08).
     */
    protected $fillable = [
        'gallery_id',
        'image_id',
    ];

    /**
     * Galería a la que pertenece esta imagen.
     */
    public function gallery(): BelongsTo
    {
        return $this->belongsTo(Gallery::class);
    }

    /**
     * Fichero de imagen asociado.
     */
    public function image(): BelongsTo
    {
        return $this->belongsTo(File::class, 'image_id');
    }
}
