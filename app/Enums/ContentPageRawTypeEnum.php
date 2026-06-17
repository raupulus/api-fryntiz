<?php

declare(strict_types=1);

namespace App\Enums;

enum ContentPageRawTypeEnum: string
{
    case Html = 'html';
    case Markdown = 'markdown';
    case PlainText = 'plain_text';
    case Json = 'json';

    public function label(): string
    {
        return match ($this) {
            self::Html => 'HTML',
            self::Markdown => 'Markdown',
            self::PlainText => 'Texto plano',
            self::Json => 'JSON',
        };
    }
}
