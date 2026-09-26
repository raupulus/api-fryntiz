<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Formatos en los que se escribe una página de contenido.
 *
 * El valor es el nombre público (el que usa la API en `?format=` y en el campo
 * `format`); `rawType()` es el tipo con el que se guarda en
 * `content_available_page_raw.type`. Sólo difieren en Editor.js, que se guarda
 * como `json`.
 */
enum ContentPageFormatEnum: string
{
    case EditorJs = 'editorjs';
    case Markdown = 'markdown';
    case Html = 'html';

    public function label(): string
    {
        return match ($this) {
            self::EditorJs => 'Editor.js',
            self::Markdown => 'Markdown',
            self::Html => 'HTML',
        };
    }

    /**
     * Tipo en `content_available_page_raw.type`.
     */
    public function rawType(): string
    {
        return match ($this) {
            self::EditorJs => 'json',
            self::Markdown => 'markdown',
            self::Html => 'html',
        };
    }

    /**
     * Campo del formulario del panel que edita este formato.
     */
    public function formField(): string
    {
        return match ($this) {
            self::EditorJs => 'content_json',
            self::Markdown => 'content_markdown',
            self::Html => 'content_html',
        };
    }

    public static function fromRawType(string $type): ?self
    {
        foreach (self::cases() as $case) {
            if ($case->rawType() === $type) {
                return $case;
            }
        }

        return null;
    }
}
