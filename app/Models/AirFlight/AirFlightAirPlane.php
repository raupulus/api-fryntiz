<?php

namespace App\Models\AirFlight;

use Illuminate\Database\Eloquent\Model;

/**
 * Class AirFlightAirPlane
 *
 * Representa un avión concreto.
 *
 * @package App\Api\AirFlight
 */
class AirFlightAirPlane extends Model
{
    protected $table = 'airflight_airplanes';

    protected $fillable = [
        'icao',
        'category',
        'seen_last_at',
        'seen_first_at'
    ];
}
