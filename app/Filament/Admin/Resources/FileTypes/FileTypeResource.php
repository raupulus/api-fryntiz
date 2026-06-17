<?php

namespace App\Filament\Admin\Resources\FileTypes;

use App\Filament\Admin\Resources\FileTypes\Pages\CreateFileType;
use App\Filament\Admin\Resources\FileTypes\Pages\EditFileType;
use App\Filament\Admin\Resources\FileTypes\Pages\ListFileTypes;
use App\Filament\Components\ImageCropperUpload;
use App\Models\FileType;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;

class FileTypeResource extends Resource
{
    protected static ?string $model = FileType::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocument;

    protected static string|\UnitEnum|null $navigationGroup = 'Configuración';

    protected static ?int $navigationSort = 10;

    protected static ?string $modelLabel = 'Tipo de archivo';

    protected static ?string $pluralModelLabel = 'Tipos de archivos';

    protected static ?string $recordTitleAttribute = 'extension';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Identificación')->columns(3)->schema([
                    TextInput::make('type')->required()->maxLength(127)
                        ->datalist(['imagen', 'documento', 'vídeo', 'audio', 'comprimido', 'código', 'otro'])
                        ->label('Tipo'),
                    TextInput::make('mime')->required()->maxLength(127)
                        ->rule('regex:/^[\w\-.+]+\/[\w\-.+]+$/')
                        ->unique(ignoreRecord: true)
                        ->helperText('Ej: image/png, application/pdf')
                        ->label('MIME'),
                    TextInput::make('extension')->required()->maxLength(12)
                        ->rule('regex:/^[a-z0-9]+$/')
                        ->unique(ignoreRecord: true)
                        ->prefix('.')->label('Extensión'),
                    Select::make('user_id')
                        ->relationship('user', 'name')->default(fn () => auth()->id())
                        ->searchable()->preload()->label('Owner'),
                ]),

                Section::make('Iconos')
                    ->description('Cada icono debe ser cuadrado (1:1). Se recortarán automáticamente.')
                    ->columns(4)->schema([
                        ImageCropperUpload::makeImage('icon16')
                            ->icon(16)
                            ->directory('file-types/icons/16')->maxSize(256)->label('16×16'),
                        ImageCropperUpload::makeImage('icon32')
                            ->icon(32)
                            ->directory('file-types/icons/32')->maxSize(256)->label('32×32'),
                        ImageCropperUpload::makeImage('icon64')
                            ->icon(64)
                            ->directory('file-types/icons/64')->label('64×64'),
                        ImageCropperUpload::makeImage('icon128')
                            ->icon(128)
                            ->directory('file-types/icons/128')->maxSize(1024)->label('128×128'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('icon32')->square()->label('32px'),
                TextColumn::make('type')->searchable()->sortable()->badge()->label('Tipo'),
                TextColumn::make('extension')->searchable()->sortable()
                    ->prefix('.')->label('Ext'),
                TextColumn::make('mime')->searchable()->copyable()->label('MIME'),
                TextColumn::make('user.name')->toggleable()->label('Owner'),
                TextColumn::make('created_at')->dateTime('d/m/Y')->toggleable(),
            ])
            ->filters([
                SelectFilter::make('type')->options(fn () => FileType::query()->distinct()->pluck('type', 'type')->all()
                )->label('Tipo'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ])
            ->defaultSort('type')
            ->groups([
                Group::make('type')->label('Tipo')->collapsible(),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListFileTypes::route('/'),
            'create' => CreateFileType::route('/create'),
            'edit' => EditFileType::route('/{record}/edit'),
        ];
    }
}
