<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Content\Contents\Pages\Concerns;

use App\Enums\ContentPageFormatEnum;
use App\Filament\Components\EditorJsField;
use App\Filament\Components\ImageCropperUpload;
use App\Models\Content\Content;
use App\Models\Content\ContentPage;
use App\Models\Content\ContentPageVersion;
use App\Models\User;
use App\Services\Content\ContentConversion;
use App\Services\Content\ContentFormatConverter;
use App\Services\Content\ContentPageFormatService;
use App\Services\Content\ContentPageHistoryService;
use Closure;
use Filament\Actions\Action;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\CodeEditor;
use Filament\Forms\Components\CodeEditor\Enums\Language;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Callout;
use Filament\Schemas\Components\Html;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * El formulario de una página: sus datos y su contenido en el formato en que
 * se escribe, con el camino para cambiar de formato. Viene del modal del
 * antiguo `PagesRelationManager` (F8 del plan de contenidos del 2026-09-24).
 *
 * Cada página se escribe en UN formato —Editor.js, Markdown o HTML—, su fuente:
 * sólo se ve y se edita el editor de ese formato, y al guardar los demás se
 * regeneran a partir de él (`ContentPageFormatService`). Una página nueva
 * empieza en Editor.js.
 *
 * Cambiar de formato es a propósito un camino con frenos, porque convertir
 * puede perder cosas:
 *
 *  1. «Pasar a …» convierte lo que hay en pantalla y lo enseña, con los avisos
 *     de lo que cambia, antes de aceptar. No guarda nada.
 *  2. Al aceptar, la página se abre en el formato nuevo con el resultado, para
 *     revisarlo y seguir escribiendo. «Deshacer» vuelve al formato de antes tal
 *     y como estaba, mientras no se haya guardado.
 *  3. Para guardar hay que marcar una casilla que dice qué va a pasar. Al
 *     guardar, lo anterior pasa al historial de versiones.
 *
 * Los campos ocultos (`source_format`, `stored_format`, `original_format`,
 * `pending_change`, `backup_id`) son el estado de ese camino, no columnas. Lo
 * que había antes del cambio, para «Deshacer», se guarda en la caché del
 * servidor y no en el formulario: antes viajaba en cada petición y una página
 * grande pasaba del límite de Livewire (G4).
 */
trait ContentPageForm
{
    /**
     * El contenido al que pertenecen las páginas.
     */
    abstract protected function ownerContent(): Content;

    /**
     * La página que se edita; null si es nueva.
     */
    abstract protected function currentPage(): ?ContentPage;

