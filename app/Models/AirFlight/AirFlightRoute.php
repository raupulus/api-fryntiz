<?php

namespace App\Models\AirFlight;

use Illuminate\Database\Eloquent\Model;

/**
 * Class AirFlightRoute
 *
 * Representa las rutas que recorren los aviones.
 *
 * @package App\Models\AirFlight
 */
class AirFlightRoute extends Model
{
    protected $table = 'airflight_routes';

    protected $fillable = [
        'airplane_id',
        'squawk',
        'flight',
        'lat',
        'lon',
        'altitude',
        'vert_rate',
        'track',
        'speed',
        'seen_at',
        'messages',
        'rssi',
        'emergency',
    ];

}
