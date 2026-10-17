<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\AiProviderEnum;
use App\Models\AiProvider;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AiProvider>
 */
class AiProviderFactory extends Factory
{
    protected $model = AiProvider::class;

    public function definition(): array
    {
        return [
            'provider' => AiProviderEnum::Gemini,
            'model' => 'gemini-3.8-flash',
            'api_key' => 'test-gemini-key',
            'is_active' => true,
            'is_default' => true,
            'sends_reasoning_level' => true,
            'requests_reasoning_summary' => true,
        ];
    }

    public function provider(AiProviderEnum $provider, string $model): static
    {
        return $this->state([
            'provider' => $provider,
            'model' => $model,
            'api_key' => 'test-'.$provider->value.'-key',
            'is_default' => false,
            'requests_reasoning_summary' => $provider->summarizesReasoningByDefault(),
        ]);
    }
}
