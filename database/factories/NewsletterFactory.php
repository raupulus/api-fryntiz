<?php

namespace Database\Factories;

use App\Models\Newsletter;
use App\Models\Platform;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class NewsletterFactory extends Factory
{
    protected $model = Newsletter::class;

    public function definition(): array
    {
        return [
            'platform_id'        => Platform::factory(),
            'email'              => $this->faker->unique()->safeEmail(),
            'name'               => $this->faker->name(),
            'is_verified'        => true,
            'verification_token' => Str::random(64),
            'verified_at'        => now(),
            'unsubscribe_token'  => Str::random(64),
            'status'             => 'active',
            'subscription_source' => 'api',
            'language'           => 'es',
        ];
    }

    public function unverified(): static
    {
        return $this->state([
            'is_verified' => false,
            'verified_at' => null,
            'status'      => 'pending',
        ]);
    }
}
