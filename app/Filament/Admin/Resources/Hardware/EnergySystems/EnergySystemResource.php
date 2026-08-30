<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Hardware\EnergySystems;

use App\Filament\Admin\Clusters\Energy;
use App\Filament\Admin\Resources\Hardware\EnergySystems\Pages\CreateEnergySystem;
use App\Filament\Admin\Resources\Hardware\EnergySystems\Pages\EditEnergySystem;
use App\Filament\Admin\Resources\Hardware\EnergySystems\Pages\ListEnergySystems;
use App\Models\Hardware\EnergySystem;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;

/**
 * La instalación energética (D79).
 *
 * Agrupa elementos que comparten batería y tensión, que es lo que permite
 * preguntar «cuánto ha generado la casa hoy» sin ir listando ids a mano.
 */
class EnergySystemResource extends Resource
{
    protected static ?string $model = EnergySystem::class;

    protected static ?string $cluster = Energy::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedHome;

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'Instalación';

    protected static ?string $pluralModelLabel = 'Instalaciones';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()->maxLength(255)
                    ->label('Nombre')
                    ->placeholder('Casa 24V, Nodo UV, Banco de routers…')
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (?string $state, callable $set, string $operation): void {
                        // El slug es lo que usan la API y la web
                        // (`?system=casa`), así que se genera solo al crear y no
                        // se toca al editar: cambiarlo rompería enlaces.
                        if ($operation === 'create' && $state !== null) {
                            $set('slug', Str::slug($state));
                        }
                    }),
                TextInput::make('slug')
                    ->required()->maxLength(255)->unique(ignoreRecord: true)
                    ->label('Slug')
                    ->helperText('Es lo que se usa para filtrar desde la API y la web. No lo cambies una vez publicado.'),
                Select::make('user_id')
                    ->relationship('user', 'name')
                    ->required()->searchable()->preload()
                    ->label('Propietario'),
                Toggle::make('is_standalone')
                    ->label('Autoabastecido')
                    ->helperText('Nodo con placa pequeña y batería, sin red.'),
                TextInput::make('nominal_voltage')
                    ->numeric()->step(0.01)->suffix(' V')
                    ->label('Tensión nominal'),
                TextInput::make('battery_capacity_ah')
                    ->numeric()->step(0.01)->suffix(' Ah')
                    ->label('Capacidad del banco de baterías'),
                Textarea::make('notes')
                    ->rows(3)->columnSpanFull()
                    ->label('Notas'),
            ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Instalación')->searchable()->sortable(),
                TextColumn::make('slug')->label('Slug')->badge()->copyable(),
                TextColumn::make('elements_count')
                    ->counts('elements')
                    ->label('Elementos')
                    ->numeric(),
                TextColumn::make('nominal_voltage')->label('V nominal')->suffix(' V')->placeholder('—'),
                TextColumn::make('battery_capacity_ah')->label('Batería')->suffix(' Ah')->placeholder('—'),
                IconColumn::make('is_standalone')->label('Autoabastecido')->boolean(),
                TextColumn::make('user.name')->label('Propietario')->toggleable(),
                TextColumn::make('created_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
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

    public static function getPages(): array
    {
        return [
            'index' => ListEnergySystems::route('/'),
            'create' => CreateEnergySystem::route('/create'),
            'edit' => EditEnergySystem::route('/{record}/edit'),
        ];
    }
}
