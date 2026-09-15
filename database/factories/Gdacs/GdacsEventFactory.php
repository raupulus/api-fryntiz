<?php

declare(strict_types=1);

namespace Database\Factories\Gdacs;

use App\Enums\GdacsAlertLevelEnum;
use App\Enums\GdacsEventTypeEnum;
use App\Models\Gdacs\GdacsEvent;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<GdacsEvent>
 */
class GdacsEventFactory extends Factory
{
    protected $model = GdacsEvent::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $from = Carbon::instance($this->faker->dateTimeBetween('-30 days', 'now'));

        return [
            'event_type' => $this->faker->randomElement(GdacsEventTypeEnum::cases())->value,
            'event_id' => $this->faker->unique()->numberBetween(1000000, 9999999),
            'episode_id' => $this->faker->numberBetween(1, 5),
            'name' => 'Forest fires in Spain',
            'alert_level' => GdacsAlertLevelEnum::Green->value,
            'is_current' => true,
            'from_date' => $from,
            'to_date' => (clone $from)->addDay(),
            'last_modified_at' => Carbon::now(),
            'lat' => $this->faker->latitude(36, 38),
            'lon' => $this->faker->longitude(-7, -5),
            'distance_km' => $this->faker->randomFloat(1, 0, 150),
            'severity_value' => $this->faker->randomFloat(1, 1, 5000),
            'severity_unit' => 'ha',
            'severity_text' => 'Green impact for forestfire',
            'affected_population' => null,
            'report_url' => 'https://www.gdacs.org/report.aspx',
        ];
    }

    /**
     * Suceso ya no activo (por si algún test necesita distinguir).
     */
    public function inactive(): static
    {
        return $this->state(fn () => ['is_current' => false]);
    }
}
