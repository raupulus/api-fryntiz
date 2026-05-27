<?php

namespace Database\Factories\Content;

use App\Models\Content\Content;
use App\Models\Content\ContentAvailableType;
use App\Models\Platform;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ContentFactory extends Factory
{
    protected $model = Content::class;

    public function definition(): array
    {
        $title = $this->faker->unique()->sentence(4);

        // Crear tipo si no existe (necesario para FK)
        $type = ContentAvailableType::firstOrCreate(
            ['id' => 1],
            ['name' => 'article', 'slug' => 'article', 'plural_name' => 'articles', 'description' => 'Artículos']
        );

        return [
            'platform_id' => Platform::factory(),
            'author_id' => User::factory(),
            'title' => $title,
            'slug' => Str::slug($title),
            'excerpt' => $this->faker->paragraph(),
            'is_active' => true,
            'published_at' => now()->subDays(rand(1, 30)),
            'type_id' => $type->id,
        ];
    }
}
