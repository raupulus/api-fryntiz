<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Email extends Model
{
    use HasFactory;

    protected $table = 'emails';

    // TOFIX: Solo para pruebas, cambiar en cuanto termine el desarrollo!!!!!!!!
    protected $guarded = [
        'id',
    ];
}
