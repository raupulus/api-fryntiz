<?php

namespace App\Models\WeatherStation;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AEMETAdverseEvents extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'slug', 'polygon', 'others_fields_json'];
}
