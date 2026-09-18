<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Hardware\HardwareComponent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HardwareComponent>
 */
class HardwareComponentFactory extends Factory
{
    protected $model = HardwareComponent::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->words(2, true),
            'brand' => fake()->company(),
            'model' => fake()->word(),
        ];
    }
}
