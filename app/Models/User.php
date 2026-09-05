<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\UserRoleEnum;
use App\Http\Traits\ImageTrait;
use App\Models\Content\Content;
use Carbon\Carbon;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Notifications\DatabaseNotificationCollection;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Sanctum\HasApiTokens;

use function asset;

/**
 * Class User
 *
 * @property int $id
 * @property int $role_id Role principal del usuario, aunque pueda tener otros roles extras
 * @property bool $is_active Cuenta activa. Distinto de deleted_at (borrado lógico).
 * @property int|null $current_team_id Identificador del equipo al que pertenece.
 * @property string $name Nombre del usuario
 * @property string|null $surname Apellidos del usuario
 * @property string|null $nickname Apodo del usuario, ha de ser único para permitir el login en la aplicación
 * @property string|null $profile_photo_path
 * @property string $email Email del usuario, ha de ser único para permitir el login en la aplicación
 * @property \Illuminate\Support\Carbon|null $email_verified_at Momento en el que ha verificado el email
 * @property string $password Contraseña del usuario cifrada.
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property string|null $remember_token
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read UserDetail|null $details
 * @property-read UserSocial|null $facebook
 * @property-read string $full_name
 * @property-read mixed $profile_photo_url
 * @property-read UserSocial|null $twitter
 * @property-read string $url_image
 * @property-read string $url_image_large
 * @property-read string $url_image_medium
 * @property-read string $url_image_micro
 * @property-read string $url_image_normal
 * @property-read string $url_image_small
 * @property-read DatabaseNotificationCollection<int, DatabaseNotification> $notifications
 * @property-read int|null $notifications_count
 * @property-read UserRole $role
 * @property-read UserSetting|null $settings
 * @property-read Collection<int, Platform> $platforms
 * @property-read Collection<int, UserSocial> $socials
 * @property-read int|null $socials_count
 * @property-read Collection<int, ApiToken> $tokens
 * @property-read int|null $tokens_count
 *
 * @method static \Database\Factories\UserFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereCurrentTeamId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmailVerifiedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereNickname($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User wherePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereProfilePhotoPath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereRememberToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereRoleId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereSurname($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereTwoFactorRecoveryCodes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereTwoFactorSecret($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User withoutTrashed()
 *
 * @mixin \Eloquent
 */
