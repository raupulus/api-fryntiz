<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\SmartPlant\SmartPlantPlants;

use App\Filament\Admin\Clusters\SmartPlant;
use App\Filament\Admin\Resources\SmartPlant\SmartPlantPlants\Pages\CreateSmartPlantPlant;
use App\Filament\Admin\Resources\SmartPlant\SmartPlantPlants\Pages\EditSmartPlantPlant;
use App\Filament\Admin\Resources\SmartPlant\SmartPlantPlants\Pages\ListSmartPlantPlants;
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
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SmartPlantPlantResource extends Resource
{
    use ScopesToOwner;

    protected static ?string $model = SmartPlantPlant::class;

    protected static ?string $cluster = SmartPlant::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSun;

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'Planta';

    protected static ?string $pluralModelLabel = 'Plantas';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('user_id')
                    ->relationship('user', 'name'),
                TextInput::make('name')
                    ->required(),
                TextInput::make('name_scientific')
                    ->required(),
                // Admite HTML básico: la web lo pinta con `@safeHtml`, que deja
                // pasar sólo etiquetas de formato. Es un `Textarea` y no un
                // `TextInput` porque escribir marcado en una caja de una línea
                // no hay quien lo lea. La columna es `varchar(255)`, así que el
                // límite se declara aquí y no se descubre al guardar.
                Textarea::make('description')
                    ->required()
                    ->rows(3)
                    ->maxLength(255)
                    ->helperText('Admite HTML básico: <p>, <br>, <strong>, <em>, <ul>/<li>, <a href>. El resto se descarta al mostrarla.')
                    ->columnSpanFull(),
                // Igual que `description`: se pinta con `@safeHtml`, así que
                // admite el mismo HTML básico. Este campo es donde de verdad
                // se escribe con marcado (secciones «Origen», «Ecología»...
                // envueltas en `<p>`/`<strong>`/`<br>`).
                Textarea::make('details')
                    ->required()
                    ->rows(8)
                    ->helperText('Admite HTML básico: <p>, <br>, <strong>, <em>, <ul>/<li>, <a href>. El resto se descarta al mostrarla.')
                    ->columnSpanFull(),
                Textarea::make('image')
                    ->default('smartplant/default.jpg')
                    ->columnSpanFull(),
                DateTimePicker::make('start_at')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('image')
                    ->disk('public')
                    ->square()
                    ->defaultImageUrl(asset('storage/smartplant/default.jpg'))
                    ->label('Imagen'),
                TextColumn::make('name')->searchable()->sortable()->label('Nombre'),
                TextColumn::make('name_scientific')->toggleable()->label('Científico'),
                TextColumn::make('start_at')->date('d/m/Y')->sortable()->label('Sembrada'),
                TextColumn::make('registers_count')->counts('registers')->label('Lecturas'),
                TextColumn::make('latest_soil_humidity')
                    ->state(fn ($record) => $record->registers()->latest('created_at')->value('soil_humidity'))
                    ->suffix(' %')
                    ->color(fn ($state) => $state < 30 ? 'danger' : ($state < 60 ? 'warning' : 'success'))
                    ->label('Humedad suelo'),
                TextColumn::make('user.name')->toggleable()->label('Usuario'),
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
