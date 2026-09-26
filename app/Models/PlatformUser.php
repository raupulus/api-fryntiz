<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\BaseModels\BaseModel;
use App\Services\Content\ContentContributorService;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Plataforma asignada a un Editor (`platform_user`).
 *
 * Un Editor crea contenidos sólo en sus plataformas. Con `auto_contributor`
 * entra además como colaborador en todos los contenidos de la plataforma (ver
 * `ContentContributorService`).
 *
 * @property int $id
 * @property int $user_id
 * @property int $platform_id
 * @property bool $auto_contributor
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User|null $user
 * @property-read Platform|null $platform
 */
class PlatformUser extends BaseModel
{
    protected $table = 'platform_user';

    protected $fillable = [
        'user_id',
        'platform_id',
        'auto_contributor',
    ];

    protected $casts = [
        'auto_contributor' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        // Al activar el colaborador automático (o asignar la plataforma ya con
        // él), el editor entra en los contenidos que ya existen, salvo en los
        // que se le quitó a mano. Desactivarlo no le saca de ninguno.
        static::saved(function (PlatformUser $assignment): void {
            if ($assignment->auto_contributor && ($assignment->wasRecentlyCreated || $assignment->wasChanged('auto_contributor'))) {
                app(ContentContributorService::class)->applyToExistingContents($assignment);
            }
        });
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    /**
     * @return BelongsTo<Platform, $this>
     */
    public function platform(): BelongsTo
    {
        return $this->belongsTo(Platform::class, 'platform_id', 'id');
    }
}
