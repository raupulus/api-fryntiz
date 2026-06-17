<?php

declare(strict_types=1);

namespace App\Enums;

enum ContentTypeEnum: string
{
    case Article = 'article';
    case Tutorial = 'tutorial';
    case Project = 'project';
    case Page = 'page';
    case Review = 'review';

    public function label(): string
    {
        return match ($this) {
            self::Article => 'Artículo',
            self::Tutorial => 'Tutorial',
            self::Project => 'Proyecto',
            self::Page => 'Página',
            self::Review => 'Reseña',
        };
    }
}
