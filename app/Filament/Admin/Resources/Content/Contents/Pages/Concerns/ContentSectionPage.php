<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Content\Contents\Pages\Concerns;

use App\Filament\Admin\Resources\Content\Contents\ContentResource;
use App\Models\Content\Content;
use Filament\Actions\Action;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Gate;

/**
 * Lo común a las secciones de la ficha de un contenido (E2 de la auditoría de
 * contenidos; F7 del plan del 2026-09-24):
 *
 * - el título dice la sección y el contenido;
 * - «Vista previa» en la cabecera;
 * - en las que tienen formulario, «Guardar cambios» arriba y fijo abajo (E4);
 * - cada sección enseña sólo las relaciones que le tocan (ninguna, salvo que
 *   la página diga otra cosa).
 */
trait ContentSectionPage
{
    public function getTitle(): string|Htmlable
    {
        $title = $this->getRecordTitle();

        return static::getNavigationLabel().': '.($title instanceof Htmlable ? $title->toHtml() : $title);
    }

    public function getBreadcrumb(): string
    {
        return static::getNavigationLabel();
    }

    /**
     * @return array<class-string>
     */
    protected function getAllRelationManagers(): array
    {
        return [];
    }

    protected function previewAction(): Action
    {
        return Action::make('preview')
            ->label('Vista previa')
            ->icon('heroicon-o-eye')
            ->color('gray')
            ->url(fn (): string => ContentResource::getUrl('preview', ['record' => $this->getRecord()]))
            ->openUrlInNewTab()
            ->visible(fn (): bool => ! $this->contentRecord()->trashed() && Gate::allows('view', $this->contentRecord()));
    }

    protected function saveOnTopAction(): Action
    {
        return $this->getSaveFormAction()->formId('form');
    }

    protected function contentRecord(): Content
    {
        $record = $this->getRecord();

        if (! $record instanceof Content) {
            throw new \LogicException('Las secciones de la ficha son de un contenido.');
        }

        return $record;
    }
}
