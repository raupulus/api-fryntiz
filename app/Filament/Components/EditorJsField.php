<?php

declare(strict_types=1);

namespace App\Filament\Components;

use Filament\Forms\Components\Field;

/**
 * Campo personalizado de Filament que integra Editor.js.
 * Guarda y carga datos en formato JSON.
 */
class EditorJsField extends Field
{
    protected string $view = 'filament.components.editorjs-field';

    protected array $editorTools = [];

    protected ?string $editorPlaceholder = null;

    public function tools(array $tools): static
    {
        $this->editorTools = $tools;

        return $this;
    }

    public function placeholder(string $placeholder): static
    {
        $this->editorPlaceholder = $placeholder;

        return $this;
    }

    public function getEditorTools(): array
    {
        return $this->editorTools;
    }

    public function getPlaceholder(): ?string
    {
        return $this->editorPlaceholder;
    }
}
