<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Galleries;

use App\Filament\Admin\Resources\Galleries\Pages\CreateGallery;
use App\Filament\Admin\Resources\Galleries\Pages\EditGallery;
use App\Filament\Admin\Resources\Galleries\Pages\ListGalleries;
use App\Filament\Components\ImageCropperUpload;
use App\Filament\Concerns\ScopesToOwner;
use App\Models\Gallery;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class GalleryResource extends Resource
{
    use ScopesToOwner;

    protected static ?string $model = Gallery::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPhoto;

    protected static string|\UnitEnum|null $navigationGroup = 'Gestión';

    protected static ?int $navigationSort = 35;

    protected static ?string $modelLabel = 'Galería';

    protected static ?string $pluralModelLabel = 'Galerías';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('user_id')
                ->relationship('user', 'name')
                ->searchable()->preload()
                ->default(fn () => auth()->id())
                ->label('Usuario'),
            ImageCropperUpload::makeImage('image_id')
                ->cover16x9()
                ->storeFiles(false)
                ->dehydrated(fn ($state) => filled($state))
                ->label('Imagen de portada'),
            TextInput::make('name')->required()->maxLength(511)->label('Nombre'),
            Textarea::make('description')->maxLength(1024)->rows(2)
                ->columnSpanFull()->label('Descripción'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['image.fileType', 'user']))
            ->columns([
                ImageColumn::make('image_id')
                    ->getStateUsing(fn (Gallery $record): ?string => $record->image?->thumbnail('small'))
                    ->square()
                    ->label('Portada'),
                TextColumn::make('name')->searchable()->sortable()->label('Nombre'),
                TextColumn::make('images_count')->counts('images')->label('Imágenes'),
                TextColumn::make('contents_count')->counts('contents')->label('Contenidos'),
                TextColumn::make('user.name')->label('Usuario')->searchable(),
                TextColumn::make('created_at')->dateTime('d/m/Y')->label('Creada')->sortable(),
            ])
            ->recordActions([EditAction::make()])
            ->toolbarActions([
                BulkActionGroup::make([DeleteBulkAction::make()]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\ImagesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListGalleries::route('/'),
            'create' => CreateGallery::route('/create'),
            'edit' => EditGallery::route('/{record}/edit'),
        ];
    }
}
