<?php

declare(strict_types=1);

namespace Database\Factories\Referred;

use App\Models\Referred\ReferredPlatform;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ReferredPlatform>
 */
class ReferredPlatformFactory extends Factory
{
    protected $model = ReferredPlatform::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->company().' Affiliates';

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'description' => fake()->sentence(),
            'url' => fake()->url(),
            'url_panel' => fake()->url(),
            'url_register' => fake()->url(),
        ];
    }
}
