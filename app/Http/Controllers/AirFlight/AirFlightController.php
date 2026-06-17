<?php

declare(strict_types=1);

namespace App\Http\Controllers\AirFlight;

use App\Http\Controllers\Controller;
use App\Models\AirFlight\AirFlightAirPlane;
use Carbon\Carbon;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;

/**
 * Class AirFlightController
 */
class AirFlightController extends Controller
{
    /**
     * Lleva a la vista de resumen para visualizar la depuración.
     *
     * @return Application|Factory|View
     */
    public function index()
    {
        $now = Carbon::now();
        $lastHour = (clone $now)->subHour();

        $planes = AirFlightAirPlane::where('seen_last_at', '>=', $lastHour)
            ->orderByDesc('seen_last_at')
            ->paginate(20);

        return view('airflight.index')->with([
            'planes' => $planes,
        ]);
    }
}
