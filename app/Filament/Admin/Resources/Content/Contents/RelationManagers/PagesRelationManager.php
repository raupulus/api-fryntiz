<?php

namespace App\Filament\Admin\Resources\Content\Contents\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PagesRelationManager extends RelationManager
{
    protected static string $relationship = 'pages';

    protected static ?string $title = 'Páginas';

    protected static ?string $recordTitleAttribute = 'title';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('title')->maxLength(255)->required()->label('Título'),
            TextInput::make('slug')->maxLength(255)->label('Slug'),
            TextInput::make('order')->numeric()->default(0)->label('Orden'),
            RichEditor::make('content')->columnSpanFull()
                ->fileAttachmentsDirectory('content-pages')->label('Contenido HTML'),
            FileUpload::make('image_path')
                ->image()->imageEditor()->imageEditorAspectRatios(['16:9', '4:3'])
                ->directory('content-pages')->visibility('public')
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
            ->headerActions([CreateAction::make()])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }
}
