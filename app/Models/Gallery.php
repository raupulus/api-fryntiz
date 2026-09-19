<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\GalleryAspectRatioEnum;
use App\Models\BaseModels\BaseModel;
use App\Models\Content\Content;
use App\Models\Content\ContentPage;
use App\Models\Hardware\HardwareComponent;
use App\Models\Hardware\HardwareDevice;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Carbon;

/**
 * Class Gallery
 *
 * Agrupación de imágenes reutilizable con relación de aspecto configurable,
 * asociable polimórficamente a Contenidos, Páginas de contenido, Hardware y otros modelos.
 *
 * @property int $id
 * @property int|null $user_id Usuario que crea la galería
 * @property int|null $image_id FK a la imagen de portada en la tabla files
 * @property string $name Nombre de la galería
 * @property string|null $description Descripción del contenido de la galería
 * @property GalleryAspectRatioEnum $aspect_ratio Proporción de aspecto requerida para las imágenes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User|null $user
 * @property-read File|null $image
 * @property-read Collection<int, GalleryImage> $images
 * @property-read int|null $images_count
 * @property-read Collection<int, Content> $contents
 * @property-read int|null $contents_count
 * @property-read Collection<int, ContentPage> $pages
 * @property-read int|null $pages_count
 * @property-read Collection<int, HardwareDevice> $hardwareDevices
 * @property-read int|null $hardware_devices_count
 * @property-read Collection<int, HardwareComponent> $hardwareComponents
 * @property-read int|null $hardware_components_count
 *
 * @method static \Database\Factories\GalleryFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Gallery newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Gallery newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Gallery query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Gallery whereAspectRatio($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Gallery whereCreatedAt($value)
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
    use HasFactory;

    protected $table = 'galleries';

    /**
     * @var list<string> Campos que admiten asignación masiva.
     */
    protected $fillable = [
        'user_id',
        'image_id',
        'name',
        'description',
        'aspect_ratio',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'aspect_ratio' => GalleryAspectRatioEnum::class,
        ];
    }

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
     * Imágenes que componen la galería, ordenadas correlativamente.
     *
     * @return HasMany<GalleryImage, $this>
     */
    public function images(): HasMany
    {
        return $this->hasMany(GalleryImage::class, 'gallery_id', 'id')
            ->orderBy('order', 'asc')
            ->orderBy('id', 'asc');
    }

    /**
     * Contenidos asociados a esta galería (relación polimórfica vía galleryables).
     *
     * @return MorphToMany<Content, $this>
     */
    public function contents(): MorphToMany
    {
        return $this->morphedByMany(Content::class, 'galleryable', 'galleryables')
            ->withPivot(['order'])
            ->orderByPivot('order')
            ->withTimestamps();
    }

    /**
     * Páginas de contenido asociadas a esta galería.
     *
     * @return MorphToMany<ContentPage, $this>
     */
    public function pages(): MorphToMany
    {
        return $this->morphedByMany(ContentPage::class, 'galleryable', 'galleryables')
            ->withPivot(['order'])
            ->orderByPivot('order')
            ->withTimestamps();
    }

    /**
     * Dispositivos de hardware asociados a esta galería.
     *
     * @return MorphToMany<HardwareDevice, $this>
     */
    public function hardwareDevices(): MorphToMany
    {
        return $this->morphedByMany(HardwareDevice::class, 'galleryable', 'galleryables')
            ->withPivot(['order'])
            ->orderByPivot('order')
            ->withTimestamps();
    }

    /**
     * Componentes de hardware asociados a esta galería.
     *
     * @return MorphToMany<HardwareComponent, $this>
     */
    public function hardwareComponents(): MorphToMany
    {
        return $this->morphedByMany(HardwareComponent::class, 'galleryable', 'galleryables')
            ->withPivot(['order'])
            ->orderByPivot('order')
            ->withTimestamps();
    }

    /**
     * Comprueba si una imagen concreta es la portada actual de la galería.
     */
    public function isCover(int $fileId): bool
    {
        return (int) $this->image_id === $fileId;
    }

    /**
     * Elimina la galería junto con sus imágenes y los ficheros asociados.
     */
    public function safeDelete(): bool
    {
        $this->loadMissing(['images.image', 'image']);

        foreach ($this->images as $galleryImage) {
            $galleryImage->safeDelete();
        }

        return parent::safeDelete();
    }
}
