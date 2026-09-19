<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\File;
use App\Models\Gallery;
use App\Models\GalleryImage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GalleryImage>
 */
class GalleryImageFactory extends Factory
{
    protected $model = GalleryImage::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'gallery_id' => Gallery::factory(),
            'image_id' => fn () => File::create([
                'module' => 'galleries',
                'path' => 'galleries',
                'storage_path' => 'public/galleries',
                'name' => 'image_'.$this->faker->unique()->uuid().'.jpg',
                'original_name' => 'photo.jpg',
                'width' => 1600,
                'height' => 900,
                'size' => 2048,
                'is_private' => false,
            ])->id,
            'order' => $this->faker->numberBetween(1, 50),
            'caption' => $this->faker->optional()->sentence(),
        ];
    }
}
