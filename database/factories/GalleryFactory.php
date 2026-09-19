<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\GalleryAspectRatioEnum;
use App\Models\Gallery;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Gallery>
 */
class GalleryFactory extends Factory
{
    protected $model = Gallery::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => $this->faker->sentence(3),
            'description' => $this->faker->paragraph(),
            'aspect_ratio' => GalleryAspectRatioEnum::Wide16x9,
        ];
    }

    public function square(): static
    {
        return $this->state(fn () => [
            'aspect_ratio' => GalleryAspectRatioEnum::Square1x1,
        ]);
    }

    public function standard(): static
    {
        return $this->state(fn () => [
            'aspect_ratio' => GalleryAspectRatioEnum::Standard4x3,
        ]);
    }

    public function free(): static
    {
        return $this->state(fn () => [
            'aspect_ratio' => GalleryAspectRatioEnum::Free,
        ]);
    }
}
