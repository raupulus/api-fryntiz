<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Energy\EnergyDevices;

use App\Filament\Admin\Clusters\Energy;
use App\Filament\Admin\Resources\Energy\EnergyDevices\Pages\ListEnergyDevices;
use App\Filament\Admin\Resources\Energy\EnergyDevices\Pages\ManageEnergyDevice;
use App\Filament\Concerns\ScopesToOwner;
use App\Models\Hardware\HardwareDevice;
use App\Models\Hardware\HardwareEnergy;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Los **aparatos** que miden energía, como puerta de entrada al módulo.
 *
 * La lista de elementos entra por el otro lado: cada fila es un papel suelto, y
 * para ver los tres papeles de un controlador solar había que volver al listado
 * y buscar el siguiente a mano. Aquí se entra por el aparato y dentro están
 * todos sus papeles, que es como se mira una instalación.
 *
 * **No se dan de alta aparatos desde aquí ni se editan sus datos**: eso es del
 * módulo de Hardware. Ésta es su cara energética, y lo único que se gestiona
 * son sus papeles.
 */
class EnergyDeviceResource extends Resource
{
    use ScopesToOwner;

    protected static ?string $model = HardwareDevice::class;

    protected static ?string $cluster = Energy::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCpuChip;

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'Aparato';

    protected static ?string $pluralModelLabel = 'Aparatos';

    /**
     * @param  Builder<covariant \Illuminate\Database\Eloquent\Model>  $query
     * @return Builder<covariant \Illuminate\Database\Eloquent\Model>
     */
    protected static function scopeOwnerQuery(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    public static function table(Table $table): Table
    {
        return $table
            // **Sólo los aparatos que pintan algo en energía.** Un listado con
            // todos los dispositivos del usuario dentro del módulo de energía no
            // dice nada: la mayoría no mide corriente.
            //
            // El filtro va aquí y no en `getEloquentQuery()` porque es de
            // presentación, no de permisos: puesto allí, además de solaparse con
            // el del trait `ScopesToOwner`, hacía que la ficha de un aparato
            // devolviera un 404 en cuanto se le borraba su último elemento.
            ->modifyQueryUsing(fn (Builder $query) => $query
                ->whereHas('hardwareEnergy')
                ->with(['type', 'hardwareEnergy']))
            ->defaultSort('name')
            ->columns([
                TextColumn::make('name')
                    ->label('Aparato')
                    ->description(fn (HardwareDevice $record): string => (string) $record->getRelationValue('type')?->name)
                    ->searchable()
                    ->sortable(),

                // Los papeles como badges, que es lo que se viene a ver: de un
                // vistazo se sabe si el aparato mide las tres cosas o sólo una.
                TextColumn::make('hardwareEnergy.role')
                    ->label('Papeles')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => HardwareEnergy::ROLE_LABELS[$state] ?? (string) $state)
                    ->color(fn (?string $state): string => match ($state) {
                        HardwareEnergy::ROLE_GENERATOR => 'success',
                        HardwareEnergy::ROLE_BATTERY => 'warning',
                        default => 'info',
                    })
                    ->placeholder('sin papeles'),

                TextColumn::make('hardware_energy_count')
                    ->label('Elementos')
                    ->counts('hardwareEnergy')
                    ->alignCenter(),

                TextColumn::make('type.name')
                    ->label('Tipo')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('hardware_type_id')
                    ->relationship('type', 'name')
                    ->label('Tipo de aparato'),
            ])
            ->recordActions([
                // Lleva a la ficha energética del aparato, que es donde está
                // todo lo suyo: sus papeles, su configuración y su telemetría.
                Action::make('energia')
                    ->label('Ver energía')
                    ->icon('heroicon-o-bolt')
                    ->url(fn (HardwareDevice $record): string => ManageEnergyDevice::getUrl(['record' => $record])),
            ])
            ->emptyStateHeading('Ningún aparato mide energía todavía')
            ->emptyStateDescription(
                'Un aparato aparece aquí en cuanto se le da de alta un papel '
                .'—generador, consumo o batería— desde su ficha en Hardware.'
            );
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEnergyDevices::route('/'),
            'edit' => ManageEnergyDevice::route('/{record}'),
        ];
    }
}
