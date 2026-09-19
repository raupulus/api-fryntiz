<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\BaseModels\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Class GalleryImage
 *
 * Imagen individual dentro de una galería, con orden secuencial y pie de foto opcional.
 *
 * @property int $id
 * @property int|null $gallery_id FK a la galería a la que pertenece
 * @property int|null $image_id FK a la imagen en la tabla files
 * @property int $order Orden numérico de visualización
 * @property string|null $caption Pie de foto o descripción corta
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Gallery|null $gallery
 * @property-read File|null $image
 *
 * @method static \Database\Factories\GalleryImageFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GalleryImage newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GalleryImage newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GalleryImage query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GalleryImage whereCaption($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GalleryImage whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GalleryImage whereGalleryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GalleryImage whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GalleryImage whereImageId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GalleryImage whereOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GalleryImage whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class GalleryImage extends BaseModel
{
    use HasFactory;

    protected $table = 'gallery_images';

    /**
     * @var list<string> Campos que admiten asignación masiva.
     */
    protected $fillable = [
        'gallery_id',
        'image_id',
        'order',
        'caption',
    ];

    /**
     * @var list<string> Relaciones que se cargan siempre por defecto.
     */
    protected $with = [
        'image.fileType',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'order' => 'integer',
        ];
    }

    /**
     * Galería a la que pertenece esta imagen.
     *
     * @return BelongsTo<Gallery, $this>
     */
    public function gallery(): BelongsTo
    {
        return $this->belongsTo(Gallery::class);
    }

    /**
     * Fichero de imagen asociado.
     *
     * @return BelongsTo<File, $this>
     */
    public function image(): BelongsTo
    {
        return $this->belongsTo(File::class, 'image_id');
    }

    /**
     * Elimina el registro y limpia el fichero físico asociado en files.
     */
    public function safeDelete(): bool
    {
        $this->loadMissing(['image', 'gallery']);

        $fileId = $this->image_id;
        $isCover = $fileId && $this->gallery?->image_id === $fileId;

        if ($isCover) {
            return (bool) $this->delete();
        }

        return parent::safeDelete();
    }
}
