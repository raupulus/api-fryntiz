<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Content\Content;
use App\Models\Content\ContentPage;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ContentPage>
 */
class PageFactory extends Factory
{
    protected $model = ContentPage::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = $this->faker->sentence(3);

        $modularContent = [
            [
                'type' => 'text',
                'data' => [
                    'content' => '<p>'.$this->faker->paragraph().'</p>',
                    'alignment' => 'left',
                ],
            ],
            [
                'type' => 'cta',
                'data' => [
                    'text' => 'Más información',
                    'url' => $this->faker->url(),
                    'variant' => 'primary',
                    'open_in_new_tab' => false,
                ],
            ],
        ];

        return [
            'content_id' => Content::factory(),
            'title' => $title,
            'slug' => Str::slug($title),
            'content' => json_encode($modularContent, JSON_THROW_ON_ERROR),
            'order' => 1,
        ];
    }

    public function active(): static
    {
        return $this->state(fn () => [
            'content_id' => Content::factory()->state([
                'is_active' => true,
                'status_id' => 1,
                'published_at' => now()->subDay(),
            ]),
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn () => [
            'content_id' => Content::factory()->state([
                'is_active' => false,
                'status_id' => 2,
                'published_at' => null,
            ]),
        ]);
    }
}
