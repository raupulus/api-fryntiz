<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Content\Contents\Pages\Concerns;

use App\Models\AiProvider;
use App\Models\Content\Content;
use App\Models\File;
use App\Models\Platform;
use App\Services\Content\ContentAiImageService;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Locked;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

/**
 * Portadas con IA en el campo `image_id` de una página del panel: lo que hay
 * detrás de «Generar con IA», «Mejorar con IA» y «Deshacer», las acciones del
 * propio campo (`AiCoverImageActions`). Lo usan «Datos» de la entrada
 * (`EditContent`) y el editor de páginas (`ManageContentPages`).
 *
 * La imagen elegida la mete el navegador en el campo como una subida más, sin
 * guardar: se guarda con el formulario, por el camino de siempre (D40, D53).
 * Hasta guardar, «Deshacer» devuelve el campo a como estaba.
 */
trait HasAiCoverImages
{
    /**
     * Lo que tenía el campo antes de la primera imagen de la IA; vacío si no
     * hay nada que deshacer.
     *
     * @var array{state?: mixed}
     */
    #[Locked]
    public array $aiCoverUndo = [];

    /**
     * La entrada de la portada; `null` si aún no existe (al crearla).
     */
    abstract protected function aiCoverContent(): ?Content;

    /**
     * La página de la portada, si es la de una página.
     */
    abstract protected function aiCoverPageId(): ?int;

    /**
     * La portada guardada: la única de `files` que se acepta del campo.
     */
    abstract protected function aiCoverSavedId(): ?int;

    protected function aiCoverEditable(): bool
    {
        return true;
    }

    public function hasAiCoverUndo(): bool
    {
        return $this->aiCoverUndo !== [];
    }

    /**
     * Si se puede usar: con la entrada creada, permiso para editarla, algún
     * proveedor con modelo de imágenes y, para mejorar, una imagen en el campo
     * y algún proveedor que sepa mejorarla.
     */
    public function aiCoverAvailable(string $mode = 'generate'): bool
    {
        $content = $this->aiCoverContent();

        if ($content === null || ! $this->aiCoverEditable() || Gate::denies('update', $content)) {
            return false;
        }

        $providers = $this->aiCoverProviders($mode);

        return $mode === 'improve'
            ? $providers !== [] && $this->aiCoverSource() !== null
            : $providers !== [];
    }

    /**
     * Lo que necesita el modal.
     *
     * @return array<string, mixed>
     */
    public function aiCoverModalData(string $mode): array
    {
        $content = $this->aiCoverContent();
        abort_unless($content !== null, 404);

        $source = $mode === 'improve' ? $this->aiCoverSource() : null;
        $platform = $content->platform;

        return [
            'mode' => $mode,
            'providers' => $this->aiCoverProviders($mode),
            'streamUrl' => route('admin.contents.ai.images', $content),
            'pageId' => $this->aiCoverPageId(),
            'colors' => $platform instanceof Platform ? $platform->themeColors() : [],
            'maxImages' => ContentAiImageService::MAX_IMAGES,
            'sourceToken' => $source === null ? null : app(ContentAiImageService::class)->registerSource($source, $content, (int) auth()->id()),
            'sourceUrl' => match (true) {
                $source instanceof File => $source->url,
                $source instanceof TemporaryUploadedFile => $source->temporaryUrl(),
                default => null,
            },
        ];
    }

    /**
     * Apunta cómo estaba el campo antes de la primera imagen de la IA, para
     * «Deshacer». La imagen la mete después el navegador en el campo, como
     * una subida más.
     */
    public function rememberAiCover(): void
    {
        $content = $this->aiCoverContent();
        abort_unless($content !== null && $this->aiCoverEditable() && Gate::allows('update', $content), 403);

        if ($this->aiCoverUndo === []) {
            $this->aiCoverUndo = ['state' => $this->data['image_id'] ?? null];
        }

        Notification::make()
            ->title('Portada puesta, sin guardar')
            ->body('Guarda para quedártela. Hasta entonces, «Deshacer» en el campo la quita.')
            ->success()
            ->send();
    }

    /**
     * Cómo estaba el campo antes de la primera imagen de la IA, para que
     * «Deshacer» se lo vuelva a poner (`AiCoverImageActions::attach()`). Una
     * subida que no se había guardado no se puede devolver: el campo queda
     * vacío.
     *
     * @return array<string, int|string>|null
     */
    public function takeAiCoverUndo(): ?array
    {
        $content = $this->aiCoverContent();
        abort_unless($content !== null && $this->aiCoverEditable() && Gate::allows('update', $content), 403);

        $state = $this->aiCoverUndo['state'] ?? null;
        $this->aiCoverUndo = [];

        Notification::make()->title('Portada como estaba')->success()->send();

        if (! is_array($state) || $state === []) {
            return null;
        }

        foreach ($state as $value) {
            if (! is_int($value) && ! is_string($value)) {
                return null;
            }
        }

        return $state;
    }

    /**
     * Al guardar, lo de la IA ya es la portada: no hay nada que deshacer.
     */
    protected function forgetAiCoverUndo(): void
    {
        $this->aiCoverUndo = [];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function aiCoverProviders(string $mode): array
    {
        return AiProvider::usableForImages()
            ->filter(fn (AiProvider $provider): bool => $mode !== 'improve' || $provider->provider->supportsImageEditing())
            ->map(fn (AiProvider $provider): array => [
                'value' => $provider->provider->value,
                'label' => $provider->imageLabel(),
                'name' => $provider->provider->label(),
                'qualities' => collect($provider->provider->imageQualities())
                    ->map(fn (string $label, string $value): array => ['value' => $value, 'label' => $label])
                    ->values()->all(),
                'defaultQuality' => $provider->provider->defaultImageQuality(),
            ])
            ->values()
            ->all();
    }

    /**
     * La imagen que hay en el campo: una recién subida (o elegida) o la
     * portada guardada. Un id cualquiera que llegue del navegador no vale.
     */
    private function aiCoverSource(): TemporaryUploadedFile|File|null
    {
        $state = $this->data['image_id'] ?? null;
        $value = is_array($state) ? (reset($state) ?: null) : $state;

        if ($value instanceof TemporaryUploadedFile) {
            return $value;
        }

        $saved = $this->aiCoverSavedId();

        return is_numeric($value) && $saved !== null && (int) $value === $saved
            ? File::query()->find($saved)
            : null;
    }
}
