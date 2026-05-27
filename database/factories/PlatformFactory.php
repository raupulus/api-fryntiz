<?php

namespace Database\Factories;

use App\Models\Platform;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class PlatformFactory extends Factory
{
    protected $model = Platform::class;

    public function definition(): array
    {
        $title = $this->faker->unique()->words(3, true);

        return [
            'user_id' => User::factory(),
            'title' => ucfirst($title),
            'slug' => Str::slug($title),
            'description' => $this->faker->sentence(10),
            'domain' => $this->faker->unique()->domainName(),
            'url_about' => $this->faker->url(),
        ];
    }
}
