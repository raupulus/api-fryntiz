<?php

declare(strict_types=1);

namespace App\Http\Resources\V2\WeatherStation;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Serializa el parte meteorológico oficial y la tabla diaria de mareas
 * para una ciudad o provincia (`/api/v2/weather-station-official/cities/{city}`
 * y `/api/v2/weather-station-official/provinces/{province}`).
 */
class OfficialWeatherResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var array<string, mixed> $data */
        $data = $this->resource;

        return [
            'municipality' => $data['municipality'],
            'province' => $data['province'],
            'aemet_code' => $data['aemet_code'],
            'updated_at' => $data['updated_at'],
            'updated_at_iso' => $data['updated_at_iso'],
            'weather' => [
                'source' => $data['weather']['source'],
                'current_temp' => $data['weather']['current_temp'],
                'temp_max' => $data['weather']['temp_max'],
                'temp_min' => $data['weather']['temp_min'],
                'sky_state' => $data['weather']['sky_state'],
                'wind_direction' => $data['weather']['wind_direction'],
                'wind_speed_kmh' => $data['weather']['wind_speed_kmh'],
                'uv_max' => $data['weather']['uv_max'],
                'rain_prob' => $data['weather']['rain_prob'],
                'sea_temp' => $data['weather']['sea_temp'],
            ],
            'tides' => [
                'coefficient' => $data['tides']['coefficient'],
                'is_aguaje' => $data['tides']['is_aguaje'],
                'type_label' => $data['tides']['type_label'],
                'lunar_day' => $data['tides']['lunar_day'],
                'events' => $data['tides']['events'],
            ],
        ];
    }
}
