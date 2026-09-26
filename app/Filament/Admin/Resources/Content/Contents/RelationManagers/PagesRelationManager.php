<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Content\Contents\RelationManagers;

use App\Enums\ContentPageFormatEnum;
use App\Enums\ContentPageVersionReasonEnum;
use App\Exceptions\ContentPageConflictException;
use App\Filament\Components\EditorJsField;
use App\Filament\Components\ImageCropperUpload;
use App\Filament\Concerns\HasImageFileUpload;
use App\Models\Content\Content;
use App\Models\Content\ContentPage;
use App\Models\Content\ContentPageVersion;
use App\Models\User;
use App\Services\Content\ContentConversion;
use App\Services\Content\ContentFormatConverter;
use App\Services\Content\ContentPageDraftService;
use App\Services\Content\ContentPageFormatService;
use App\Services\Content\ContentPageHistoryService;
use Closure;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\CodeEditor;
use Filament\Forms\Components\CodeEditor\Enums\Language;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Callout;
use Filament\Schemas\Components\Html;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use RuntimeException;

/**
 * Páginas de un contenido.
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
 *     guardar, lo anterior pasa al historial de versiones y se puede traer de
 *     vuelta con «Recuperar versión anterior», que sigue el mismo camino.
 *
 * Los campos ocultos (`source_format`, `stored_format`, `original_*`,
 * `pending_change`, `backup_id`, `opened_at`) son el estado de ese camino, no
 * columnas. `opened_at` es el `updated_at` de la página al abrir el modal: si
 * alguien la guarda mientras tanto, no se pisa (D4) y lo escrito queda en el
 * borrador de quien guardaba.
 */
class PagesRelationManager extends RelationManager
{
    use HasImageFileUpload;

    protected static string $relationship = 'pages';

    protected static ?string $title = 'Páginas';

    protected static ?string $recordTitleAttribute = 'title';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('title')->maxLength(255)->required()->label('Título'),
            TextInput::make('slug')->maxLength(255)->label('Slug'),
            TextInput::make('order')->numeric()->default(0)->label('Orden'),

            Hidden::make('source_format')->default(ContentPageFormatEnum::EditorJs->value),
            Hidden::make('stored_format'),
            Hidden::make('original_format'),
            Hidden::make('original_content'),
            Hidden::make('pending_change'),
            Hidden::make('backup_id'),
            Hidden::make('opened_at'),

            $this->formatCallout(),

