<?php

declare(strict_types=1);

namespace App\Models;

use App\Http\Traits\ImageTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name Nombre de la red social
 * @property string $slug Slug para la red social
 * @property string $type Tipo de red social
 * @property string $color Código Hexadecimal del color primario de la red social
 * @property string $url Url a la página principal de la red social
 * @property string|null $url_user Parte de la url hacia el perfil de usuario
 * @property string|null $url_privacity Url a la política de privacidad de la red social
 * @property string|null $icon Icono para la red social
 * @property string|null $image Imagen de la red social a 120x120px
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SocialNetwork newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SocialNetwork newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SocialNetwork query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SocialNetwork whereColor($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SocialNetwork whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SocialNetwork whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SocialNetwork whereIcon($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SocialNetwork whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SocialNetwork whereImage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SocialNetwork whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SocialNetwork whereSlug($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SocialNetwork whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SocialNetwork whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SocialNetwork whereUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SocialNetwork whereUrlPrivacity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SocialNetwork whereUrlUser($value)
 *
 * @mixin \Eloquent
 */
class SocialNetwork extends Model
{
    // use ImageTrait;

    protected $table = 'social_networks';

    protected $fillable = [
        'name',
        'slug',
        'type',
        'color',
        'url',
        'url_user',
        'url_privacity',
        'icon',
        'image',
    ];

    /**
    public function image(): BelongsTo
    {
        return $this->belongsTo(File::class, 'file_id', 'id');
    }
     */
}
