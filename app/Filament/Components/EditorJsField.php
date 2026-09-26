<?php

declare(strict_types=1);

namespace App\Filament\Components;

use App\Models\Content\Content;
use Closure;
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

    /**
     * Bloque de HTML libre en el menú. Por defecto no: sólo administradores
     * (B1 de la auditoría de contenidos). Sin la herramienta, los bloques de
     * HTML que ya haya se ven como «no se puede mostrar» y se guardan intactos.
     */
    protected bool|Closure $allowRawHtml = false;

    /**
     * Contenido al que van las subidas: las rutas del editor cuelgan de él.
     */
    protected Content|Closure|null $content = null;

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

    public function allowRawHtml(bool|Closure $allow = true): static
    {
        $this->allowRawHtml = $allow;

        return $this;
    }

    public function canUseRawHtml(): bool
    {
        return (bool) $this->evaluate($this->allowRawHtml);
    }

    public function content(Content|Closure|null $content): static
    {
        $this->content = $content;

        return $this;
    }

    /**
     * Rutas de subida, imagen por URL y metadatos de enlaces del contenido, más
     * el token CSRF. Sin contenido no hay rutas y el editor va sin imágenes,
     * adjuntos ni tarjetas de enlace.
     *
     * @return array<string, string>
     */
    public function getEndpoints(): array
    {
        $content = $this->evaluate($this->content);

        if (! $content instanceof Content) {
            return [];
        }

        return [
            'upload' => route('admin.contents.editor.files.store', $content),
            'byUrl' => route('admin.contents.editor.files.by-url', $content),
            'urlMetadata' => route('admin.contents.editor.url-metadata', $content),
            'csrf' => csrf_token(),
        ];
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
