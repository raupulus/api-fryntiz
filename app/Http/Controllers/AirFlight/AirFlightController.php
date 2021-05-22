<?php

namespace App\Http\Controllers\AirFlight;

use App\Models\AirFlight\AirFlightAirPlane;
use App\Http\Controllers\Controller;
use Carbon\Carbon;

/**
 * Class AirFlightController
 *
 * @package App\Http\Controllers\AirFlightController
 */
class AirFlightController extends Controller
{
    /**
     * Lleva a la vista de resumen para visualizar la depuración.
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function index()
    {
        $now = Carbon::now();
        $lastHour = (clone($now))->subHour();

        $planes = AirFlightAirPlane::where('seen_last_at', '>=', $lastHour)
            ->orderByDesc('seen_last_at')
            ->paginate(20);

        return view('airflight.index')->with([
            'planes' => $planes,
        ]);
    }
}
