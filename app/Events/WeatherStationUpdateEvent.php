<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WeatherStationUpdateEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Message details
     */
    public $datas;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($datas)
    {
        $this->datas = $datas->prepareApiResponse();
    }


    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        // https://github.com/beyondcode/laravel-websockets-demo/blob/master/app/Events/MessageSent.php

        //\Log::info('Evento broadcastOn weather-station');
        return new Channel('weather-station');
        //return new PrivateChannel('weather-station');
        //return new PresenceChannel('weater-station-update');
    }
}
