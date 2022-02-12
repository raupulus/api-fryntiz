<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Request;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Sanctum\HasApiTokens;
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

    /*
    public static function createModel(Request $request)
    {

    }
    */

}
