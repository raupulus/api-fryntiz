<?php

declare(strict_types=1);

namespace App\Filament\Components;

use Filament\Forms\Components\FileUpload;

/**
 * Componente reutilizable de subida de imágenes con cropper preconfigurado.
 *
 * Uso: ImageCropperUpload::makeImage('photo')->avatar()->directory('photos');
 */
class ImageCropperUpload extends FileUpload
{
    public static function makeImage(string $name): static
    {
        return static::make($name)
            ->image()
            ->imageEditor()
            ->disk('public')
            ->visibility('public')
            ->maxSize(4096)
            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp']);
    }

    public function avatar(): static
    {
        return $this
            ->imageEditorAspectRatios(['1:1'])
            ->imageResizeMode('cover')
            ->imageResizeTargetWidth('512')
            ->imageResizeTargetHeight('512');
    }

    public function cover16x9(): static
    {
        return $this
            ->imageEditorAspectRatios(['16:9', '4:3', '1:1'])
            ->imageResizeMode('cover')
            ->imageResizeTargetWidth('1600')
            ->imageResizeTargetHeight('900');
    }

    public function logo(): static
    {
        return $this
            ->imageEditorAspectRatios(['1:1', '3:1'])
            ->imageResizeMode('contain')
            ->imageResizeTargetWidth('512')
            ->imageResizeTargetHeight('512');
    }

    public function icon(int $size = 64): static
    {
        return $this
            ->imageEditorAspectRatios(['1:1'])
            ->imageResizeMode('cover')
            ->imageResizeTargetWidth((string) $size)
            ->imageResizeTargetHeight((string) $size)
            ->maxSize(512);
    }
}
