<?php

namespace App\Filament\Admin\Resources\CV\CurriculumAvailableRepositoryTypes;

use App\Filament\Admin\Resources\CV\CurriculumAvailableRepositoryTypes\Pages\CreateCurriculumAvailableRepositoryType;
use App\Filament\Admin\Resources\CV\CurriculumAvailableRepositoryTypes\Pages\EditCurriculumAvailableRepositoryType;
use App\Filament\Admin\Resources\CV\CurriculumAvailableRepositoryTypes\Pages\ListCurriculumAvailableRepositoryTypes;
use App\Filament\Components\ImageCropperUpload;
use App\Models\CV\CurriculumAvailableRepositoryType;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CurriculumAvailableRepositoryTypeResource extends Resource
{
    protected static ?string $model = CurriculumAvailableRepositoryType::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|\UnitEnum|null $navigationGroup = 'Módulos';

    protected static ?int $navigationSort = 21;

    protected static ?string $modelLabel = 'Tipo de repositorio';

    protected static ?string $pluralModelLabel = 'Tipos de repositorios';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Logo')->schema([
                    ImageCropperUpload::makeImage('image_id')
                        ->icon(128)
                        ->storeFiles(false)
                        ->dehydrated(fn ($state) => filled($state))
                        ->hiddenLabel()
                        ->extraAttributes(['class' => 'flex justify-center mx-auto'])
                        ->columnSpanFull(),
                ])->columnSpanFull(),

                Section::make('Detalles')->schema([
                    TextInput::make('name')
                        ->label('Nombre')
                        ->columnSpanFull(),
                    TextInput::make('title')
                        ->label('Título')
                        ->columnSpanFull(),
                    TextInput::make('slug')
                        ->label('Slug')
                        ->required()
                        ->columnSpanFull(),
                    TextInput::make('url')
                        ->label('URL')
                        ->url()
                        ->columnSpanFull(),
                ])->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('image_id')->label('Imagen')->sortable()->toggleable(),
                TextColumn::make('title')->label('Título')->searchable(),
                TextColumn::make('name')->label('Nombre')->searchable(),
                TextColumn::make('created_at')
                    ->label('Creado el')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label('Actualizado el')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCurriculumAvailableRepositoryTypes::route('/'),
            'create' => CreateCurriculumAvailableRepositoryType::route('/create'),
            'edit' => EditCurriculumAvailableRepositoryType::route('/{record}/edit'),
        ];
    }
}