    /**
     * Si la página está en lectura (la tiene bloqueada otro).
     */
    abstract public function isReadOnly(): bool;

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->model(fn (): ContentPage|string => $this->currentPage() ?? ContentPage::class)
            // Si otro la tiene bloqueada, todo en lectura (P4).
            ->disabled(fn (): bool => $this->isReadOnly())
            ->components([
                Section::make('Datos de la página')
                    ->collapsible()
                    ->collapsed(fn (): bool => $this->currentPage() !== null)
                    ->columns(2)
                    ->schema([
                        TextInput::make('title')->maxLength(255)->required()->label('Título')
                            ->live(onBlur: true)
                            // Slug vacío: sale del título (C6).
                            ->afterStateUpdated(function (?string $state, Get $get, Set $set): void {
                                if (blank($get('slug')) && filled($state)) {
                                    $set('slug', $this->freeSlug(Str::slug($state)));
                                }
                            }),
                        TextInput::make('slug')->maxLength(255)->label('Slug')
                            ->rule('alpha_dash')
                            ->rule(fn (): Closure => $this->slugRule())
                            ->live(onBlur: true)
                            // Se comprueba al dejar de escribir, no al guardar.
                            ->afterStateUpdated(fn ($livewire, TextInput $component) => $livewire->validateOnly($component->getStatePath()))
                            ->helperText('Vacío, sale del título. Único dentro del contenido.'),
                        // Pedía `image_path`, una columna que no existe en
                        // ninguna tabla del proyecto (N232). La real es
                        // `image_id`, con su clave foránea a `files`.
                        ImageCropperUpload::makeImage('image_id')
                            ->asFileRecord()
                            ->storeFiles(false)
                            ->cover16x9()
                            ->directory('content-pages')
                            ->columnSpanFull()->label('Imagen de la página'),
                    ]),

                Hidden::make('source_format')->default(ContentPageFormatEnum::EditorJs->value),
                Hidden::make('stored_format'),
                Hidden::make('original_format'),
                Hidden::make('pending_change'),
                Hidden::make('backup_id'),

                $this->formatCallout(),

                // El JSON es el formato de almacenamiento de Editor.js, no la
                // interfaz: el editor visual es donde se escribe. La pestaña
                // de JSON en crudo está a propósito —pegar el JSON de otra
                // página es cómodo cuando se sabe lo que se hace—, pero va la
                // segunda y avisando.
                Tabs::make('Editor.js')
                    ->visible(fn (Get $get): bool => $this->currentFormat($get) === ContentPageFormatEnum::EditorJs)
                    ->columnSpanFull()
                    ->tabs([
                        Tab::make('Editor visual')
                            ->icon('heroicon-o-pencil-square')
                            ->schema([
                                EditorJsField::make('content_json')
                                    ->key('visual-editor')
                                    ->label('Contenido')
                                    ->hiddenLabel()
                                    ->allowRawHtml(fn (): bool => $this->isAdmin())
                                    ->content(fn (): Content => $this->ownerContent())
                                    // La misma regla en «JSON en crudo»:
                                    // comparten el estado y Filament se queda
                                    // con las reglas del último campo, que para
                                    // un Editor no existe.
                                    ->rules([fn (): Closure => $this->contentRule(ContentPageFormatEnum::EditorJs)])
                                    ->columnSpanFull(),
                            ]),

                        // Sólo administradores: por aquí entra cualquier cosa,
                        // también HTML que el editor no deja escribir (B1).
                        Tab::make('JSON en crudo')
                            ->key('raw-json')
                            ->icon('heroicon-o-code-bracket')
                            ->visible(fn (): bool => $this->isAdmin())
                            ->schema([
                                Textarea::make('content_json')
                                    ->label('JSON de Editor.js')
                                    ->helperText('Para pegar el contenido de otra página o corregir a mano. Tiene que ser un objeto con una clave «blocks»; al guardar se comprueba cada bloque, como con el editor visual.')
                                    ->rows(18)
                                    ->autosize()
                                    ->rules([fn (): Closure => $this->contentRule(ContentPageFormatEnum::EditorJs)])
                                    ->columnSpanFull(),
                            ]),
                    ]),

                Tabs::make('Markdown')
                    ->visible(fn (Get $get): bool => $this->currentFormat($get) === ContentPageFormatEnum::Markdown)
                    ->columnSpanFull()
                    ->tabs([
                        Tab::make('Markdown')
                            ->icon('heroicon-o-hashtag')
                            ->schema([
                                MarkdownEditor::make('content_markdown')
                                    ->label('Markdown')
                                    ->hiddenLabel()
                                    ->fileAttachments(false)
                                    ->live(onBlur: true)
                                    ->rules([fn (): Closure => $this->contentRule(ContentPageFormatEnum::Markdown)])
                                    ->helperText('Markdown con tablas y listas de tareas. Los <div data-editorjs-block> son bloques de Editor.js que Markdown no sabe expresar: se ven igual en la web y, si no los tocas, vuelven intactos al pasar a Editor.js.')
                                    ->columnSpanFull(),
                            ]),

                        Tab::make('Vista previa')
                            ->icon('heroicon-o-eye')
                            ->schema([
                                Html::make(fn (Get $get): HtmlString => $this->preview((string) $get('content_markdown'), ContentPageFormatEnum::Markdown)),
                            ]),
                    ]),

                Tabs::make('HTML')
                    ->visible(fn (Get $get): bool => $this->currentFormat($get) === ContentPageFormatEnum::Html)
                    ->columnSpanFull()
                    ->tabs([
                        // Editor de código y no el RichEditor de antes: el
                        // RichEditor pasa el HTML por TipTap al cargar y al
                        // guardar, y en las páginas reales se comía entre el
                        // 70 y el 80 % del marcado.
                        Tab::make('Código HTML')
                            ->icon('heroicon-o-code-bracket')
                            ->schema([
                                CodeEditor::make('content_html')
                                    ->label('HTML')
                                    ->hiddenLabel()
                                    ->language(Language::Html)
                                    ->live(onBlur: true)
                                    // El HTML se sirve tal cual: sólo lo cambia
                                    // un administrador. Deshabilitado no se
                                    // envía, así que un Editor guarda el resto
                                    // sin tocarlo.
                                    ->disabled(fn (): bool => ! $this->isAdmin())
                                    ->rules([fn (): Closure => $this->contentRule(ContentPageFormatEnum::Html)])
                                    ->helperText(fn (): string => $this->isAdmin()
                                        ? 'Se sirve tal cual, quitando sólo los envoltorios <div data-editorjs-block>, que guardan los bloques de Editor.js para poder volver a él sin perderlos.'
                                        : 'Esta página está escrita en HTML, que se sirve tal cual: sólo un administrador puede cambiarlo. Puedes cambiar el título, el slug y la imagen.')
                                    ->columnSpanFull(),
                            ]),

                        Tab::make('Vista previa')
                            ->icon('heroicon-o-eye')
                            ->schema([
                                Html::make(fn (Get $get): HtmlString => $this->preview((string) $get('content_html'), ContentPageFormatEnum::Html)),
                            ]),
                    ]),

                Checkbox::make('confirm_format_change')
                    ->label(fn (Get $get): string => $this->confirmationLabel($get))
                    ->visible(fn (Get $get): bool => filled($get('pending_change')) && filled($get('stored_format')))
                    ->accepted()
                    ->validationMessages(['accepted' => 'Marca la casilla para confirmar el cambio antes de guardar.'])
                    ->columnSpanFull(),
            ]);
    }

    // ── Cambio de formato ───────────────────────────────────────────────────

    private function formatCallout(): Callout
    {
        return Callout::make(function (Get $get): string {
            $heading = 'Formato de la página: '.$this->currentFormat($get)->label();

            return filled($get('pending_change')) ? $heading.' (sin guardar)' : $heading;
        })
            ->description(fn (Get $get): string => $this->calloutDescription($get))
            ->key('format')
            ->status(fn (Get $get): string => filled($get('pending_change')) ? 'warning' : 'info')
            ->actions([
                ...array_map(fn (ContentPageFormatEnum $target): Action => $this->convertAction($target), ContentPageFormatEnum::cases()),
                $this->undoAction(),
            ])
            ->columnSpanFull();
    }

    private function calloutDescription(Get $get): string
    {
        $current = $this->currentFormat($get)->label();
        $original = ContentPageFormatEnum::tryFrom((string) $get('original_format'))?->label() ?? $current;

        return match ($get('pending_change')) {
            'convert' => "Has pasado la página de {$original} a {$current} y todavía no se ha guardado nada. "
                ."Revisa el resultado: al guardar, {$current} será el formato de la página y los demás se regenerarán desde aquí. "
                ."Si algo no está bien, «Deshacer» vuelve a {$original} tal y como estaba.",
            'restore' => "Has cargado una versión del historial, en {$current}, y todavía no se ha guardado nada. "
                .'Revísala: al guardar sustituye al contenido actual, que pasa al historial. '
                ."«Deshacer» vuelve a {$original} tal y como estaba.",
            default => 'Es el único formato que se edita y el que la API sirve por defecto. Los demás se generan a partir de este al guardar.',
        };
    }

    private function confirmationLabel(Get $get): string
    {
        $stored = ContentPageFormatEnum::tryFrom((string) $get('stored_format'))?->label() ?? '';
        $current = $this->currentFormat($get)->label();

        if ($get('pending_change') === 'restore') {
            return "Entiendo que al guardar el contenido actual se sustituye por la versión cargada, en {$current}. Lo que hay ahora pasa al historial.";
        }

        if ($stored === $current) {
            return 'Entiendo que al guardar el contenido se sustituye por el que ha salido de la conversión, y que si la conversión ha estropeado algo no se arregla solo: habrá que recuperarlo desde el historial.';
        }

        return "Entiendo que la página pasa de {$stored} a {$current}: al guardar, {$current} será su formato y los demás se regenerarán desde él. "
            .'Si la conversión ha estropeado algo, no se arregla solo: habrá que recuperarlo desde el historial.';
    }

    private function convertAction(ContentPageFormatEnum $target): Action
    {
        return Action::make('convertTo'.Str::studly($target->value))
            ->label('Pasar a '.$target->label())
            ->icon('heroicon-o-arrows-right-left')
            ->color('gray')
            ->button()
            // A HTML sólo pasa un administrador: el HTML se sirve tal cual.
            ->visible(fn (Get $get): bool => ! $this->isReadOnly() && $this->currentFormat($get) !== $target
                && ($target !== ContentPageFormatEnum::Html || $this->isAdmin()))
            ->modalHeading('Pasar la página a '.$target->label())
            ->modalDescription(fn (Get $get): string => sprintf(
                'Esto es lo que sale al convertir a %s lo que tienes ahora en %s. Todavía no se guarda nada: si aceptas, la página se abre en %s para que lo revises, y hasta que no guardes puedes deshacerlo.',
                $target->label(),
                $this->currentFormat($get)->label(),
                $target->label(),
            ))
            ->modalWidth(Width::FiveExtraLarge)
            ->fillForm(function (Get $get) use ($target): array {
                $from = $this->currentFormat($get);
                $content = (string) $get($from->formField());

                if (trim($content) === '') {
                    return ['result' => '', 'notes' => 'La página está vacía: sólo cambia el formato.'];
                }

                try {
                    $conversion = $this->converter()->convert($content, $from, $target);
                } catch (InvalidArgumentException $e) {
                    return ['result' => '', 'notes' => 'No se puede convertir: '.$e->getMessage(), 'failed' => '1'];
                }

                return ['result' => $conversion->content, 'notes' => $this->notes($conversion)];
            })
            ->schema([
                Hidden::make('failed'),
                Hidden::make('notes'),
                Callout::make('Qué cambia')
                    ->description(fn (Get $get): HtmlString => $this->notesHtml((string) $get('notes')))
                    ->warning(),
                Textarea::make('result')
                    ->label('Resultado en '.$target->label())
                    ->readOnly()
                    ->rows(16),
            ])
            ->modalSubmitActionLabel('Convertir y editar en '.$target->label())
            ->action(function (array $data, Get $get, Set $set, Action $action) use ($target): void {
                if (filled($data['failed'] ?? null)) {
                    Notification::make()->danger()->title('No se ha cambiado el formato')->body('La conversión ha fallado; la página sigue como estaba.')->send();
                    $action->halt();
                }

                $this->applyChange($get, $set, $target, (string) ($data['result'] ?? ''), 'convert');
            });
    }

    private function undoAction(): Action
    {
        return Action::make('undoFormatChange')
            ->label(fn (Get $get): string => 'Deshacer y volver a '.(ContentPageFormatEnum::tryFrom((string) $get('original_format'))?->label() ?? 'como estaba'))
            ->icon('heroicon-o-arrow-uturn-left')
            ->color('warning')
            ->button()
            ->visible(fn (Get $get): bool => ! $this->isReadOnly() && filled($get('pending_change')))
            ->requiresConfirmation()
            ->modalHeading('Deshacer el cambio')
            ->modalDescription('Se descarta lo convertido y lo que hayas escrito después, y la página vuelve a estar como antes del cambio.')
            ->modalSubmitActionLabel('Deshacer')
            ->action(function (Get $get, Set $set): void {
                $original = Cache::get($this->undoKey());

                if (! is_array($original)) {
                    Notification::make()->warning()->title('Ya no se puede deshacer')->body('Lo de antes del cambio ya no está guardado. Recarga la página para volver a lo guardado.')->send();

                    return;
                }

                $format = ContentPageFormatEnum::tryFrom((string) ($original['format'] ?? '')) ?? ContentPageFormatEnum::EditorJs;

                $set($format->formField(), (string) ($original['content'] ?? ''));
                $set('source_format', $format->value);
                $set('original_format', null);
                $set('pending_change', null);
                $set('confirm_format_change', false);
                Cache::forget($this->undoKey());
            });
    }

    /**
     * Abre la página en `$target` con `$content`, sin guardar.
     *
     * Lo que había antes del primer cambio se aparta, en la caché del
     * servidor, para poder deshacerlo.
     */
    private function applyChange(Get $get, Set $set, ContentPageFormatEnum $target, string $content, string $kind): void
    {
        $current = $this->currentFormat($get);

        if (blank($get('pending_change'))) {
            $set('original_format', $current->value);
            Cache::put($this->undoKey(), ['format' => $current->value, 'content' => (string) $get($current->formField())], now()->addDay());
        }

        // El editor del formato que se deja se vacía: no viaja dos veces.
        $set($current->formField(), null);
        $set($target->formField(), $content);
        $set('source_format', $target->value);
        $set('pending_change', $kind);
        $set('confirm_format_change', false);
    }

    /**
     * Clave de la caché con lo de antes del cambio: de esta pestaña y esta
     * página.
     */
    private function undoKey(): string
    {
        return sprintf('content-page-undo:%s:%s', $this->getId(), $this->currentPage()->id ?? 'new');
    }

    /**
     * Regla del campo del contenido: lo que no se podría guardar se dice en el
     * formulario, antes de intentarlo (bloques sin su dato principal, HTML
     * libre de quien no es administrador…).
     */
    private function contentRule(ContentPageFormatEnum $format): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($format): void {
            $problems = $this->formatService()->problems($this->currentPage(), $format, is_string($value) ? $value : null, $this->currentUser());

            if ($problems !== []) {
                $fail(implode(' ', $problems));
            }
        };
    }

    /**
     * Slug único dentro del contenido (C6). Una página de la papelera también
     * lo ocupa: si se restaura, chocaría.
     */
    private function slugRule(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            if (blank($value)) {
                return;
            }

            $other = $this->pageWithSlug((string) $value);

            if ($other !== null) {
                $fail(sprintf('Ese slug ya lo usa la página «%s»%s.', $other->title, $other->trashed() ? ', que está en la papelera' : ''));
            }
        };
    }

    private function pageWithSlug(string $slug): ?ContentPage
    {
        return ContentPage::withTrashed()
            ->where('content_id', $this->ownerContent()->id)
            ->where('slug', $slug)
            ->when($this->currentPage() !== null, fn ($query) => $query->whereKeyNot($this->currentPage()?->id))
            ->first(['id', 'title', 'deleted_at']);
    }

    /**
     * El slug que sale del título, con «-2», «-3»… si ya lo tiene otra página.
     */
    private function freeSlug(string $base): string
    {
        $base = $base !== '' ? $base : 'pagina';
        $slug = $base;

        for ($i = 2; $this->pageWithSlug($slug) !== null; $i++) {
            $slug = "{$base}-{$i}";
        }

        return $slug;
    }

    // ── Utilidades ──────────────────────────────────────────────────────────

    private function currentUser(): ?User
    {
        $user = auth()->user();

        return $user instanceof User ? $user : null;
    }

    private function isAdmin(): bool
    {
        return $this->currentUser()?->isAdmin() ?? false;
    }

    /**
     * JSON indentado para leerlo, o tal cual si no es JSON.
     */
    private function prettyJson(string $json): string
    {
        $decoded = json_decode($json, true);

        return is_array($decoded)
            ? (string) json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            : $json;
    }

    private function currentFormat(Get $get): ContentPageFormatEnum
    {
        return ContentPageFormatEnum::tryFrom((string) $get('source_format')) ?? ContentPageFormatEnum::EditorJs;
    }

    /**
     * Versión del historial de ESTA página: el id viene del formulario y no se
     * da por bueno.
     */
    private function versionFor(mixed $versionId): ?ContentPageVersion
    {
        $page = $this->currentPage();

        if ($page === null || blank($versionId)) {
            return null;
        }

        return app(ContentPageHistoryService::class)->find($page, $versionId);
    }

    /**
     * HTML de la vista previa de lo que se está escribiendo, saneado: todavía
     * no ha pasado por la limpieza de guardar (D37).
     */
    private function preview(string $content, ContentPageFormatEnum $format): HtmlString
    {
        if (trim($content) === '') {
            return new HtmlString('<p class="fi-prose">La página está vacía.</p>');
        }

        return new HtmlString('<div class="fi-prose">'.Str::sanitizeHtml($this->converter()->toServedHtml($content, $format)).'</div>');
    }

    private function notes(ContentConversion $conversion): string
    {
        $notes = array_filter([$conversion->summary, ...$conversion->warnings]);

        return $notes === [] ? 'Nada que avisar: la conversión no pierde nada.' : implode("\n", $notes);
    }

    /**
     * Las notas vienen del estado del formulario: se escapan al pintarlas.
     */
    private function notesHtml(string $notes): HtmlString
    {
        $lines = array_filter(explode("\n", $notes), fn (string $line): bool => trim($line) !== '');

        return new HtmlString(implode('<br>', array_map(fn (string $line): string => '• '.e($line), $lines)));
    }

    private function converter(): ContentFormatConverter
    {
        return app(ContentFormatConverter::class);
    }

    private function formatService(): ContentPageFormatService
    {
        return app(ContentPageFormatService::class);
    }
}
