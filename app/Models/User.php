<?php

namespace App\Models;

use App\Http\Traits\ImageTrait;
use Carbon\Carbon;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Sanctum\HasApiTokens;
use RoleHelper;
use function asset;

/**
 * Class User
 *
 * @package App\Models
 */
class User extends Authenticatable
{
    use HasApiTokens;
    use HasFactory;
    use Notifiable;
    use TwoFactorAuthenticatable;
    use ImageTrait;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'surname',
        'email',
        'password',
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
     *
     * @return HasMany
     */
    public function socials(): HasMany
    {
        return $this->hasMany(UserSocial::class, 'user_id', 'id');
    }

    /**
     * Obtiene los datos para la red social de Twitter.
     *
     * @return UserSocial|null
     */
    public function getTwitterAttribute(): ?UserSocial
    {
        return $this->socials()->where('social_network_id', 2)->first();
    }

    /**
     * Obtiene los datos para la red social de Facebook.
     *
     * @return UserSocial|null
     */
    public function getFacebookAttribute(): ?UserSocial
    {
        return $this->socials()->where('social_network_id', 1)->first();
    }



    /**
     * Devuelve el nombre completo del usuario (nombre y apellido).
     *
     * @return string
     */
    public function getFullNameAttribute(): string
    {
        if (!$this->surname) {
            return $this->name;
        }

        return $this->name . ' ' . $this->surname;
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
        //return route('users.show', $this);
        return '#';
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
     * Elimina de forma segura un usuario y todos los datos asociado.
     *
     * @return bool
     */
    public function safeDelete()
    {
        ## Elimino la imagen asociada al tipo de repositorio y todas las miniaturas.
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
     * @param \Illuminate\Http\Request $request
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
     * @param $request
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
        ## Controlo que exista usuario y además sea distinto al role superadmin.
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
     *
     * @return \Illuminate\Database\Eloquent\Collection|\App\Models\User[]
     */
    public static function all($columns = ['*'])
    {
        $users = parent::all();

        ## Usuarios Activos que según el role del actual puede ver.
        if (RoleHelper::isSuperAdmin()) {
            return $users;
        } else if (RoleHelper::isAdmin()) {
            return $users->whereNotIn('role_id', [1]);
        }

        return $users->whereNotIn('role_id', [1, 2]);
    }

    /**
     * Devuelve todos los usuarios activos de la plataforma.
     *
     * @return \Illuminate\Database\Eloquent\Collection|\Illuminate\Foundation\Auth\User[]
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
     * @return \Illuminate\Database\Eloquent\Collection|\Illuminate\Foundation\Auth\User[]
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
}
