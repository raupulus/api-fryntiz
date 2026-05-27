<?php

namespace App\Listeners;

use App\Events\WeatherStationUpdateEvent;

class BroadcastWeatherStationUpdate
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle($event)
    {
        broadcast(new WeatherStationUpdateEvent($event->datas));
    }
}
