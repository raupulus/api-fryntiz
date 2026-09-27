<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Content\Contents\Pages;

use App\Enums\ContentPageFormatEnum;
use App\Enums\ContentPageVersionReasonEnum;
use App\Exceptions\ContentPageConflictException;
use App\Exceptions\ContentPageLockedException;
use App\Exceptions\ContentUploadException;
use App\Filament\Admin\Resources\Content\Contents\ContentResource;
use App\Filament\Admin\Resources\Content\Contents\Pages\Concerns\ContentPageForm;
use App\Filament\Admin\Resources\Content\Contents\Pages\Concerns\ContentSectionPage;
use App\Filament\Concerns\HasImageFileUpload;
use App\Models\Content\Content;
use App\Models\Content\ContentPage;
use App\Models\Content\ContentPageDraft;
use App\Models\File;
use App\Models\User;
use App\Services\Content\ContentFileService;
use App\Services\Content\ContentImageService;
use App\Services\Content\ContentPageDraftService;
use App\Services\Content\ContentPageHistoryService;
use App\Services\Content\ContentPageLockService;
use App\Services\Content\ContentPageLockState;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Livewire\WithFileUploads;
use RuntimeException;

/**
 * «Páginas»: la pantalla propia para editar las páginas de un contenido (E1 y
 * P3 de la auditoría de contenidos; F8 del plan del 2026-09-24). Sustituye a
 * la ventana flotante del antiguo `PagesRelationManager`.
 *
 * - A la izquierda, la lista: cambiar de página guarda antes, se reordena
 *   arrastrando y «Añadir página» abre una nueva que se crea al final.
 * - Arriba, una barra fija con «Guardar» (también Ctrl/Cmd+S), el estado del
 *   autoguardado y del bloqueo, el formato, «Vista previa», «Historial» e
 *   «Imágenes».
 * - Mientras se escribe, cada 30 s se guarda un borrador en el servidor y se
 *   renueva el bloqueo (F6); al abrir una página con borrador se ofrece
 *   recuperarlo.
 * - Si otro la tiene bloqueada, se ve en lectura.
 *
 * La lógica de guardar, del bloqueo, de los borradores y del historial está en
 * los servicios de F6; aquí sólo se usa. El formulario está en el rasgo
 * `ContentPageForm`.
 *
 * @property-read Schema $form
 */
class ManageContentPages extends Page
{
    use ContentPageForm;
    use ContentSectionPage;
    use HasImageFileUpload;
    use InteractsWithRecord;
    use WithFileUploads;

    public const AUTOSAVE_SECONDS = 30;

    protected static string $resource = ContentResource::class;

    protected static ?string $navigationLabel = 'Páginas';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentDuplicate;

    protected string $view = 'filament.admin.content.page-editor';

    /**
     * Página que se edita; null en una nueva, que se crea al guardar.
     */
    public ?int $pageId = null;

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    /**
     * Esta pestaña, para el bloqueo: otra pestaña del mismo usuario la ve en
     * lectura.
     */
    public string $lockToken = '';

    public bool $readOnly = false;

    public ?string $lockMessage = null;

    /**
     * Cómo está el bloqueo para esta pestaña (`ContentPageLockState::*`).
     */
    public ?string $lockStatus = null;

    /**
     * `updated_at` de la página al abrirla (D4).
     */
    public ?string $openedAt = null;

    public ?string $autosavedAt = null;

    /**
     * Hay algo distinto de lo guardado (aunque ya esté en el borrador).
     */
    public bool $hasUnsavedChanges = false;

    /**
     * El borrador que se ofrece al abrir: id, hace cuánto y si la página
     * cambió después.
     *
     * @var array{id: int, ago: string, outdated: bool}|null
     */
    public ?array $draftOffer = null;

    /**
     * La imagen nueva al sustituir una desde «Imágenes».
     */
    public mixed $replacement = null;

    public static function canAccess(array $parameters = []): bool
    {
        $record = $parameters['record'] ?? null;

        return parent::canAccess($parameters) && ($record === null || Gate::allows('update', $record));
    }

