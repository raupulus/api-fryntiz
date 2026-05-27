<?php

namespace App\Enums;

enum FileTypeEnum: string
{
    case Image = 'image';
    case Document = 'document';
    case Video = 'video';
    case Audio = 'audio';
    case Archive = 'archive';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Image => 'Imagen',
            self::Document => 'Documento',
            self::Video => 'Video',
            self::Audio => 'Audio',
            self::Archive => 'Archivo comprimido',
            self::Other => 'Otro',
        };
    }
}
