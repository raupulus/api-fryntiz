<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Hardware\HardwareDevice;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HardwareDevice>
 */
class HardwareDeviceFactory extends Factory
{
    protected $model = HardwareDevice::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->word(),
            'is_public' => false,
        ];
    }

    public function public(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_public' => true,
        ]);
    }
}
