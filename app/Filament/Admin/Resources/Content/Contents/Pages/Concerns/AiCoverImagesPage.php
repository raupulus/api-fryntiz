<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Content\Contents\Pages\Concerns;

/**
 * Una página del panel con portadas con IA en su campo `image_id`: lo que
 * usan las acciones del campo (`AiCoverImageActions`). Lo cumple el trait
 * `HasAiCoverImages`.
 */
interface AiCoverImagesPage
{
    public function aiCoverAvailable(string $mode = 'generate'): bool;

    /**
     * @return array<string, mixed>
     */
    public function aiCoverModalData(string $mode): array;

    public function hasAiCoverUndo(): bool;
}
