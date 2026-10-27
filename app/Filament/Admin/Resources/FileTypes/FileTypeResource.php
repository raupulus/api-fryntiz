<?php

declare(strict_types=1);

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
            ->columns(1)
            ->components([
                Section::make('Identificación')
                    ->columnSpanFull()
                    ->columns(['sm' => 2, 'lg' => 4])
                    ->schema([
                        TextInput::make('type')
                            ->required()
                            ->maxLength(127)
                            ->datalist(['imagen', 'documento', 'vídeo', 'audio', 'comprimido', 'código', 'otro'])
                            ->placeholder('Ej: imagen, documento...')
                            ->label('Tipo'),
                        TextInput::make('extension')
                            ->required()
                            ->maxLength(12)
                            ->rule('regex:/^[a-z0-9]+$/')
                            ->unique(ignoreRecord: true)
                            ->prefix('.')
                            ->placeholder('fig, png, pdf...')
                            ->label('Extensión'),
                        TextInput::make('mime')
                            ->required()
                            ->maxLength(127)
                            ->rule('regex:/^[\w\-.+]+\/[\w\-.+]+$/')
                            ->unique(ignoreRecord: true)
                            ->placeholder('application/x-figma')
                            ->helperText('Formato tipo/subtipo (ej: image/png)')
                            ->label('MIME'),
                        Select::make('user_id')
                            ->relationship('user', 'name')
                            ->default(fn () => auth()->id())
                            ->searchable()
                            ->preload()
                            ->label('Propietario'),
                    ]),

                Section::make('Iconos')
                    ->description('Cada icono debe ser cuadrado (1:1). Se optimizan y recortan automáticamente.')
                    ->columnSpanFull()
                    ->columns(['sm' => 2, 'lg' => 4])
                    ->schema([
                        ImageCropperUpload::makeImage('icon16')
                            ->icon(16)
                            ->itemPanelAspectRatio('1:1')
                            ->panelAspectRatio('1:1')
                            ->imagePreviewHeight('150')
                            ->directory('file-types/icons/16')
                            ->maxSize(256)
                            ->label('16×16')
                            ->helperText('16×16 px · Minúsculo / Favicon')
                            ->alignCenter()
                            ->extraAttributes(['class' => 'ft-icon-field']),
                        ImageCropperUpload::makeImage('icon32')
                            ->icon(32)
                            ->itemPanelAspectRatio('1:1')
                            ->panelAspectRatio('1:1')
                            ->imagePreviewHeight('150')
                            ->directory('file-types/icons/32')
                            ->maxSize(256)
                            ->label('32×32')
                            ->helperText('32×32 px · Tablas y listados')
                            ->alignCenter()
                            ->extraAttributes(['class' => 'ft-icon-field']),
                        ImageCropperUpload::makeImage('icon64')
                            ->icon(64)
                            ->itemPanelAspectRatio('1:1')
                            ->panelAspectRatio('1:1')
                            ->imagePreviewHeight('150')
                            ->directory('file-types/icons/64')
                            ->maxSize(512)
                            ->label('64×64')
                            ->helperText('64×64 px · Tamaño medio')
                            ->alignCenter()
                            ->extraAttributes(['class' => 'ft-icon-field']),
                        ImageCropperUpload::makeImage('icon128')
                            ->icon(128)
                            ->itemPanelAspectRatio('1:1')
                            ->panelAspectRatio('1:1')
                            ->imagePreviewHeight('150')
                            ->directory('file-types/icons/128')
                            ->maxSize(1024)
                            ->label('128×128')
                            ->helperText('128×128 px · Alta resolución')
                            ->alignCenter()
                            ->extraAttributes(['class' => 'ft-icon-field']),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('icon32')
                    ->disk('public')
                    ->defaultImageUrl(fn (FileType $record): string => $record->url_image)
                    ->square()
                    ->size(32)
                    ->label('32px'),
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
