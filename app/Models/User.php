<?php

declare(strict_types=1);

namespace App\Models;

use App\Http\Traits\ImageTrait;
use Carbon\Carbon;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
use RoleHelper;

use function asset;

/**
 * Class User
 *
 * @property int $id
 * @property int $role_id Role principal del usuario, aunque pueda tener otros roles extras
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
        'name',
        'surname',
        'nickname',
        'email',
        'password',
        'profile_photo_path',
        'email_verified_at',
    ];

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

    public function urlAvatarIcon()
    {
        if ($this->profile_photo_path) {
            return asset($this->image);
        }

        return asset('images/avatar-icon.png');
    }

    public function urlAvatar()
    {
        if ($this->profile_photo_path) {
            return asset($this->image);
        }

        return asset('images/avatar.png');
    }

    public function getProfilePhotoUrlAttribute()
    {
        return $this->urlAvatar();
    }

    public function urlProfile()
    {
        // return route('users.show', $this);
        return route('dashboard.users.show', $this->id);
    }

    public function adminlte_image()
    {
        return $this->urlAvatarIcon();
    }

    public function adminlte_desc()
    {
        return 'Role_X';
    }

    public function adminlte_profile_url()
    {
        return $this->urlProfile();
    }

    /*
     public function adminlte_image()
    {
        return 'https://picsum.photos/300/300';
    }

    public function adminlte_desc()
    {
        return 'That\'s a nice guy';
    }

    public function adminlte_profile_url()
    {
        return 'profile/username';
    }
     */

    /**
     * Determina si el usuario puede acceder al panel de Filament indicado.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        if ($panel->getId() === 'admin') {
            return $this->isSuperAdmin();
        }

        return true;
    }

    /**
     * Comprueba si el usuario es SuperAdmin (role_id = 1).
     */
    public function isSuperAdmin(): bool
    {
        return $this->role_id === 1;
    }

    /**
     * Comprueba si el usuario es Admin o SuperAdmin (role_id = 1 o 2).
     */
    public function isAdmin(): bool
    {
        return in_array($this->role_id, [1, 2], true);
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

        $this->deleted_at = $this->deleted_at ? null : Carbon::now();

        return $this->save();
    }

    /**
     * Obtiene todos los modelos de la base de datos filtrando por roles.
     *
     * @param  array|mixed  $columns
     * @return Collection|User[]
     */
    public static function all($columns = ['*'])
    {
        $users = parent::all();

        // # Usuarios Activos que según el role del actual puede ver.
        if (RoleHelper::isSuperAdmin()) {
            return $users;
        } elseif (RoleHelper::isAdmin()) {
            return $users->whereNotIn('role_id', [1]);
        }

        return $users->whereNotIn('role_id', [1, 2]);
    }

    /**
     * Devuelve todos los usuarios activos de la plataforma.
     *
     * @return Collection|Authenticatable[]
     */
    public static function getAllActive()
    {
        return self::whereNull('deleted_at')->get();
    }

    /**
     * Devuelve la cantidad de usuarios activos de la plataforma.
     *
     * @return int
     */
    public static function countActive()
    {
        return self::whereNull('deleted_at')->count() ?? 0;
    }

    /**
     * Devuelve todos los usuarios inactivos de la plataforma.
     *
     * @return Collection|Authenticatable[]
     */
    public static function getAllInactive()
    {
        return self::where('deleted_at')->get();
    }

    /**
     * Devuelve la cantidad de usuarios activos de la plataforma.
     *
     * @return int
     */
    public static function countInactive()
    {
        return self::where('deleted_at')->count() ?? 0;
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
                    // 'url_image' => $sn->urlImage,
                    'url_image' => 'http://localhost:8000/images/default/small.jpg', // TODO->Terminar imágenes en SocialNetwork
                ];
            }),

        ];
    }
}
