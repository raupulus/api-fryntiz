<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Content\Content;
use App\Models\Content\ContentAvailableType;
use App\Models\Platform;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Content>
 */
class PostFactory extends Factory
{
    protected $model = Content::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = $this->faker->unique()->sentence(4);

        $type = ContentAvailableType::firstOrCreate(
            ['id' => 1],
            ['name' => 'article', 'slug' => 'article', 'plural_name' => 'articles', 'description' => 'Artículos']
        );

        return [
            'platform_id' => Platform::factory(),
            'author_id' => User::factory(),
            'status_id' => 1,
            'type_id' => $type->id,
            'title' => $title,
            'slug' => Str::slug($title),
            'excerpt' => $this->faker->paragraph(),
            'is_active' => true,
            'is_featured' => false,
            'published_at' => now()->subDays(rand(1, 30)),
        ];
    }

    public function published(): static
    {
        return $this->state(fn () => [
            'is_active' => true,
            'status_id' => 1,
            'published_at' => now()->subDay(),
        ]);
    }

    public function draft(): static
    {
        return $this->state(fn () => [
            'is_active' => false,
            'status_id' => 2,
            'published_at' => null,
        ]);
    }

    public function featured(): static
    {
        return $this->state(fn () => [
            'is_featured' => true,
        ]);
    }
}
