<?php

namespace App\Filament\Admin\Resources\Content\Contents\RelationManagers;

use App\Filament\Components\EditorJsField;
use App\Filament\Components\ImageCropperUpload;
use App\Models\Content\ContentAvailablePageRaw;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PagesRelationManager extends RelationManager
{
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

            Tabs::make('Editor')->columnSpanFull()->tabs([
                Tab::make('Editor Visual (JSON)')
                    ->icon('heroicon-o-code-bracket')
                    ->schema([
                        EditorJsField::make('content_json')
                            ->label('Contenido (Editor.js)')
                            ->helperText('Este editor guarda en formato JSON para clientes API.')
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

            ImageCropperUpload::makeImage('image_path')
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
                    ->mutateFormDataUsing(function (array $data) {
                        $this->pendingJson = $data['content_json'] ?? null;
                        unset($data['content_json']);

                        return $data;
                    })
                    ->after(fn ($record) => $this->persistRaw($record)),
            ])
            ->recordActions([
                EditAction::make()
                    ->mutateRecordDataUsing(function (array $data, $record) {
                        $data['content_json'] = $record->raw?->content;

                        return $data;
                    })
                    ->mutateFormDataUsing(function (array $data) {
                        $this->pendingJson = $data['content_json'] ?? null;
                        unset($data['content_json']);

                        return $data;
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

        $typeId = ContentAvailablePageRaw::query()->where('type', 'json')->value('id');

        $record->raw()->updateOrCreate(
            ['available_page_raw_id' => $typeId],
            ['content' => $this->pendingJson],
        );

        $this->pendingJson = null;
    }
}
