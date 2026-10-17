<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Platform;
use App\Models\PlatformEditorProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PlatformEditorProfile>
 */
class PlatformEditorProfileFactory extends Factory
{
    protected $model = PlatformEditorProfile::class;

    public function definition(): array
    {
        return [
            'platform_id' => Platform::factory(),
            'name' => ucfirst($this->faker->unique()->word()),
            'system_instruction' => 'Escribe como '.$this->faker->words(3, true).'.',
        ];
    }
}
