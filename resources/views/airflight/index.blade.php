@dd([
    \App\Api\AirFlight\AirFlightAirPlane::orderByDesc('created_at')->limit(20)->get(),
    \App\Api\AirFlight\AirFlightRoutes::orderByDesc('created_at')->limit(50)
    ->get(),
])
