<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Proporciones de aspecto permitidas para las imágenes de una galería.
 */
enum GalleryAspectRatioEnum: string
{
    case Wide16x9 = '16:9';
    case Standard4x3 = '4:3';
    case Square1x1 = '1:1';
    case Free = 'free';

    /**
     * Etiqueta descriptiva en español.
     */
    public function label(): string
    {
        return match ($this) {
            self::Wide16x9 => '16:9 Panorámica (Recomendado)',
            self::Standard4x3 => '4:3 Estándar',
            self::Square1x1 => '1:1 Cuadrada',
            self::Free => 'Libre (sin restricción fija)',
        };
    }

    /**
     * Devuelve las proporciones permitidas para el cropper de Filament.
     *
     * @return array<int, string>
     */
    public function cropperAspectRatios(): array
    {
        return match ($this) {
            self::Wide16x9 => ['16:9'],
            self::Standard4x3 => ['4:3'],
            self::Square1x1 => ['1:1'],
            self::Free => ['16:9', '4:3', '1:1'],
        };
    }

    /**
     * Proporción fija para bloquear el cropper de Filament (null para recorte libre).
     */
    public function cropRatio(): ?string
    {
        return match ($this) {
            self::Wide16x9 => '16:9',
            self::Standard4x3 => '4:3',
            self::Square1x1 => '1:1',
            self::Free => null,
        };
    }

    /**
     * Regla CSS de aspect-ratio para las tarjetas de la galería.
     */
    public function cssAspectRatio(): string
    {
        return match ($this) {
            self::Wide16x9 => '16/9',
            self::Standard4x3 => '4/3',
            self::Square1x1 => '1/1',
            self::Free => '16/9',
        };
    }

    /**
     * Ancho relativo para el viewport del cropper de Filament.
     */
    public function viewportWidth(): ?int
    {
        return match ($this) {
            self::Wide16x9 => 16,
            self::Standard4x3 => 4,
            self::Square1x1 => 1,
            self::Free => null,
        };
    }

    /**
     * Alto relativo para el viewport del cropper de Filament.
     */
    public function viewportHeight(): ?int
    {
        return match ($this) {
            self::Wide16x9 => 9,
            self::Standard4x3 => 3,
            self::Square1x1 => 1,
            self::Free => null,
        };
    }

    /**
     * Devuelve las opciones como array value => label (para formularios de Filament).
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        $options = [];
        foreach (self::cases() as $case) {
            $options[$case->value] = $case->label();
        }

        return $options;
    }
}
