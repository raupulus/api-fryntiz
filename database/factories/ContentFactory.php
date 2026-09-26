<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ContentStatusEnum;
use App\Models\Content\Content;
use App\Models\Content\ContentAvailableType;
use App\Models\Platform;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @extends Factory<Content>
 */
class ContentFactory extends Factory
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
            // Publicado y activo: lo que sale en las webs. Las reglas de
            // publicación de `Content` se aplican igual al crear.
            'status_id' => ContentStatusEnum::Published->value,
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
            'status_id' => ContentStatusEnum::Published->value,
            'published_at' => now()->subDay(),
        ]);
    }

    /**
     * Publicado pero oculto («Activo» desmarcado). Se desmarca después de
     * crearlo: al entrar en «publicado», el modelo lo marca como activo.
     */
    public function hidden(): static
    {
        return $this->published()->afterCreating(function (Content $content): void {
            $content->is_active = false;
            $content->save();
        });
    }

    public function draft(): static
    {
        return $this->state(fn () => [
            'is_active' => false,
            'status_id' => ContentStatusEnum::Draft->value,
            'published_at' => null,
        ]);
    }

    public function scheduled(?Carbon $at = null): static
    {
        return $this->state(fn () => [
            'is_active' => false,
            'status_id' => ContentStatusEnum::Scheduled->value,
            'published_at' => null,
            'scheduled_at' => $at ?? now()->addDay(),
        ]);
    }

    public function featured(): static
    {
        return $this->state(fn () => [
            'is_featured' => true,
        ]);
    }
}
