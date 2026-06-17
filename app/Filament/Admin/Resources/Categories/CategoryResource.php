<?php

namespace App\Filament\Admin\Resources\Categories;

use App\Filament\Admin\Resources\Categories\Pages\CreateCategory;
use App\Filament\Admin\Resources\Categories\Pages\EditCategory;
use App\Filament\Admin\Resources\Categories\Pages\ListCategories;
use App\Filament\Components\ImageCropperUpload;
use App\Models\Category;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Select;
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

class CategoryResource extends Resource
{
    protected static ?string $model = Category::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFolder;

    protected static string|\UnitEnum|null $navigationGroup = 'Gestión';

    protected static ?int $navigationSort = 30;

    protected static ?string $modelLabel = 'Categoría';

    protected static ?string $pluralModelLabel = 'Categorías';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Logo')->schema([
                ImageCropperUpload::makeImage('image_path')
                    ->logo()
                    ->directory('categories')
                    ->hiddenLabel()
                    ->extraAttributes(['class' => 'flex justify-center mx-auto'])
                    ->columnSpanFull(),
            ])->columnSpanFull(),

            Section::make('Categoría')->columns(2)->schema([
                TextInput::make('name')->required()->maxLength(255)
                    ->unique(ignoreRecord: true)
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn ($state, $set, string $operation) => $operation === 'create' ? $set('slug', Str::slug((string) $state)) : null
                    )->label('Nombre'),
                TextInput::make('slug')->required()->maxLength(255)
                    ->unique(ignoreRecord: true)->rule('alpha_dash')->label('Slug'),
                Select::make('parent_id')
                    ->relationship('parentCategory', 'name')
                    ->searchable()->preload()->nullable()->label('Categoría padre'),
                TextInput::make('priority')->numeric()->minValue(0)->label('Prioridad'),
                TextInput::make('icon')->maxLength(255)
                    ->helperText('Clase CSS (ej. fa-folder).')->label('Icono'),
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
                TextColumn::make('parentCategory.name')->label('Padre')->toggleable(),
                ColorColumn::make('color')->label('Color'),
                TextColumn::make('priority')->label('Prioridad')->sortable()->toggleable(),
                TextColumn::make('slug')->label('Slug')->toggleable(),
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
            'index' => ListCategories::route('/'),
            'create' => CreateCategory::route('/create'),
            'edit' => EditCategory::route('/{record}/edit'),
        ];
    }
}