            // El JSON es el formato de almacenamiento de Editor.js, no la
            // interfaz: el editor visual es donde se escribe. La pestaña de JSON
            // en crudo está a propósito —pegar el JSON de otra página es cómodo
            // cuando se sabe lo que se hace—, pero va la segunda y avisando.
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
                                ->helperText('Editor.js. Al guardar se regeneran el HTML que se sirve a la web y la versión en Markdown.')
                                ->allowRawHtml(fn (): bool => $this->isAdmin())
                                ->content(fn () => $this->getOwnerRecord())
                                // La misma regla en «JSON en crudo»: comparten el
                                // estado y Filament se queda con las reglas del
                                // último campo, que para un Editor no existe.
                                ->rules([fn (?ContentPage $record): Closure => $this->contentRule($record, ContentPageFormatEnum::EditorJs)])
                                ->columnSpanFull(),
                        ]),

                    // Sólo administradores: por aquí entra cualquier cosa, también
                    // HTML que el editor no deja escribir (B1).
                    Tab::make('JSON en crudo')
                        ->key('raw-json')
                        ->icon('heroicon-o-code-bracket')
                        ->visible(fn (): bool => $this->isAdmin())
                        ->schema([
                            // Mismo estado que el editor visual: lo que se pegue
                            // aquí se ve allí al cambiar de pestaña, y al revés.
                            Textarea::make('content_json')
                                ->label('JSON de Editor.js')
                                ->helperText('Para pegar el contenido de otra página o corregir a mano. Tiene que ser un objeto con una clave «blocks»; al guardar se comprueba cada bloque, como con el editor visual.')
                                ->rows(18)
                                ->autosize()
                                ->rules([fn (?ContentPage $record): Closure => $this->contentRule($record, ContentPageFormatEnum::EditorJs)])
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
                                ->rules([fn (?ContentPage $record): Closure => $this->contentRule($record, ContentPageFormatEnum::Markdown)])
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
                    // Editor de código y no el RichEditor de antes: el RichEditor
                    // pasa el HTML por TipTap al cargar y al guardar, y en las
                    // páginas reales se comía entre el 70 y el 80 % del marcado
                    // (figuras, pies de foto, clases de las tablas).
                    Tab::make('Código HTML')
                        ->icon('heroicon-o-code-bracket')
                        ->schema([
                            CodeEditor::make('content_html')
                                ->label('HTML')
                                ->hiddenLabel()
                                ->language(Language::Html)
                                ->live(onBlur: true)
                                // El HTML se sirve tal cual: sólo lo cambia un
                                // administrador. Deshabilitado no se envía, así
                                // que un Editor guarda el resto sin tocarlo.
                                ->disabled(fn (): bool => ! $this->isAdmin())
                                ->rules([fn (?ContentPage $record): Closure => $this->contentRule($record, ContentPageFormatEnum::Html)])
                                ->helperText(fn (): string => $this->isAdmin()
                                    ? 'Se sirve tal cual, quitando sólo los envoltorios <div data-editorjs-block>, que guardan los bloques de Editor.js para poder volver a él sin perderlos.'
                                    : 'Esta página está escrita en HTML, que se sirve tal cual: sólo un administrador puede cambiarlo. Puedes cambiar el título, el slug, el orden y la imagen.')
                                ->columnSpanFull(),
                        ]),

                    Tab::make('Vista previa')
                        ->icon('heroicon-o-eye')
                        ->schema([
                            Html::make(fn (Get $get): HtmlString => $this->preview((string) $get('content_html'), ContentPageFormatEnum::Html)),
                        ]),
                ]),

            // Pedía `image_path`, una columna que no existe en ninguna tabla del
            // proyecto, así que la imagen se perdía al guardar sin dar ningún
            // error (N232). La columna real es `image_id`, con su clave foránea
            // a `files`.
            ImageCropperUpload::makeImage('image_id')
                ->asFileRecord()
                ->storeFiles(false)
                ->cover16x9()
                ->directory('content-pages')
                ->columnSpanFull()->label('Imagen de la página'),

            Checkbox::make('confirm_format_change')
                ->label(fn (Get $get): string => $this->confirmationLabel($get))
                ->visible(fn (Get $get): bool => filled($get('pending_change')) && filled($get('stored_format')))
                ->accepted()
                ->validationMessages(['accepted' => 'Marca la casilla para confirmar el cambio antes de guardar.'])
                ->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('order')->sortable()->label('Orden'),
                TextColumn::make('title')->label('Título'),
                // En el móvil sobran: se toca la fila para abrir la página (E3).
                TextColumn::make('slug')->label('Slug')->toggleable()->visibleFrom('md'),
                TextColumn::make('currentRawType.type')
                    ->label('Formato')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => ContentPageFormatEnum::fromRawType((string) $state)?->label() ?? '—')
                    ->toggleable()
                    ->visibleFrom('md'),
            ])
            // Sin el filtro de la papelera por defecto: lo pone `TrashedFilter`.
            ->modifyQueryUsing(fn (Builder $query) => $query->with('currentRawType')->withoutGlobalScopes([SoftDeletingScope::class]))
            ->filters([
                TrashedFilter::make()
                    ->label('Papelera')
                    ->placeholder('Sin la papelera')
                    ->trueLabel('Todas, también la papelera')
                    ->falseLabel('Sólo la papelera'),
            ])
            ->recordAction('edit')
            ->reorderable('order')
            ->defaultSort('order')
            ->headerActions([
                CreateAction::make()
                    ->label('Añadir página')
                    ->using(fn (array $data, Action $action): ContentPage => $this->savePage(
                        new ContentPage(['content_id' => $this->getOwnerRecord()->getKey()]),
                        $data,
                        $action,
                    )),
            ])
            ->recordActions([
                EditAction::make()
                    ->mutateRecordDataUsing(function (array $data, ContentPage $record): array {
                        $service = $this->formatService();
                        $format = $service->sourceFormat($record);

                        $content = $service->sourceContent($record);

                        $data['source_format'] = $format->value;
                        $data['stored_format'] = $format->value;
                        // Indentado: en «JSON en crudo» se lee (C9).
                        $data[$format->formField()] = $format === ContentPageFormatEnum::EditorJs ? $this->prettyJson($content) : $content;
                        $data['backup_id'] = $service->latestBackup($record)?->id;
                        $data['opened_at'] = $record->updated_at?->toIso8601String();

                        return $data;
                    })
                    ->using(fn (ContentPage $record, array $data, Action $action): ContentPage => $this->savePage($record, $data, $action))
                    ->hidden(fn (ContentPage $record): bool => $record->trashed()),
                // En un menú, para que en el móvil no se salgan (E3).
                ActionGroup::make([
                    // A la papelera, y las de detrás suben un puesto (G3).
                    DeleteAction::make()
                        ->label('Eliminar')
                        ->using(fn (ContentPage $record): bool => $record->safeDelete()),
                    // Vuelve al final, para no chocar con el orden de las demás.
                    RestoreAction::make()
                        ->label('Restaurar')
                        ->using(function (ContentPage $record): bool {
                            $record->order = (int) ContentPage::query()->where('content_id', $record->content_id)->max('order') + 1;
                            $record->restore();

                            return true;
                        }),
                    ForceDeleteAction::make()->label('Eliminar definitivamente'),
                ]),
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
                $this->restoreAction(),
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
            'restore' => "Has cargado la versión anterior, en {$current}, y todavía no se ha guardado nada. "
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
            return 'Entiendo que al guardar el contenido se sustituye por el que ha salido de la conversión, y que si la conversión ha estropeado algo no se arregla solo: habrá que recuperarlo con «Recuperar versión anterior».';
        }

        return "Entiendo que la página pasa de {$stored} a {$current}: al guardar, {$current} será su formato y los demás se regenerarán desde él. "
            .'Si la conversión ha estropeado algo, no se arregla solo: habrá que recuperarlo con «Recuperar versión anterior».';
    }

    private function convertAction(ContentPageFormatEnum $target): Action
    {
        return Action::make('convertTo'.Str::studly($target->value))
            ->label('Pasar a '.$target->label())
            ->icon('heroicon-o-arrows-right-left')
            ->color('gray')
            ->button()
            // A HTML sólo pasa un administrador: el HTML se sirve tal cual.
            ->visible(fn (Get $get): bool => $this->currentFormat($get) !== $target
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
            ->visible(fn (Get $get): bool => filled($get('pending_change')))
            ->requiresConfirmation()
            ->modalHeading('Deshacer el cambio')
            ->modalDescription('Se descarta lo convertido y lo que hayas escrito después, y la página vuelve a estar como antes del cambio.')
            ->modalSubmitActionLabel('Deshacer')
            ->action(function (Get $get, Set $set): void {
                $original = ContentPageFormatEnum::tryFrom((string) $get('original_format')) ?? ContentPageFormatEnum::EditorJs;

                $set($original->formField(), $get('original_content'));
                $set('source_format', $original->value);
                $set('original_format', null);
                $set('original_content', null);
                $set('pending_change', null);
                $set('confirm_format_change', false);
            });
    }

    private function restoreAction(): Action
    {
        return Action::make('restoreBackup')
            ->label('Recuperar versión anterior')
            ->icon('heroicon-o-clock')
            ->color('gray')
            ->button()
            ->visible(fn (Get $get): bool => filled($get('backup_id')) && blank($get('pending_change')))
            ->modalHeading('Recuperar la versión anterior')
            ->modalDescription('Es la última versión del historial: lo que había antes del último cambio. Todavía no se guarda nada: si aceptas, se abre en el editor para que la revises, y hasta que no guardes puedes deshacerlo.')
            ->modalWidth(Width::FiveExtraLarge)
            ->fillForm(function (Get $get, ?ContentPage $record): array {
                $backup = $this->backupFor($record, $get('backup_id'));

                if ($backup === null) {
                    return ['result' => '', 'notes' => 'No se ha encontrado la versión.', 'failed' => '1'];
                }

                return [
                    'result' => $backup->content,
                    'notes' => sprintf(
                        'Versión en %s del %s, guardada en el historial por: %s.',
                        $backup->format->label(),
                        $backup->created_at?->timezone(config('app.display_timezone', 'Europe/Madrid'))->format('d/m/Y H:i'),
                        mb_strtolower($backup->reason->label()),
                    ),
                ];
            })
            ->schema([
                Hidden::make('failed'),
                Hidden::make('notes'),
                Callout::make('Versión')
                    ->description(fn (Get $get): HtmlString => $this->notesHtml((string) $get('notes')))
                    ->info(),
                Textarea::make('result')->label('Contenido de la versión')->readOnly()->rows(16),
            ])
            ->modalSubmitActionLabel('Cargar esta versión')
            ->action(function (array $data, Get $get, Set $set, Action $action, ?ContentPage $record): void {
                $backup = $this->backupFor($record, $get('backup_id'));

                if ($backup === null) {
                    Notification::make()->danger()->title('No se ha encontrado la versión')->send();
                    $action->halt();

                    return;
                }

                // El contenido sale de la base de datos, no del formulario.
                $this->applyChange($get, $set, $backup->format, $backup->content, 'restore');
            });
    }

    /**
     * Abre la página en `$target` con `$content`, sin guardar.
     *
     * Lo que había antes del primer cambio se aparta para poder deshacerlo.
     */
    private function applyChange(Get $get, Set $set, ContentPageFormatEnum $target, string $content, string $kind): void
    {
        $current = $this->currentFormat($get);

        if (blank($get('pending_change'))) {
            $set('original_format', $current->value);
            $set('original_content', (string) $get($current->formField()));
        }

        $set($target->formField(), $content);
        $set('source_format', $target->value);
        $set('pending_change', $kind);
        $set('confirm_format_change', false);
    }

    // ── Guardado ────────────────────────────────────────────────────────────

    /**
     * Guarda los datos de la página y su contenido juntos
     * (`ContentPageFormatService::savePage()`): si algo falla no se guarda
     * nada, el modal sigue abierto con lo escrito y se dice por qué.
     *
     * @param  array<string, mixed>  $data
     */
    private function savePage(ContentPage $page, array $data, Action $action): ContentPage
    {
        $format = ContentPageFormatEnum::tryFrom((string) ($data['source_format'] ?? '')) ?? ContentPageFormatEnum::EditorJs;
        $content = isset($data[$format->formField()]) ? (string) $data[$format->formField()] : null;
        $reason = match ($data['pending_change'] ?? null) {
            'restore' => ContentPageVersionReasonEnum::Restore,
            'convert' => ContentPageVersionReasonEnum::FormatChange,
            default => null,
        };
        $keepBackup = $reason !== null;
        $openedAt = filled($data['opened_at'] ?? null) ? Carbon::parse((string) $data['opened_at']) : null;

        try {
            $saved = $this->formatService()->savePage($page, $this->pageAttributes($data), $format, $content, $reason, $this->currentUser(), $openedAt);
        } catch (ContentPageConflictException $e) {
            $this->keepInDraft($page, $data, $format, $content, $openedAt);
            $this->refuse($action, $e->getMessage());
        } catch (ValidationException $e) {
            $this->refuse($action, implode(' ', Arr::flatten($e->errors())));
        } catch (InvalidArgumentException|RuntimeException $e) {
            $this->refuse($action, $e->getMessage());
        }

        if (! $saved && $keepBackup) {
            Notification::make()
                ->warning()
                ->title('No se ha cambiado el formato')
                ->body('El contenido estaba vacío, así que la página se queda como estaba.')
                ->send();

            return $page;
        }

        $headings = $format === ContentPageFormatEnum::EditorJs
            ? 0
            : $this->converter()->topLevelHeadings((string) $page->refresh()->content);

        if ($headings > 0) {
            Notification::make()
                ->warning()
                ->title($headings === 1 ? 'La página tiene 1 título h1 o h2' : "La página tiene {$headings} títulos h1 o h2")
                ->body('Se ha guardado igual, pero en la web el h1 es el título del contenido y el h2 el de la página: dentro del texto, los títulos van mejor del h3 al h6 (### en Markdown).')
                ->persistent()
                ->send();
        }

        return $page;
    }

    /**
     * La página se ha guardado desde otro sitio: lo escrito aquí va al
     * borrador de quien guardaba, para no perderlo (D4).
     *
     * @param  array<string, mixed>  $data
     */
    private function keepInDraft(ContentPage $page, array $data, ContentPageFormatEnum $format, ?string $content, ?Carbon $openedAt): void
    {
        $user = $this->currentUser();
        $owner = $this->getOwnerRecord();

        if ($user === null || ! $owner instanceof Content || $content === null || trim($content) === '') {
            return;
        }

        app(ContentPageDraftService::class)->save(
            $user,
            $owner,
            $page->exists ? $page : null,
            $format,
            $content,
            isset($data['title']) ? (string) $data['title'] : null,
            isset($data['slug']) ? (string) $data['slug'] : null,
            is_numeric($data['image_id'] ?? null) ? (int) $data['image_id'] : null,
            $openedAt,
        );
    }

    /**
     * No se guarda nada: se avisa y el modal sigue abierto.
     */
    private function refuse(Action $action, string $reason): never
    {
        Notification::make()
            ->danger()
            ->title('No se ha guardado la página')
            ->body($reason.' No se ha cambiado nada.')
            ->persistent()
            ->send();

        $action->halt();

        throw new RuntimeException('La acción debería haberse detenido.');
    }

    /**
     * Las columnas de la página: el resto del formulario es el estado del
     * editor, no columnas.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function pageAttributes(array $data): array
    {
        foreach ([
            'content_json', 'content_markdown', 'content_html', 'source_format', 'stored_format',
            'original_format', 'original_content', 'pending_change', 'backup_id', 'opened_at', 'confirm_format_change',
        ] as $key) {
            unset($data[$key]);
        }

        return $this->resolveImageUpload($data, 'image_id', 'content-pages', webpOriginal: true);
    }

    /**
     * Regla del campo del contenido: lo que no se podría guardar se dice en el
     * formulario, antes de intentarlo (bloques sin su dato principal, HTML
     * libre de quien no es administrador…).
     */
    private function contentRule(?ContentPage $record, ContentPageFormatEnum $format): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($record, $format): void {
            $problems = $this->formatService()->problems($record, $format, is_string($value) ? $value : null, $this->currentUser());

            if ($problems !== []) {
                $fail(implode(' ', $problems));
            }
        };
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
     * Versión de ESTA página: el id viene del formulario y no se da por bueno.
     */
    private function backupFor(?ContentPage $record, mixed $backupId): ?ContentPageVersion
    {
        if ($record === null || blank($backupId)) {
            return null;
        }

        return app(ContentPageHistoryService::class)->find($record, $backupId);
    }

    /**
     * HTML de la vista previa, saneado: aquí lo ve quien administra el panel.
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
