<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\SocialNetwork;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<SocialNetwork>
 */
class SocialMediaFactory extends Factory
{
    protected $model = SocialNetwork::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = $this->faker->unique()->company().' Social';

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'type' => 'social',
            'color' => $this->faker->hexColor(),
            'url' => 'https://'.$this->faker->domainName(),
            'url_user' => 'https://'.$this->faker->domainName().'/user',
            'url_privacity' => 'https://'.$this->faker->domainName().'/privacy',
            'icon' => 'globe',
            'image' => null,
        ];
    }

    public function active(): static
    {
        return $this->state(fn () => [
            'type' => 'social',
        ]);
    }
}
