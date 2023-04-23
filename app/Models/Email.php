<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Email extends Model
{
    use HasFactory;

    protected $table = 'emails';

    protected $fillable = [
        'user_id',
        'language_id',
        'email',
        'attributes',
        'subject',
        'message',
        'privacity',
        'contactme',
        'server_ip',
        'client_ip',
        'app_name',
        'app_domain',
        'client_user_agent',
        'client_referer',
        'client_accept_language',
    ];

    /**
     * Relación con el usuario.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    /**
     * Relación con el idioma del usuario
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function language()
    {
        return $this->belongsTo(Language::class, 'language_id', 'id');
    }
}


