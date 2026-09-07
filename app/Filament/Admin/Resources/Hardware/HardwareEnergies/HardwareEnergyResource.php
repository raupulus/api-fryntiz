<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Hardware\HardwareEnergies;

use App\Filament\Admin\Clusters\Energy;
use App\Filament\Admin\Resources\Hardware\HardwareEnergies\Pages\CreateHardwareEnergy;
use App\Filament\Admin\Resources\Hardware\HardwareEnergies\Pages\EditHardwareEnergy;
use App\Filament\Admin\Resources\Hardware\HardwareEnergies\Pages\ListHardwareEnergies;
use App\Filament\Concerns\ScopesToOwner;
use App\Models\Hardware\HardwareEnergy;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * El **elemento energético**: un panel, un router, una batería (D81).
 *
 * Esta pantalla es donde se le pone a cada elemento su instalación, su papel y
 * —lo más importante— su **tensión nominal**: es lo que hace que los vatios
 * salgan bien. Sin ella se multiplica la corriente del canal por el único
 * voltaje que traiga la petición, y un panel de 24 V y una Pico de 3,7 V en la
 * misma petición dan números sin sentido.
 */
class HardwareEnergyResource extends Resource
{
    use ScopesToOwner;

    /**
     * `hardware_energy` no tiene `user_id`: el dueño es el de la instalación
     * de la que cuelga el módulo, igual que hace {@see HardwareEnergy::scopeForUser()}.
     *
     * @param  Builder<covariant \Illuminate\Database\Eloquent\Model>  $query
     * @return Builder<covariant \Illuminate\Database\Eloquent\Model>
     */
    protected static function scopeOwnerQuery(Builder $query, int $userId): Builder
    {
        return $query->whereHas('system', static fn (Builder $q) => $q->where('user_id', $userId));
    }

    protected static ?string $model = HardwareEnergy::class;

    protected static ?string $cluster = Energy::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBattery100;

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'Elemento energético';

    protected static ?string $pluralModelLabel = 'Elementos de energía';

    public static function form(Schema $schema): Schema
    {
        return HardwareEnergyForm::completo($schema);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                // `name` era un campo que había que rellenar a mano para
                // escribir lo que ya se sabe. `display_name` lo compone.
                TextColumn::make('display_name')
                    ->label('Elemento')
                    // La clave foránea es nullable, así que la relación puede
                    // venir vacía por mucho que PHPStan crea que no.
                    ->description(fn (HardwareEnergy $record): string => $record->hardware_device_id === null
                        ? ''
                        : (string) $record->getRelationValue('hardwareDevice')?->display_name)
                    ->searchable(['sensor_position'])
                    ->sortable(false),
                TextColumn::make('system.name')
                    ->label('Instalación')
                    ->badge()
                    ->sortable(),
                TextColumn::make('role')
                    ->label('Papel')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => HardwareEnergy::ETIQUETAS_DE_ROL[$state] ?? (string) $state)
                    ->color(fn (?string $state): string => match ($state) {
                        HardwareEnergy::ROLE_GENERATOR => 'success',
                        HardwareEnergy::ROLE_BATTERY => 'warning',
                        default => 'info',
                    }),
                TextColumn::make('sourceType.name')
                    ->label('Fuente')
                    ->toggleable(),
                TextColumn::make('hardwareDevice.name')
                    ->label('Monitor')
                    ->toggleable()
                    ->sortable(),
                TextColumn::make('sensor_position')
                    ->label('Canal')
                    ->numeric()
                    ->sortable(),
                // Sin tensión nominal los vatios de este elemento dependen del
                // voltaje que traiga la petición, que puede no ser el suyo.
                TextColumn::make('nominal_voltage')
                    ->label('V nominal')
                    ->suffix(' V')
                    ->placeholder('sin definir')
                    ->color(fn ($state): string => $state === null ? 'danger' : 'gray')
                    ->sortable(),
                IconColumn::make('is_active')
                    ->label('Activo')
                    ->boolean(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('energy_system_id')
                    ->relationship('system', 'name')
                    ->label('Instalación'),
                SelectFilter::make('role')
                    ->options(HardwareEnergy::ETIQUETAS_DE_ROL)
                    ->label('Papel'),
                TernaryFilter::make('is_active')->label('Activo'),
                TernaryFilter::make('nominal_voltage')
                    ->label('Tensión nominal')
                    ->placeholder('Todos')
                    ->trueLabel('Con tensión nominal')
                    ->falseLabel('SIN tensión nominal')
                    ->queries(
                        true: fn ($query) => $query->whereNotNull('nominal_voltage'),
                        false: fn ($query) => $query->whereNull('nominal_voltage'),
                        blank: fn ($query) => $query,
                    ),
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
            RelationManagers\PowerLoadsRelationManager::class,
            RelationManagers\PowerGeneratorsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListHardwareEnergies::route('/'),
            'create' => CreateHardwareEnergy::route('/create'),
            'edit' => EditHardwareEnergy::route('/{record}/edit'),
        ];
    }
}
