<?php

declare(strict_types=1);

namespace App\Models\Referred;

use App\Models\BaseModels\BaseModel;
use App\Models\File;
use Database\Factories\Referred\ReferredPlatformFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Plataforma de programas de afiliación (Amazon, AliExpress, etc.).
 *
 * @property int $id
 * @property int|null $image_id Relación con la imagen/logo asociada
 * @property string $name Nombre de la plataforma para afiliados
 * @property string $slug Identificador amigable para URLs
 * @property string|null $description Descripción de la plataforma
 * @property string|null $url Enlace a la página principal
 * @property string|null $url_panel Enlace al panel de control de referidos
 * @property string|null $url_register Enlace a la página de registro
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read File|null $image
 * @property-read Collection<int, ReferredThing> $things
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReferredPlatform newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReferredPlatform newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReferredPlatform query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReferredPlatform whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReferredPlatform whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReferredPlatform whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReferredPlatform whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReferredPlatform whereImageId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReferredPlatform whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReferredPlatform whereSlug($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReferredPlatform whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReferredPlatform whereUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReferredPlatform whereUrlPanel($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReferredPlatform whereUrlRegister($value)
 *
 * @mixin \Eloquent
 */
class ReferredPlatform extends BaseModel
{
    /** @use HasFactory<ReferredPlatformFactory> */
    use HasFactory;

    use SoftDeletes;

    protected $table = 'referred_platforms';

    protected $fillable = [
        'image_id',
        'name',
        'slug',
        'description',
        'url',
        'url_panel',
        'url_register',
    ];

    protected static function booted(): void
    {
        static::creating(function (ReferredPlatform $platform): void {
            if (blank($platform->slug)) {
                $platform->slug = Str::slug($platform->name);
            }
        });
    }

    protected static function newFactory(): ReferredPlatformFactory
    {
        return ReferredPlatformFactory::new();
    }

    /**
     * Enlaces de compra asociados a esta plataforma.
     *
     * @return HasMany<ReferredThing, $this>
     */
    public function things(): HasMany
    {
        return $this->hasMany(ReferredThing::class, 'referred_platform_id');
    }

    /**
     * Imagen / Logo de la plataforma.
     *
     * @return BelongsTo<File, $this>
     */
    public function image(): BelongsTo
    {
        return $this->belongsTo(File::class, 'image_id');
    }
}
