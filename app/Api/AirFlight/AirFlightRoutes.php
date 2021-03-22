<?php

namespace App\Api\AirFlight;

use Illuminate\Database\Eloquent\Model;

/**
 * Class AirFlightAirPlane
 *
 * Representa las rutas que recorren los aviones.
 *
 * @package App\Api\AirFlight
 */
class AirFlightRoutes extends Model
{
    protected $table = 'airflight_routes';

    protected $fillable = [
        'airflight_airplane_id',
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
