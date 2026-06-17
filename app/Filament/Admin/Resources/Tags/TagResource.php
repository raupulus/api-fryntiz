<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Tags;

use App\Filament\Admin\Resources\Tags\Pages\CreateTag;
use App\Filament\Admin\Resources\Tags\Pages\EditTag;
use App\Filament\Admin\Resources\Tags\Pages\ListTags;
use App\Models\Tag;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ColorColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class TagResource extends Resource
{
    protected static ?string $model = Tag::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTag;

    protected static string|\UnitEnum|null $navigationGroup = 'Gestión';

    protected static ?int $navigationSort = 40;

    protected static ?string $modelLabel = 'Etiqueta';

    protected static ?string $pluralModelLabel = 'Etiquetas';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Etiqueta')->columns(2)->schema([
                TextInput::make('name')->required()->maxLength(255)
                    ->unique(ignoreRecord: true)
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn ($state, $set, string $operation) => $operation === 'create' ? $set('slug', Str::slug((string) $state)) : null
                    )->label('Nombre'),
                TextInput::make('slug')->required()->maxLength(255)
                    ->unique(ignoreRecord: true)->rule('alpha_dash')->label('Slug'),
                TextInput::make('icon')->maxLength(255)->label('Icono CSS'),
                ColorPicker::make('color')->default('#000000')->label('Color'),
                Textarea::make('description')->maxLength(511)->rows(2)
                    ->columnSpanFull()->label('Descripción'),
            ])->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable()->label('Nombre'),
                TextColumn::make('slug')->searchable()->toggleable()->label('Slug'),
                ColorColumn::make('color')->label('Color'),
                TextColumn::make('icon')->toggleable()->label('Icono'),
                TextColumn::make('created_at')->label('Creado el')->dateTime('d/m/Y')->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()])
            ->toolbarActions([
                BulkActionGroup::make([DeleteBulkAction::make()]),
            ])
            ->defaultSort('name');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTags::route('/'),
            'create' => CreateTag::route('/create'),
            'edit' => EditTag::route('/{record}/edit'),
        ];
    }
}
