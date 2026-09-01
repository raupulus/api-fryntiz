<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\BaseModels\BaseModel;
use App\Models\Content\Content;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Class Gallery
 *
 * Agrupación de imágenes reutilizable, asociable a uno o varios Content.
 *
 * @property int $id
 * @property int|null $user_id Usuario que crea la galería
 * @property int|null $image_id FK a la imagen de portada en la tabla files
 * @property string $name Nombre de la galería
 * @property string|null $description Descripción del contenido de la galería
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 * @property-read User|null $user
 * @property-read File|null $image
 * @property-read Collection<int, GalleryImage> $images
 * @property-read int|null $images_count
 * @property-read Collection<int, Content> $contents
 * @property-read int|null $contents_count
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Gallery newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Gallery newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Gallery query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Gallery whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Gallery whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Gallery whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Gallery whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Gallery whereImageId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Gallery whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Gallery whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Gallery whereUserId($value)
 *
 * @mixin \Eloquent
 */
class Gallery extends BaseModel
{
    protected $table = 'galleries';

    /**
     * @var list<string> Campos que admiten asignación masiva.
     *
     * Lista explícita en lugar de `$guarded = ['id']`: con guarded, cualquier
     * columna nueva queda abierta a mass assignment el día que se añada, sin
     * que nadie tenga que decidirlo (SEC-08).
     */
    protected $fillable = [
        'user_id',
        'image_id',
        'name',
        'description',
    ];

    /**
     * Usuario que subió/creó la galería.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Imagen de portada de la galería.
     */
    public function image(): BelongsTo
    {
        return $this->belongsTo(File::class, 'image_id');
    }

    /**
     * Imágenes que componen la galería.
     */
    public function images(): HasMany
    {
        return $this->hasMany(GalleryImage::class, 'gallery_id', 'id');
    }

    /**
     * Contenidos que usan esta galería. Inversa de Content::galleries().
     */
    public function contents(): BelongsToMany
    {
        return $this->belongsToMany(Content::class, 'content_galleries', 'gallery_id', 'content_id');
    }

    /**
     * Elimina la galería junto con sus imágenes y los ficheros asociados.
     */
    public function safeDelete(): bool
    {
        foreach ($this->images as $galleryImage) {
            $galleryImage->safeDelete();
        }

        return parent::safeDelete();
    }
}
