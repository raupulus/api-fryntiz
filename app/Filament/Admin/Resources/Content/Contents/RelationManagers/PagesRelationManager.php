<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Content\Contents\RelationManagers;

use App\Filament\Components\CurrentImage;
use App\Filament\Components\EditorJsField;
use App\Filament\Components\ImageCropperUpload;
use App\Filament\Concerns\HasImageFileUpload;
use App\Models\Content\ContentAvailablePageRaw;
use Closure;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PagesRelationManager extends RelationManager
{
    use HasImageFileUpload;

    protected static string $relationship = 'pages';

    protected static ?string $title = 'Páginas';

    protected static ?string $recordTitleAttribute = 'title';

    /**
     * JSON de Editor.js pendiente de persistir en la relación raw().
     */
    public ?string $pendingJson = null;

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('title')->maxLength(255)->required()->label('Título'),
            TextInput::make('slug')->maxLength(255)->label('Slug'),
            TextInput::make('order')->numeric()->default(0)->label('Orden'),

            // Tres vías sobre el mismo contenido, no tres contenidos.
            //
            // La primera pestaña se llamaba «Editor Visual (JSON)», y entre eso
            // y el `helperText` daba la impresión de que el editor visual se
            // había sustituido por un pegado de JSON. El JSON es el **formato de
            // almacenamiento** (`content_available_page_raw.type = 'json'`), no
            // la interfaz: el editor visual es Editor.js y sigue siendo el sitio
            // donde se escribe.
            //
            // La pestaña de JSON en crudo está a propósito —pegar el JSON de
            // una página entera es cómodo cuando se sabe lo que se hace—, pero
            // va la segunda y avisando.
            Tabs::make('Editor')->columnSpanFull()->tabs([
                Tab::make('Editor visual')
                    ->icon('heroicon-o-pencil-square')
                    ->schema([
                        EditorJsField::make('content_json')
                            ->label('Contenido')
                            ->helperText('Editor.js. Se guarda en JSON, que es lo que consumen los clientes de la API.')
                            ->columnSpanFull(),
                    ]),

                Tab::make('JSON en crudo')
                    ->icon('heroicon-o-code-bracket')
                    ->schema([
                        // Mismo estado que el editor visual: lo que se pegue
                        // aquí se ve allí al cambiar de pestaña, y al revés.
                        Textarea::make('content_json')
                            ->label('JSON de Editor.js')
                            ->helperText('Para pegar el contenido de otra página o corregir a mano. Tiene que ser un objeto con una clave «blocks»; si no lo es, el editor visual lo ignora y se queda vacío.')
                            ->rows(18)
                            ->autosize()
                            ->rules([
                                fn (): Closure => function (string $attribute, $value, Closure $fail): void {
                                    if (blank($value)) {
                                        return;
                                    }

                                    $decodificado = json_decode((string) $value, true);

                                    if (! is_array($decodificado) || ! isset($decodificado['blocks'])) {
                                        $fail('Esto no es un JSON de Editor.js: falta la clave «blocks».');
                                    }
                                },
                            ])
                            ->columnSpanFull(),
                    ]),

                Tab::make('HTML')
                    ->icon('heroicon-o-document-text')
                    ->schema([
                        RichEditor::make('content')
                            ->columnSpanFull()
                            ->fileAttachmentsDirectory('content-pages')
                            ->label('Contenido HTML'),
                    ]),
            ]),

            // La imagen que ya tiene guardada. El uploader de abajo no puede
            // enseñarla: apunta a `image_id`, una clave foránea, y espera una
            // ruta de disco (ver `CurrentImage`).
            CurrentImage::deLaRelacion(),

            // Pedía `image_id`... no: pedía `image_path`, una columna que no
            // existe en ninguna tabla del proyecto, así que la imagen se perdía
            // al guardar sin dar ningún error (N232). La columna real es
            // `image_id`, con su clave foránea a `files`.
            ImageCropperUpload::makeImage('image_id')
                ->storeFiles(false)
                ->dehydrated(fn ($state) => filled($state))
                ->cover16x9()
                ->directory('content-pages')
                ->columnSpanFull()->label('Imagen de la página'),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('order')->sortable()->label('Orden'),
                TextColumn::make('title')->label('Título'),
                TextColumn::make('slug')->label('Slug')->toggleable(),
            ])
            ->reorderable('order')
            ->defaultSort('order')
            ->headerActions([
                CreateAction::make()
                    ->label('Añadir Página')
                    ->mutateFormDataUsing(function (array $data) {
                        $this->pendingJson = $data['content_json'] ?? null;
                        unset($data['content_json']);

                        return $this->resolveImageUpload($data, 'image_id', 'content-pages');
                    })
                    ->after(fn ($record) => $this->persistRaw($record)),
            ])
            ->recordActions([
                EditAction::make()
                    ->mutateRecordDataUsing(function (array $data, $record) {
                        // Solo el raw JSON: la relación raw() devuelve el más
                        // reciente sin filtrar por tipo y podría ser markdown.
                        $data['content_json'] = $record->raw()
                            ->where('available_page_raw_id', $this->jsonTypeId())
                            ->value('content');

                        // El campo de imagen espera un fichero, no la clave
                        // foránea: si se rellena con el id, el componente
                        // intenta pintar el número como si fuera una ruta.
                        unset($data['image_id']);

                        return $data;
                    })
                    ->mutateFormDataUsing(function (array $data) {
                        $this->pendingJson = $data['content_json'] ?? null;
                        unset($data['content_json']);

                        return $this->resolveImageUpload($data, 'image_id', 'content-pages');
                    })
                    ->after(fn ($record) => $this->persistRaw($record)),
                DeleteAction::make(),
            ]);
    }

    /**
     * Persiste el JSON de Editor.js en la relación raw() de la página.
     */
    public function persistRaw($record): void
    {
        if (blank($this->pendingJson)) {
            return;
        }

        $typeId = $this->jsonTypeId();

        if ($typeId === null) {
            Notification::make()
                ->danger()
                ->title('No se pudo guardar el contenido del editor')
                ->body('Falta el tipo "json" en content_available_page_raw. Ejecuta el seeder ContentAvailablePageRawSeeder.')
                ->persistent()
                ->send();

            return;
        }

        $record->raw()->updateOrCreate(
            ['available_page_raw_id' => $typeId],
            ['content' => $this->pendingJson],
        );

        $this->pendingJson = null;
    }

    /**
     * Id del tipo de raw "json" (contenido de Editor.js).
     */
    protected function jsonTypeId(): ?int
    {
        return ContentAvailablePageRaw::query()->where('type', 'json')->value('id');
    }
}