class User extends Authenticatable implements FilamentUser
{
    use HasApiTokens;
    use HasFactory;
    use ImageTrait;
    use Notifiable;
    use SoftDeletes;
    use TwoFactorAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'role_id',
        'is_active',
        'name',
        'surname',
        'nickname',
        'email',
        'password',
        'profile_photo_path',
    ];

    /**
     * `email_verified_at` NO es asignable en masa: marcar un email como
     * verificado es un acto explícito, no un campo más de un formulario
     * (fix1 #3). Se pone con `markEmailAsVerified()` o con `forceFill()`.
     *
     * `role_id` sí lo es porque el formulario de usuarios de Filament lo
     * necesita, y ese formulario ya está detrás del panel de administración.
     * Por la API no llega nunca: no hay alta ni edición de usuarios por API.
     */

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'email',
        'password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
        'email_verified_at',
        'current_team_id',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
        'profile_photo_url',
    ];

    /**
     * Devuelve todos los datos de redes sociales para el usuario.
     */
    public function socials(): HasMany
    {
        return $this->hasMany(UserSocial::class, 'user_id', 'id');
    }

    /**
     * Detalles ampliados del perfil (profesión, web, etc.).
     */
    public function details(): HasOne
    {
        return $this->hasOne(UserDetail::class);
    }

    /**
     * Preferencias de notificación.
     */
    public function settings(): HasOne
    {
        return $this->hasOne(UserSetting::class);
    }

    /**
     * Relación con el role del usuario.
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(UserRole::class, 'role_id', 'id');
    }

    /**
     * Contenidos en los que colabora. Inversa de Content::contributors().
     */
    public function contributedContents(): BelongsToMany
    {
        return $this->belongsToMany(Content::class, 'content_contributors', 'user_id', 'content_id');
    }

    /**
     * Obtiene los datos para la red social de Twitter.
     */
    public function getTwitterAttribute(): ?UserSocial
    {
        return $this->socials()->where('social_network_id', 2)->first();
    }

    /**
     * Obtiene los datos para la red social de Facebook.
     */
    public function getFacebookAttribute(): ?UserSocial
    {
        return $this->socials()->where('social_network_id', 1)->first();
    }

    /**
     * Devuelve el nombre completo del usuario (nombre y apellido).
     */
    public function getFullNameAttribute(): string
    {
        if (! $this->surname) {
            return $this->name;
        }

        return $this->name.' '.$this->surname;
    }

    public function urlAvatarIcon(): string
    {
        if ($this->profile_photo_path) {
            return asset($this->image);
        }

        return asset('images/avatar-icon.png');
    }

    public function urlAvatar(): string
    {
        if ($this->profile_photo_path) {
            return asset($this->image);
        }

        return asset('images/avatar.png');
    }

    public function getProfilePhotoUrlAttribute(): string
    {
        return $this->urlAvatar();
    }

    /**
     * Plataformas sobre las que este usuario puede trabajar.
     *
     * Sólo tiene sentido para el rol Editor: un admin llega a todas y no
     * necesita filas en el pivote.
     */
    public function platforms(): BelongsToMany
    {
        return $this->belongsToMany(Platform::class, 'platform_user')
            ->withTimestamps();
    }

    /**
     * Determina si el usuario puede acceder al panel de Filament indicado.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        if ($panel->getId() === 'admin') {
            return $this->is_active && ($this->isAdmin() || $this->isEditor());
        }

        return $this->is_active;
    }

    /**
     * Comprueba si el usuario es SuperAdmin.
     */
    public function isSuperAdmin(): bool
    {
        return (int) $this->role_id === UserRoleEnum::SuperAdmin->value;
    }

    /**
     * Comprueba si el usuario es Admin o SuperAdmin.
     */
    public function isAdmin(): bool
    {
        return in_array((int) $this->role_id, [
            UserRoleEnum::SuperAdmin->value,
            UserRoleEnum::Admin->value,
        ], true);
    }

    /**
     * Comprueba si el usuario es Editor: edita contenido, pero sólo en las
     * plataformas que tenga asignadas en `platform_user`.
     */
    public function isEditor(): bool
    {
        return (int) $this->role_id === UserRoleEnum::Editor->value;
    }

    /**
     * El rol como enum, o `null` si el `role_id` no está en el catálogo.
     */
    public function roleEnum(): ?UserRoleEnum
    {
        return UserRoleEnum::tryFrom((int) $this->role_id);
    }

    /**
     * ¿Puede este usuario asignar el rol indicado a alguien?
     *
     * Nadie reparte un rol por encima del suyo: ver
     * {@see UserRoleEnum::assignableRoles()} para el porqué (AR-P01).
     */
    public function canAssignRole(UserRoleEnum|int|null $role): bool
    {
        return $role !== null && (bool) $this->roleEnum()?->canAssign($role);
    }

    /**
     * Ids de los roles que este usuario puede repartir.
     *
     * @return list<int>
     */
    public function assignableRoleIds(): array
    {
        return $this->roleEnum()?->assignableRoleIds() ?? [];
    }

    /**
     * ¿Puede este usuario trabajar sobre la plataforma indicada?
     *
     * Un admin llega a todas. Un editor sólo a las que tiene asignadas. Un
     * contenido sin plataforma (`null`) es de administración general.
     */
    public function canManagePlatform(?int $platformId): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        if (! $this->isEditor() || $platformId === null) {
            return false;
        }

        return $this->platforms()
            ->whereKey($platformId)
            ->exists();
    }

    /**
     * Elimina de forma segura un usuario y todos los datos asociado.
     *
     * @return bool
     */
    public function safeDelete()
    {
        // # Elimino la imagen asociada al tipo de repositorio y todas las miniaturas.
        /*
        if ($this->image) {
            $this->image->safeDelete();
        }
        */

        return $this->delete();
    }

    /**
     * Rellena los datos en el modelo de usuario y lo devuelve.
     *
     *
     * @return mixed
     */
    public function createModel(Request $request)
    {
        $user = $this->create($request->only([
            'name',
            'surname',
            'email',
        ]) + [
            'password' => Hash::make($request->get('password')),
        ]);

        return $user;
    }

    /**
     * Actualiza los datos para en el modelo de usuario y lo devuelve.
     *
     *
     * @return $this
     */
    public function updateModel($request)
    {
        $this->update($request->only([
            'name',
            'surname',
            'email',
        ])
        );

        if ($request->has('password')) {
            $this->update([
                'password' => Hash::make($request->password),
            ]);
        }

        return $this;
    }

    /**
     * Marca al usuario actual como activo o inactivo según el estado actual.
     *
     * @return bool|null
     */
    public function toggleActive()
    {
        // # Controlo que exista usuario y además sea distinto al role superadmin.
        if ($this->role_id == 1) {
            return null;
        }

        // N192: antes esto era `$this->deleted_at = ...`. Con `SoftDeletes`
        // activo, desactivar a alguien lo hacía desaparecer del propio listado
        // desde el que se le desactivaba, sin forma de volver a activarlo.
        // Desactivado y borrado son dos estados distintos.
        $this->is_active = ! $this->is_active;

        return $this->save();
    }

    /**
     * Restringe la consulta a los usuarios que el usuario dado puede ver.
     *
     * Sustituye a un `public static function all()` que sobreescribía el método
     * de Eloquent para filtrar por rol. Aquello estaba mal por tres motivos:
     * ignoraba el parámetro `$columns`, cambiaba el significado de un método
     * que el framework llama por su cuenta, y consultaba `auth()->user()->role_id`
     * sin comprobar que hubiera alguien autenticado — o sea, error fatal en
     * cualquier comando de consola, job o petición pública que acabase pasando
     * por ahí.
     *
     * @param  Builder<User>  $query
     * @return Builder<User>
     */
    public function scopeVisibleTo(Builder $query, ?User $user): Builder
    {
        if ($user?->isSuperAdmin()) {
            return $query;
        }

        if ($user?->isAdmin()) {
            return $query->where('role_id', '!=', UserRoleEnum::SuperAdmin->value);
        }

        return $query->whereNotIn('role_id', [
            UserRoleEnum::SuperAdmin->value,
            UserRoleEnum::Admin->value,
        ]);
    }

    /**
     * Devuelve todos los usuarios activos.
     *
     * Miraba `deleted_at`, que es borrado lógico, no desactivación (N192).
     *
     * @return Collection<int, User>
     */
    public static function getAllActive()
    {
        return self::query()->where('is_active', true)->get();
    }

    /**
     * Devuelve la cantidad de usuarios activos de la plataforma.
     *
     * Contaba `deleted_at IS NULL`, o sea «no borrados», que no es lo mismo que
     * activos: un usuario desactivado con `is_active = false` seguía contando
     * (AR-CODE-01). Contradecía además a `getAllActive()`, su pareja, que sí
     * filtra por `is_active`.
     */
    public static function countActive(): int
    {
        return self::query()->where('is_active', true)->count();
    }

    /**
     * Devuelve todos los usuarios inactivos de la plataforma.
     *
     * Hacía `self::where('deleted_at')`. Con un solo argumento, Eloquent lo
     * traduce a `whereNull('deleted_at')`; y como el modelo usa `SoftDeletes`,
     * el global scope ya añade esa misma condición. Resultado: la consulta
     * devolvía **los usuarios vivos**, justo lo contrario de lo que promete el
     * nombre, y nunca filtraba por `is_active` (AR-CODE-01).
     *
     * @return Collection<int, User>
     */
    public static function getAllInactive()
    {
        return self::query()->where('is_active', false)->get();
    }

    /**
     * Devuelve la cantidad de usuarios inactivos de la plataforma.
     *
     * Mismo fallo que {@see self::getAllInactive()}: contaba usuarios vivos.
     */
    public static function countInactive(): int
    {
        return self::query()->where('is_active', false)->count();
    }

    /**
     * Devuelve la cantidad de usuarios nuevos este mes.
     *
     * @return int
     */
    public static function countNewInThisMonth()
    {
        return self::whereBetween('created_at',
            [
                Carbon::now()->subMonth()->format('Y-m-d H:i:s'),
                Carbon::now()->format('Y-m-d H:i:s'),
            ]
        )->count();
    }

    /**
     * Devuelve información básica sobre el usuario.
     */
    public function basicInfo(): array
    {
        return [
            'name' => $this->fullName,
            'nick' => $this->nickname,
            'url_image_micro' => $this->urlImageMicro,
            'url_image_small' => $this->urlImageSmall,
            'profession' => 'Developer', // Tablas user_details
            'web' => 'raupulus.dev', // Tabla user_details
            'social_networks' => $this->socials->map(function ($ele) {
                $sn = $ele->socialNetwork;

                return [
                    'slug' => $sn->slug,
                    'name' => $sn->name,
                    'color' => $sn->color,
                    'nick' => $ele->nick,
                    'url' => $ele->url,
                    // La URL estaba escrita a mano contra `http://localhost:8000`,
                    // así que en producción todos los perfiles apuntaban a una
                    // imagen de la máquina de desarrollo de nadie. `asset()`
                    // resuelve contra `APP_URL`. `social_networks.image` guarda
                    // la ruta de la imagen propia de cada red; si está vacía, se
                    // cae a la genérica.
                    'url_image' => $sn->image
                        ? asset($sn->image)
                        : asset('images/default/small.jpg'),
                ];
            }),

        ];
    }
}
