<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\WeatherStation\AEMET\AEMETOzoneTotal;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AEMETOzoneTotal>
 */
class AEMETOzoneTotalFactory extends Factory
{
    protected $model = AEMETOzoneTotal::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'ozone_value' => fake()->numberBetween(250, 450),
            'measured_on' => fake()->unique()->date(),
        ];
    }
}
