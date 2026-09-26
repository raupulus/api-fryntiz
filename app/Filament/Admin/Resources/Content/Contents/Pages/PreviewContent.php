<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Content\Contents\Pages;

use App\Filament\Admin\Resources\Content\Contents\ContentResource;
use App\Models\Content\Content;
use App\Models\Content\ContentPage;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Gate;

/**
 * «Vista previa» del contenido (C7 de la auditoría de contenidos; F7 del plan
 * del 2026-09-24): todas sus páginas seguidas, con el HTML que sirve la API,
 * también si es un borrador. Estilos básicos, como en `main`: cada web pone
 * los suyos.
 */
class PreviewContent extends Page
{
    use InteractsWithRecord;

    protected static string $resource = ContentResource::class;

    protected string $view = 'filament.admin.content.preview';

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);

        abort_unless(Gate::allows('view', $this->getRecord()), 403);
    }

    public function getTitle(): string|Htmlable
    {
        $record = $this->getRecord();

        return 'Vista previa: '.($record instanceof Content ? $record->title : '');
    }

    /**
     * @return Collection<int, ContentPage>
     */
    public function getPreviewPages(): Collection
    {
        $record = $this->getRecord();

        return $record instanceof Content ? $record->pages()->get() : new Collection;
    }
}
