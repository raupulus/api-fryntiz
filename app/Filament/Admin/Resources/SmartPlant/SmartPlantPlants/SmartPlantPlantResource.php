<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\SmartPlant\SmartPlantPlants;

use App\Filament\Admin\Clusters\SmartPlant;
use App\Filament\Admin\Resources\SmartPlant\SmartPlantPlants\Pages\CreateSmartPlantPlant;
use App\Filament\Admin\Resources\SmartPlant\SmartPlantPlants\Pages\EditSmartPlantPlant;
use App\Filament\Admin\Resources\SmartPlant\SmartPlantPlants\Pages\ListSmartPlantPlants;
use App\Filament\Components\ImageCropperUpload;
use App\Filament\Concerns\ScopesToOwner;
use App\Models\SmartPlant\SmartPlantPlant;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SmartPlantPlantResource extends Resource
{
    use ScopesToOwner;

    protected static ?string $model = SmartPlantPlant::class;

    protected static ?string $cluster = SmartPlant::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSun;

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'Planta';

    protected static ?string $pluralModelLabel = 'Plantas';

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $slug = 'plants';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Identificación')
                    ->columns(2)
                    ->schema([
                        Select::make('user_id')
                            ->label('Propietario')
                            ->relationship('user', 'name')
                            ->default(fn (): ?int => auth()->id())
                            ->searchable()
                            ->preload()
                            ->required(),
                        DateTimePicker::make('start_at')
                            ->label('Fecha de siembra')
                            ->required(),
                        TextInput::make('name')
                            ->label('Nombre')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('slug')
                            ->label('Slug URL')
                            ->unique(ignoreRecord: true)
                            ->maxLength(160)
                            ->helperText('Opcional: si se deja vacío se genera automáticamente desde el nombre.'),
                        TextInput::make('name_scientific')
                            ->label('Nombre científico')
                            ->required()
                            ->maxLength(255),
                    ])->columnSpanFull(),

                Section::make('Imagen y descripción')
                    ->schema([
                        ImageCropperUpload::makeImage('image')
                            ->label('Imagen de la planta')
                            ->directory('smartplant'),
                        Textarea::make('description')
                            ->label('Descripción corta')
                            ->required()
                            ->rows(3)
                            ->maxLength(255)
                            ->helperText('Admite HTML básico: <p>, <br>, <strong>, <em>, <ul>/<li>, <a href>. El resto se descarta al mostrarla.')
                            ->columnSpanFull(),
                        Textarea::make('details')
                            ->label('Detalles y cuidados')
                            ->required()
                            ->rows(8)
                            ->helperText('Admite HTML básico: <p>, <br>, <strong>, <em>, <ul>/<li>, <a href>. El resto se descarta al mostrarla.')
                            ->columnSpanFull(),
                    ])->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('latestRegister'))
            ->columns([
                ImageColumn::make('image')
                    ->disk('public')
                    ->square()
                    ->defaultImageUrl(asset('storage/smartplant/default.webp'))
                    ->label('Imagen'),
                TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->limit(40)
                    ->tooltip(fn (?string $state): ?string => $state)
                    ->label('Nombre'),
                TextColumn::make('name_scientific')
                    ->limit(35)
                    ->tooltip(fn (?string $state): ?string => $state)
                    ->toggleable()
                    ->label('Científico'),
                TextColumn::make('start_at')->date('d/m/Y')->sortable()->label('Sembrada'),
                TextColumn::make('registers_count')->counts('registers')->label('Lecturas'),
                TextColumn::make('latest_soil_humidity')
                    ->state(fn ($record) => $record->latestRegister?->soil_humidity)
                    ->suffix(' %')
                    ->color(fn ($state) => $state < 30 ? 'danger' : ($state < 60 ? 'warning' : 'success'))
                    ->label('Humedad suelo'),
                TextColumn::make('user.name')->toggleable(isToggledHiddenByDefault: true)->label('Usuario'),
                TextColumn::make('created_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true)->label('Creado en'),
                TextColumn::make('updated_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true)->label('Actualizado en'),
            ])
            ->filters([])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
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
            RelationManagers\RegistersRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSmartPlantPlants::route('/'),
            'create' => CreateSmartPlantPlant::route('/create'),
            'edit' => EditSmartPlantPlant::route('/{record}/edit'),
        ];
    }
}