    public function mount(int|string $record, int|string|null $page = null): void
    {
        $this->record = $this->resolveRecord($record);

        abort_unless(Gate::allows('update', $this->getRecord()), 403);

        $this->lockToken = Str::random(40);

        $target = match (true) {
            $page === 'new' => null,
            $page === null => $this->pagesQuery()->first(),
            default => $this->pagesQuery()->whereKey((int) $page)->firstOrFail(),
        };

        $this->loadPage($target);
    }

    // ── Carga ───────────────────────────────────────────────────────────────

    private function loadPage(?ContentPage $page): void
    {
        $this->pageId = $page?->id;
        $this->openedAt = $page?->updated_at?->toIso8601String();
        $this->hasUnsavedChanges = false;
        $this->autosavedAt = null;
        $this->lockMessage = null;
        $this->lockStatus = null;

        if ($page !== null) {
            $this->applyLockState($this->locks()->acquire($page, $this->user(), $this->lockToken));
        } else {
            $this->setReadOnly(false);
        }

        $this->form->fill($this->formData($page));
        $this->offerDraft();
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(?ContentPage $page): array
    {
        if ($page === null) {
            return [
                'title' => null,
                'slug' => null,
                'image_id' => null,
                'source_format' => ContentPageFormatEnum::EditorJs->value,
                'stored_format' => null,
            ];
        }

        $service = $this->formatService();
        $format = $service->sourceFormat($page);
        $content = $service->sourceContent($page);

        return [
            'title' => $page->title,
            'slug' => $page->slug,
            'image_id' => $page->image_id,
            'source_format' => $format->value,
            'stored_format' => $format->value,
            // Indentado: en «JSON en crudo» se lee (C9).
            $format->formField() => $format === ContentPageFormatEnum::EditorJs ? $this->prettyJson($content) : $content,
            'backup_id' => $service->latestBackup($page)?->id,
        ];
    }

    private function offerDraft(): void
    {
        $draft = $this->drafts()->find($this->user(), $this->ownerContent(), $this->currentPage());

        $this->draftOffer = $draft === null ? null : [
            'id' => $draft->id,
            'ago' => ($draft->updated_at ?? now())->locale('es')->diffForHumans(),
            'outdated' => $this->drafts()->isOutdated($draft),
        ];
    }

    // ── Guardar ─────────────────────────────────────────────────────────────

    /**
     * «Guardar» (también Ctrl/Cmd+S).
     */
    public function save(): void
    {
        $this->persist();
    }

    /**
     * Antes de cambiar de página o de sección: guarda si hay algo que
     * guardar. `false` = no se ha podido, y no se cambia.
     */
    public function saveBeforeLeaving(): bool
    {
        if ($this->readOnly || ! $this->differsFromSaved()) {
            $this->releaseLock();

            return true;
        }

        $saved = $this->persist();

        if ($saved) {
            $this->releaseLock();
        }

        return $saved;
    }

    private function persist(): bool
    {
        if ($this->readOnly) {
            Notification::make()->warning()->title('La página está en lectura')->body((string) $this->lockMessage)->send();

            return false;
        }

        $data = $this->form->getState();
        $format = ContentPageFormatEnum::tryFrom((string) ($data['source_format'] ?? '')) ?? ContentPageFormatEnum::EditorJs;
        $content = isset($data[$format->formField()]) ? (string) $data[$format->formField()] : null;
        $reason = match ($data['pending_change'] ?? null) {
            'restore' => ContentPageVersionReasonEnum::Restore,
            'convert' => ContentPageVersionReasonEnum::FormatChange,
            default => null,
        };

        $page = $this->currentPage() ?? new ContentPage([
            'content_id' => $this->ownerContent()->id,
            'order' => (int) $this->pagesQuery()->max('order') + 1,
        ]);
        $attributes = $this->pageAttributes($data);

        try {
            $saved = $this->formatService()->savePage(
                $page,
                $attributes,
                $format,
                $content,
                $reason,
                $this->user(),
                $this->openedAt !== null ? Carbon::parse($this->openedAt) : null,
                $this->lockToken,
            );
        } catch (ContentPageConflictException $e) {
            $this->keepInDraft();

            return $this->refuse($e->getMessage());
        } catch (ContentPageLockedException $e) {
            $this->setReadOnly(true);
            $this->lockMessage = $e->getMessage();

            return $this->refuse($e->getMessage());
        } catch (ValidationException $e) {
            return $this->refuse(implode(' ', Arr::flatten($e->errors())));
        } catch (InvalidArgumentException|RuntimeException $e) {
            return $this->refuse($e->getMessage());
        }

        if (! $saved && $reason !== null) {
            Notification::make()->warning()->title('No se ha cambiado el formato')->body('El contenido estaba vacío, así que la página se queda como estaba.')->send();
        }

        $isNew = $this->pageId === null;
        $page->refresh();
        Cache::forget($this->undoKey());

        if ($isNew) {
            // Nueva: desde ahora es de esta pestaña.
            $this->pageId = $page->id;
            $this->locks()->acquire($page, $this->user(), $this->lockToken);
            $this->dispatch('content-page-created', url: ContentResource::getUrl('pages', ['record' => $this->ownerContent(), 'page' => $page->id]));
        }

        $this->openedAt = $page->updated_at?->toIso8601String();
        $this->hasUnsavedChanges = false;
        $this->draftOffer = null;
        $this->form->fill($this->formData($page));

        Notification::make()->success()->title('Página guardada')->send();
        $this->warnAboutTopLevelHeadings($format, (string) $page->content);

        return true;
    }

    private function refuse(string $reason): bool
    {
        Notification::make()
            ->danger()
            ->title('No se ha guardado la página')
            ->body($reason.' No se ha cambiado nada.')
            ->persistent()
            ->send();

        return false;
    }

    private function warnAboutTopLevelHeadings(ContentPageFormatEnum $format, string $html): void
    {
        $headings = $format === ContentPageFormatEnum::EditorJs ? 0 : $this->converter()->topLevelHeadings($html);

        if ($headings > 0) {
            Notification::make()
                ->warning()
                ->title($headings === 1 ? 'La página tiene 1 título h1 o h2' : "La página tiene {$headings} títulos h1 o h2")
                ->body('Se ha guardado igual, pero en la web el h1 es el título del contenido y el h2 el de la página: dentro del texto, los títulos van mejor del h3 al h6 (### en Markdown).')
                ->persistent()
                ->send();
        }
    }

    /**
     * Las columnas de la página: el resto del formulario es el estado del
     * editor, no columnas. Sin slug, sale del título.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function pageAttributes(array $data): array
    {
        $attributes = Arr::only($data, ['title', 'slug', 'image_id']);

        if (blank($attributes['slug'] ?? null)) {
            $attributes['slug'] = $this->freeSlug(Str::slug((string) ($attributes['title'] ?? '')));
        }

        return $this->resolveImageUpload($attributes, 'image_id', 'content-pages', webpOriginal: true);
    }

    /**
     * La página se ha guardado desde otro sitio: lo escrito aquí va al
     * borrador de quien guardaba, para no perderlo (D4).
     */
    private function keepInDraft(): void
    {
        $this->storeDraft();
        $this->hasUnsavedChanges = true;
    }

    // ── Autoguardado, borradores y bloqueo ──────────────────────────────────

    /**
     * Cada 30 s y al perder el foco (D1). Renueva el bloqueo (P4) y, de paso,
     * mantiene viva la sesión (D3).
     */
    public function autosave(): void
    {
        $page = $this->currentPage();

        if ($this->readOnly) {
            // Si ya la ha soltado quien la tenía, pasa a edición. Es también
            // lo que pasa al recargar: la petición de la página nueva llega
            // antes que el aviso de la vieja soltando el bloqueo.
            if ($page !== null) {
                $state = $this->locks()->acquire($page, $this->user(), $this->lockToken);

                if ($state->canEdit()) {
                    $this->loadPage($page->refresh());
                    Notification::make()->success()->title('Ya puedes editar la página')->send();
                } else {
                    $this->applyLockState($state);
                }
            }

            return;
        }

        if ($page !== null && ! $this->locks()->renew($page, $this->user(), $this->lockToken)) {
            // Se guarda igual en el borrador: no se pierde nada.
            $this->storeDraft();
            $this->setReadOnly(true);
            $this->lockMessage = 'Has perdido el bloqueo de la página (lo ha forzado un administrador o ha caducado y la ha abierto otra persona). Lo que tenías sin guardar está en tu borrador.';
            Notification::make()->warning()->title('Bloqueo perdido')->body($this->lockMessage)->persistent()->send();

            return;
        }

        $this->hasUnsavedChanges = $this->storeDraft() !== null;
        $this->autosavedAt = now()->toIso8601String();
    }

    /**
     * Guarda lo que hay en pantalla como borrador de este usuario, sin
     * validar. `null` si es igual a lo guardado.
     */
    private function storeDraft(): ?ContentPageDraft
    {
        $data = $this->data ?? [];
        $format = ContentPageFormatEnum::tryFrom((string) ($data['source_format'] ?? '')) ?? ContentPageFormatEnum::EditorJs;
        $content = (string) ($data[$format->formField()] ?? '');
        $imageId = $data['image_id'] ?? null;

        if (trim($content) === '' && $this->currentPage() === null && blank($data['title'] ?? null)) {
            return null;
        }

        return $this->drafts()->save(
            $this->user(),
            $this->ownerContent(),
            $this->currentPage(),
            $format,
            $content,
            filled($data['title'] ?? null) ? (string) $data['title'] : null,
            filled($data['slug'] ?? null) ? (string) $data['slug'] : null,
            is_numeric($imageId) ? (int) $imageId : null,
            $this->openedAt !== null ? Carbon::parse($this->openedAt) : null,
        );
    }

    /**
     * Si lo de la pantalla es distinto de lo guardado.
     */
    private function differsFromSaved(): bool
    {
        $page = $this->currentPage();
        $data = $this->data ?? [];
        $format = ContentPageFormatEnum::tryFrom((string) ($data['source_format'] ?? '')) ?? ContentPageFormatEnum::EditorJs;
        $content = (string) ($data[$format->formField()] ?? '');

        if ($page === null) {
            return trim($content) !== '' || filled($data['title'] ?? null);
        }

        $imageId = $data['image_id'] ?? null;
        $page->unsetRelation('raws')->unsetRelation('currentRawType');

        return ContentPageDraftService::hash($format, $content, $data['title'] ?? null, $data['slug'] ?? null, is_numeric($imageId) ? (int) $imageId : null)
            !== ContentPageDraftService::hash(
                $this->formatService()->sourceFormat($page),
                $this->formatService()->sourceContent($page),
                $page->title,
                $page->slug,
                $page->image_id !== null ? (int) $page->image_id : null,
            );
    }

    /**
     * Cualquier cambio en el formulario que llegue al servidor.
     */
    public function updatedData(): void
    {
        $this->hasUnsavedChanges = true;
    }

    private function releaseLock(): void
    {
        $page = $this->currentPage();

        if ($page !== null && ! $this->readOnly) {
            $this->locks()->release($page, $this->user(), $this->lockToken);
        }
    }

    private function applyLockState(ContentPageLockState $state): void
    {
        $this->setReadOnly(! $state->canEdit());
        $this->lockMessage = $state->message();
        $this->lockStatus = $state->status;
    }

    /**
     * Editor.js va con `wire:ignore` y no se entera de que el formulario pasa a
     * lectura: se le avisa.
     */
    private function setReadOnly(bool $readOnly): void
    {
        if ($this->readOnly !== $readOnly) {
            $this->dispatch('content-page-read-only', readOnly: $readOnly);
        }

        $this->readOnly = $readOnly;
    }

    public function takeOverAction(): Action
    {
        return Action::make('takeOver')
            ->label('Editar aquí')
            ->icon('heroicon-o-pencil-square')
            ->color('warning')
            ->requiresConfirmation()
            ->modalHeading('Editar en esta pestaña')
            ->modalDescription('La otra pestaña pasa a lectura en su siguiente autoguardado, y lo que tuviera sin guardar queda en tu borrador. Úsalo también si el navegador se cerró de golpe.')
            ->modalSubmitActionLabel('Editar aquí')
            ->visible(fn (): bool => $this->readOnly && $this->lockStatus === ContentPageLockState::OTHER_TAB)
            ->action(function (): void {
                $page = $this->currentPage();

                if ($page === null) {
                    return;
                }

                $this->applyLockState($this->locks()->takeOver($page, $this->user(), $this->lockToken));
                // Lo de la pantalla podía ser viejo: se vuelve a leer.
                $this->loadPage($page->refresh());
            });
    }

    public function restoreDraftAction(): Action
    {
        return Action::make('restoreDraft')
            ->label('Recuperar')
            ->color('warning')
            ->requiresConfirmation()
            ->modalHeading('Recuperar el borrador')
            ->modalDescription(fn (): string => ($this->draftOffer['outdated'] ?? false)
                ? 'La página ha cambiado desde tu borrador. Si lo recuperas, lo guardado ahora pasa al historial y se puede volver a ello.'
                : 'Se guarda el borrador en la página. Lo que hay guardado ahora pasa al historial y se puede volver a ello.')
            ->modalSubmitActionLabel('Recuperar el borrador')
            ->visible(fn (): bool => $this->draftOffer !== null && ! $this->readOnly)
            ->action(function (): void {
                $draft = ContentPageDraft::query()->find($this->draftOffer['id'] ?? 0);

                if ($draft === null) {
                    $this->draftOffer = null;

                    return;
                }

                try {
                    $page = $this->drafts()->restore($draft, $this->user(), $this->lockToken);
                } catch (AuthorizationException|ContentPageLockedException|ValidationException|InvalidArgumentException $e) {
                    $this->refuse($e instanceof ValidationException ? implode(' ', Arr::flatten($e->errors())) : $e->getMessage());

                    return;
                }

                Notification::make()->success()->title('Borrador recuperado')->body('Lo que había pasó al historial.')->send();
                $this->redirect(ContentResource::getUrl('pages', ['record' => $this->ownerContent(), 'page' => $page->id]));
            });
    }

    public function discardDraftAction(): Action
    {
        return Action::make('discardDraft')
            ->label('Descartar')
            ->color('gray')
            ->requiresConfirmation()
            ->modalHeading('Descartar el borrador')
            ->modalDescription('Se borra el borrador; la página se queda como está guardada.')
            ->modalSubmitActionLabel('Descartar')
            ->visible(fn (): bool => $this->draftOffer !== null)
            ->action(function (): void {
                $draft = ContentPageDraft::query()->find($this->draftOffer['id'] ?? 0);

                if ($draft !== null) {
                    $this->drafts()->discard($draft, $this->user());
                }

                $this->draftOffer = null;
            });
    }

    public function forceUnlockAction(): Action
    {
        return Action::make('forceUnlock')
            ->label('Forzar desbloqueo')
            ->color('danger')
            ->icon('heroicon-o-lock-open')
            ->requiresConfirmation()
            ->modalDescription('Quien la tiene abierta pasa a lectura en su siguiente autoguardado. Lo que tuviera sin guardar queda en su borrador.')
            ->visible(fn (): bool => $this->readOnly && $this->lockStatus === ContentPageLockState::OTHER_USER && $this->currentPage() !== null && $this->isAdmin())
            ->action(function (): void {
                $page = $this->currentPage();

                if ($page === null) {
                    return;
                }

                $this->locks()->forceUnlock($page, $this->user());
                $this->applyLockState($this->locks()->acquire($page, $this->user(), $this->lockToken));
                // Lo de la pantalla podía ser viejo: se vuelve a leer.
                $this->loadPage($page->refresh());
            });
    }

    // ── Lista de páginas ────────────────────────────────────────────────────

    /**
     * @return Collection<int, ContentPage>
     */
    public function getPagesList(): Collection
    {
        return $this->pagesQuery()->get(['id', 'title', 'order', 'content_id']);
    }

    /**
     * @return Collection<int, ContentPage>
     */
    public function getTrashedPages(): Collection
    {
        return ContentPage::onlyTrashed()->where('content_id', $this->ownerContent()->id)->orderBy('order')->get(['id', 'title', 'order', 'content_id', 'deleted_at']);
    }

    public function pageUrl(ContentPage|string|null $page): string
    {
        return ContentResource::getUrl('pages', ['record' => $this->ownerContent(), 'page' => $page instanceof ContentPage ? $page->id : ($page ?? 'new')]);
    }

    /**
     * Reordenar arrastrando: se renumeran 1, 2, 3… sin huecos. No toca
     * `updated_at`: cambiar el orden no es cambiar la página, y quien la tenga
     * abierta no debe encontrarse un conflicto al guardar (D4).
     *
     * @param  array<int, int|string>  $ids
     */
    public function reorderPages(array $ids): void
    {
        abort_unless(Gate::allows('update', $this->ownerContent()), 403);

        $own = $this->pagesQuery()->pluck('id')->map(fn ($id): int => (int) $id)->all();
        $ids = array_values(array_intersect(array_map('intval', $ids), $own));
        // Las que no llegaran, detrás, en su orden.
        $ids = [...$ids, ...array_values(array_diff($own, $ids))];

        DB::transaction(function () use ($ids): void {
            foreach ($ids as $position => $id) {
                ContentPage::query()->whereKey($id)->toBase()->update(['order' => $position + 1]);
            }
        });
    }

    public function deletePageAction(): Action
    {
        return Action::make('deletePage')
            ->label('Eliminar página')
            // En el móvil, sólo el icono: la barra no cabe.
            ->labeledFrom('md')
            ->icon('heroicon-o-trash')
            ->color('danger')
            ->requiresConfirmation()
            ->modalHeading(fn (): string => 'Eliminar «'.($this->currentPage()->title ?? '').'»')
            ->modalDescription('Va a la papelera de páginas y las de detrás suben un puesto. Se puede restaurar.')
            ->modalSubmitActionLabel('Eliminar')
            ->visible(fn (): bool => $this->currentPage() !== null && ! $this->readOnly)
            ->authorize(fn (): bool => $this->currentPage() !== null && Gate::allows('delete', $this->currentPage()))
            ->action(function (): void {
                $page = $this->currentPage();

                if ($page === null) {
                    return;
                }

                $page->safeDelete();
                $this->locks()->release($page, $this->user(), $this->lockToken);
                Notification::make()->success()->title('Página en la papelera')->send();
                $this->redirect($this->pageUrl($this->pagesQuery()->first()));
            });
    }

    public function restorePageAction(): Action
    {
        return Action::make('restorePage')
            ->label('Restaurar')
            ->icon('heroicon-o-arrow-uturn-left')
            ->color('gray')
            ->link()
            ->authorize(fn (array $arguments): bool => ($trashed = $this->trashedPage($arguments)) !== null && Gate::allows('restore', $trashed))
            ->action(function (array $arguments): void {
                $page = $this->trashedPage($arguments);

                if ($page === null) {
                    return;
                }

                // Vuelve al final, para no chocar con el orden de las demás.
                $page->order = (int) $this->pagesQuery()->max('order') + 1;
                $page->restore();
                Notification::make()->success()->title('Página restaurada')->send();
            });
    }

    public function forceDeletePageAction(): Action
    {
        return Action::make('forceDeletePage')
            ->label('Eliminar definitivamente')
            ->icon('heroicon-o-x-mark')
            ->color('danger')
            ->link()
            ->requiresConfirmation()
            ->modalDescription('No se puede deshacer. Sus ficheros quedan marcados para borrarse a los 30 días si nada más los usa.')
            ->authorize(fn (array $arguments): bool => ($trashed = $this->trashedPage($arguments)) !== null && Gate::allows('forceDelete', $trashed))
            ->action(function (array $arguments): void {
                $this->trashedPage($arguments)?->forceDelete();
                Notification::make()->success()->title('Página eliminada definitivamente')->send();
            });
    }

    /**
     * @param  array<string, mixed>  $arguments
     */
    private function trashedPage(array $arguments): ?ContentPage
    {
        return ContentPage::onlyTrashed()->where('content_id', $this->ownerContent()->id)->find((int) ($arguments['page'] ?? 0));
    }

    // ── Historial ───────────────────────────────────────────────────────────

    public function historyAction(): Action
    {
        return Action::make('history')
            ->label('Historial')
            // En el móvil, sólo el icono: la barra no cabe.
            ->labeledFrom('md')
            ->icon('heroicon-o-clock')
            ->color('gray')
            ->slideOver()
            ->modalWidth(Width::ThreeExtraLarge)
            ->modalHeading('Historial de la página')
            ->modalDescription('Lo que tenía la página antes de cada cambio: las 50 últimas versiones, 30 días como mucho.')
            ->modalContent(fn () => view('filament.admin.content.page-history', [
                'versions' => $this->currentPage() !== null ? app(ContentPageHistoryService::class)->all($this->currentPage()) : collect(),
            ]))
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Cerrar')
            ->visible(fn (): bool => $this->currentPage() !== null);
    }

    /**
     * «Recuperar» una versión: se abre en el editor sin guardar, con el mismo
     * aviso y la misma confirmación que un cambio de formato (G5).
     */
    public function loadVersion(int $versionId): void
    {
        $version = $this->versionFor($versionId);

        if ($version === null || $this->readOnly) {
            return;
        }

        $data = $this->data ?? [];
        $current = ContentPageFormatEnum::tryFrom((string) ($data['source_format'] ?? '')) ?? ContentPageFormatEnum::EditorJs;

        if (blank($data['pending_change'] ?? null)) {
            $data['original_format'] = $current->value;
            Cache::put($this->undoKey(), ['format' => $current->value, 'content' => (string) ($data[$current->formField()] ?? '')], now()->addDay());
        }

        $data[$current->formField()] = null;
        $data[$version->format->formField()] = $version->format === ContentPageFormatEnum::EditorJs ? $this->prettyJson($version->content) : $version->content;
        $data['source_format'] = $version->format->value;
        $data['pending_change'] = 'restore';
        $data['backup_id'] = $version->id;
        $data['confirm_format_change'] = false;

        $this->form->fill($data);
        $this->hasUnsavedChanges = true;
        $this->unmountAction();

        Notification::make()->info()->title('Versión cargada')->body('Revísala y marca la casilla antes de guardar. «Deshacer» vuelve a lo de antes.')->send();
    }

    // ── Imágenes (H2) ───────────────────────────────────────────────────────

    public function imagesAction(): Action
    {
        return Action::make('images')
            ->label('Imágenes')
            // En el móvil, sólo el icono: la barra no cabe.
            ->labeledFrom('md')
            ->icon('heroicon-o-photo')
            ->color('gray')
            ->slideOver()
            ->modalWidth(Width::FourExtraLarge)
            ->modalHeading('Imágenes de la página')
            ->modalDescription('Las de sus bloques y su portada. Cambiar el título o el texto alternativo no toca el fichero; recortar o sustituir cambia la imagen en todos los sitios donde se usa.')
            ->modalContent(fn () => view('filament.admin.content.page-images', ['images' => $this->imageCards()]))
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Cerrar')
            ->visible(fn (): bool => $this->currentPage() !== null);
    }

    /**
     * @return list<array{file: File, blocks: list<int>, cover: bool, usages: list<array{type: string, label: string, id: int}>}>
     */
    public function imageCards(): array
    {
        $page = $this->currentPage();

        if ($page === null) {
            return [];
        }

        $images = app(ContentImageService::class);
        $blocks = $this->blockNumbers($page);
        $cards = [];

        foreach ($images->pageImages($page) as $file) {
            $cards[] = [
                'file' => $file,
                'blocks' => $blocks[$file->id] ?? [],
                'cover' => (int) $page->image_id === $file->id,
                // Los otros sitios: esta página no cuenta.
                'usages' => array_values(array_filter(
                    $images->usages($file),
                    fn (array $usage): bool => ! (in_array($usage['type'], ['page', 'page-cover'], true) && $usage['id'] === $page->id),
                )),
            ];
        }

        return $cards;
    }

    /**
     * En qué bloques está cada fichero: «Bloque 7».
     *
     * @return array<int, list<int>>
     */
    private function blockNumbers(ContentPage $page): array
    {
        $numbers = [];

        try {
            $json = $this->formatService()->contentIn($page, ContentPageFormatEnum::EditorJs);
            $blocks = $json === '' ? [] : $this->converter()->decodeBlocks($json);
        } catch (\Throwable) {
            return [];
        }

        foreach ($blocks as $index => $block) {
            $fileId = (int) ($block['data']['file']['file_id'] ?? 0);

            if ($fileId > 0) {
                $numbers[$fileId][] = $index + 1;
            }
        }

        return $numbers;
    }

    public function saveImageTexts(int $fileId, ?string $title, ?string $alt): void
    {
        $file = $this->pageImage($fileId);

        if ($file === null) {
            return;
        }

        app(ContentImageService::class)->updateTexts($file, $title ?? '', $alt ?? '');
        Notification::make()->success()->title('Textos de la imagen guardados')->send();
    }

    public function cropImage(int $fileId, int $x, int $y, int $width, int $height): void
    {
        $file = $this->pageImage($fileId);

        if ($file === null) {
            return;
        }

        try {
            app(ContentImageService::class)->crop($file, $x, $y, $width, $height);
        } catch (InvalidArgumentException $e) {
            Notification::make()->danger()->title('No se ha recortado')->body($e->getMessage())->send();

            return;
        }

        Notification::make()->success()->title('Imagen recortada')->body('Mismo fichero y mismas direcciones: los bloques la enseñan ya recortada.')->send();
    }

    public function replaceImage(int $fileId): void
    {
        $file = $this->pageImage($fileId);
        $upload = $this->replacement;
        $this->replacement = null;

        if ($file === null || ! $upload instanceof UploadedFile) {
            return;
        }

        try {
            app(ContentImageService::class)->replace($file, $upload);
        } catch (ContentUploadException|InvalidArgumentException $e) {
            Notification::make()->danger()->title('No se ha sustituido')->body($e->getMessage())->send();

            return;
        }

        Notification::make()->success()->title('Imagen sustituida')->body('Mismo fichero y mismas direcciones: los bloques enseñan ya la nueva.')->send();
    }

    /**
     * Una imagen de ESTA página: el id viene del navegador y no se da por
     * bueno.
     */
    private function pageImage(int $fileId): ?File
    {
        $page = $this->currentPage();

        if ($page === null || $this->readOnly || Gate::denies('update', $this->ownerContent())) {
            return null;
        }

        return app(ContentImageService::class)->pageImages($page)->firstWhere('id', $fileId);
    }

    // ── Utilidades ──────────────────────────────────────────────────────────

    protected function getHeaderActions(): array
    {
        return [$this->previewAction()];
    }

    protected function ownerContent(): Content
    {
        return $this->contentRecord();
    }

    protected function currentPage(): ?ContentPage
    {
        return $this->pageId === null ? null : ContentPage::query()->where('content_id', $this->ownerContent()->id)->find($this->pageId);
    }

    public function isReadOnly(): bool
    {
        return $this->readOnly;
    }

    /**
     * @return Builder<ContentPage>
     */
    private function pagesQuery(): Builder
    {
        return ContentPage::query()->where('content_id', $this->ownerContent()->id)->orderBy('order')->orderBy('id');
    }

    private function user(): User
    {
        $user = $this->currentUser();

        if ($user === null) {
            throw new AuthorizationException;
        }

        return $user;
    }

    private function locks(): ContentPageLockService
    {
        return app(ContentPageLockService::class);
    }

    private function drafts(): ContentPageDraftService
    {
        return app(ContentPageDraftService::class);
    }

    /**
     * Para el componente de la vista.
     *
     * @return array<string, mixed>
     */
    public function editorConfig(): array
    {
        $page = $this->currentPage();

        return [
            'autosaveMs' => self::AUTOSAVE_SECONDS * 1000,
            'releaseUrl' => $page !== null ? route('admin.contents.pages.lock.release', [$this->ownerContent(), $page]) : null,
            'csrfUrl' => route('admin.contents.editor.csrf-token', $this->ownerContent()),
            'uploadMaxBytes' => ContentFileService::MAX_IMAGE_BYTES,
        ];
    }
}
