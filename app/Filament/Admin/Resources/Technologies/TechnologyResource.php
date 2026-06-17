<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Technologies;

use App\Filament\Admin\Resources\Technologies\Pages\CreateTechnology;
use App\Filament\Admin\Resources\Technologies\Pages\EditTechnology;
use App\Filament\Admin\Resources\Technologies\Pages\ListTechnologies;
use App\Filament\Components\ImageCropperUpload;
use App\Models\Technology;
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

class TechnologyResource extends Resource
{
    protected static ?string $model = Technology::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCpuChip;

    protected static string|\UnitEnum|null $navigationGroup = 'Gestión';

    protected static ?int $navigationSort = 20;

    protected static ?string $modelLabel = 'Tecnología';

    protected static ?string $pluralModelLabel = 'Tecnologías';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Logo')->schema([
                ImageCropperUpload::makeImage('image_path')
                    ->icon(128)
                    ->directory('technologies')
                    ->hiddenLabel()
                    ->extraAttributes(['class' => 'flex justify-center mx-auto'])
                    ->columnSpanFull(),
            ])->columnSpanFull(),

            Section::make('Tecnología')->columns(2)->schema([
                TextInput::make('name')->required()->maxLength(255)
                    ->unique(ignoreRecord: true)
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn ($state, $set, string $operation) => $operation === 'create' ? $set('slug', Str::slug((string) $state)) : null
                    )->label('Nombre'),
                TextInput::make('slug')->required()->maxLength(255)
                    ->unique(ignoreRecord: true)->rule('alpha_dash')->label('Slug'),
                ColorPicker::make('color')->required()->default('#000000')->label('Color'),
                Textarea::make('description')->rows(3)->columnSpanFull()->label('Descripción'),
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
            'index' => ListTechnologies::route('/'),
            'create' => CreateTechnology::route('/create'),
            'edit' => EditTechnology::route('/{record}/edit'),
        ];
    }
}
