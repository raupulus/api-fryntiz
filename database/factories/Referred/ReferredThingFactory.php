<?php

declare(strict_types=1);

namespace Database\Factories\Referred;

use App\Models\Hardware\HardwareComponent;
use App\Models\Hardware\HardwareDevice;
use App\Models\Referred\ReferredPlatform;
use App\Models\Referred\ReferredThing;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReferredThing>
 */
class ReferredThingFactory extends Factory
{
    protected $model = ReferredThing::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'referred_platform_id' => ReferredPlatform::factory(),
            'hardware_device_id' => HardwareDevice::factory(),
            'hardware_component_id' => null,
            'name' => fake()->words(3, true),
            'description' => fake()->sentence(),
            'url' => fake()->url(),
            'price' => fake()->randomFloat(2, 5, 250),
            'currency' => 'EUR',
            'is_active' => true,
        ];
    }

    /**
     * Indica que el enlace aplica a un componente específico.
     */
    public function forComponent(?HardwareComponent $component = null): static
    {
        return $this->state(function (array $attributes) use ($component) {
            $componentInstance = $component ?? HardwareComponent::factory()->create([
                'hardware_device_id' => $attributes['hardware_device_id'],
            ]);

            return [
                'hardware_device_id' => $componentInstance->hardware_device_id,
                'hardware_component_id' => $componentInstance->id,
            ];
        });
    }

    /**
     * Enlace inactivo.
     */
    public function inactive(): static
    {
        return $this->state(fn () => [
            'is_active' => false,
        ]);
    }
}
