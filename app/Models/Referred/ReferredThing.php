<?php

declare(strict_types=1);

namespace App\Models\Referred;

use App\Models\BaseModels\BaseModel;
use App\Models\File;
use App\Models\Hardware\HardwareComponent;
use App\Models\Hardware\HardwareDevice;
use Database\Factories\Referred\ReferredThingFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * Enlace de compra de afiliado asociado a un dispositivo hardware o a un componente.
 *
 * @property int $id
 * @property int $referred_platform_id Plataforma de afiliados asociada
 * @property int $hardware_device_id Dispositivo hardware al que pertenece
 * @property int|null $hardware_component_id Componente específico del dispositivo (null = dispositivo completo)
 * @property int|null $image_id Imagen asociada al enlace
 * @property string|null $name Título o nota opcional del enlace (ej: Pack con accesorios)
 * @property string|null $description Descripción adicional
 * @property string $url URL de compra afiliada
 * @property float|null $price Precio orientativo
 * @property string $currency Código de moneda ISO (EUR)
 * @property bool $is_active Si el enlace está activo
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read ReferredPlatform $platform
 * @property-read HardwareDevice $device
 * @property-read HardwareComponent|null $component
 * @property-read File|null $image
 * @property-read string $target_label Etiqueta descriptiva del destino (Dispositivo completo o nombre del componente)
 * @property-read string|null $formatted_price Precio formateado con moneda
 *
 * @method static Builder<static>|ReferredThing newModelQuery()
 * @method static Builder<static>|ReferredThing newQuery()
 * @method static Builder<static>|ReferredThing query()
 * @method static Builder<static>|ReferredThing active()
 * @method static Builder<static>|ReferredThing forDeviceOnly()
 * @method static Builder<static>|ReferredThing forComponents()
 * @method static Builder<static>|ReferredThing whereCreatedAt($value)
 * @method static Builder<static>|ReferredThing whereCurrency($value)
 * @method static Builder<static>|ReferredThing whereDeletedAt($value)
 * @method static Builder<static>|ReferredThing whereDescription($value)
 * @method static Builder<static>|ReferredThing whereHardwareComponentId($value)
 * @method static Builder<static>|ReferredThing whereHardwareDeviceId($value)
 * @method static Builder<static>|ReferredThing whereId($value)
 * @method static Builder<static>|ReferredThing whereImageId($value)
 * @method static Builder<static>|ReferredThing whereIsActive($value)
 * @method static Builder<static>|ReferredThing whereName($value)
 * @method static Builder<static>|ReferredThing wherePrice($value)
 * @method static Builder<static>|ReferredThing whereReferredPlatformId($value)
 * @method static Builder<static>|ReferredThing whereUpdatedAt($value)
 * @method static Builder<static>|ReferredThing whereUrl($value)
 *
 * @mixin \Eloquent
 */
class ReferredThing extends BaseModel
{
    /** @use HasFactory<ReferredThingFactory> */
    use HasFactory;

    use SoftDeletes;

    protected $table = 'referred_things';

    protected $fillable = [
        'referred_platform_id',
        'hardware_device_id',
        'hardware_component_id',
        'image_id',
        'name',
        'description',
        'url',
        'price',
        'currency',
        'is_active',
    ];

    protected $casts = [
        'price' => 'float',
        'is_active' => 'boolean',
    ];

    protected static function newFactory(): ReferredThingFactory
    {
        return ReferredThingFactory::new();
    }

    /**
     * Plataforma de afiliación a la que pertenece el enlace.
     *
     * @return BelongsTo<ReferredPlatform, $this>
     */
    public function platform(): BelongsTo
    {
        return $this->belongsTo(ReferredPlatform::class, 'referred_platform_id');
    }

    /**
     * Dispositivo hardware al que pertenece este enlace.
     *
     * @return BelongsTo<HardwareDevice, $this>
     */
    public function device(): BelongsTo
    {
        return $this->belongsTo(HardwareDevice::class, 'hardware_device_id');
    }

    /**
     * Componente específico al que aplica el enlace (null si aplica al dispositivo completo).
     *
     * @return BelongsTo<HardwareComponent, $this>
     */
    public function component(): BelongsTo
    {
        return $this->belongsTo(HardwareComponent::class, 'hardware_component_id');
    }

    /**
     * Imagen asociada al producto o enlace de compra.
     *
     * @return BelongsTo<File, $this>
     */
    public function image(): BelongsTo
    {
        return $this->belongsTo(File::class, 'image_id');
    }

    /**
     * Filtra solo los enlaces marcados como activos.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Filtra solo los enlaces aplicables al dispositivo completo (sin componente específico).
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeForDeviceOnly(Builder $query): Builder
    {
        return $query->whereNull('hardware_component_id');
    }

    /**
     * Filtra solo los enlaces asociados a componentes específicos.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeForComponents(Builder $query): Builder
    {
        return $query->whereNotNull('hardware_component_id');
    }

    /**
     * Etiqueta amigable que identifica el destino del enlace de compra.
     */
    public function getTargetLabelAttribute(): string
    {
        if ($this->hardware_component_id !== null) {
            return $this->component->name ?? 'Componente #'.$this->hardware_component_id;
        }

        return 'Dispositivo completo';
    }

    /**
     * Precio formateado para visualización amigable.
     */
    public function getFormattedPriceAttribute(): ?string
    {
        if ($this->price === null) {
            return null;
        }

        return number_format((float) $this->price, 2, ',', '.').' '.$this->currency;
    }
}
