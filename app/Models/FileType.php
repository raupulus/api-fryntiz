<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\FileType
 */
class FileType extends Model
{
    protected $table = 'file_types';
    protected $guarded = [
        'id'
    ];
}
