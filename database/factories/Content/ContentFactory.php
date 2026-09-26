<?php

declare(strict_types=1);

namespace Database\Factories\Content;

use App\Enums\ContentStatusEnum;
use App\Models\Content\Content;
use App\Models\Content\ContentAvailableType;
use App\Models\Platform;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * La que usa `Content::factory()`.
 *
 * @extends Factory<Content>
 */
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
            // Publicado y activo: lo que sale en las webs. Las reglas de
            // publicación de `Content` se aplican igual al crear.
            'status_id' => ContentStatusEnum::Published->value,
            'is_active' => true,
            'published_at' => now()->subDays(rand(1, 30)),
            'type_id' => $type->id,
        ];
    }

    public function published(): static
    {
        return $this->state(fn () => [
            'status_id' => ContentStatusEnum::Published->value,
            'is_active' => true,
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
            'status_id' => ContentStatusEnum::Draft->value,
            'is_active' => false,
            'published_at' => null,
        ]);
    }

    public function scheduled(?Carbon $at = null): static
    {
        return $this->state(fn () => [
            'status_id' => ContentStatusEnum::Scheduled->value,
            'is_active' => false,
            'published_at' => null,
            'scheduled_at' => $at ?? now()->addDay(),
        ]);
    }
}
