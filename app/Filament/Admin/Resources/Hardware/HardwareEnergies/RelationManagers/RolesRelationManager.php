<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Hardware\HardwareEnergies\RelationManagers;

use App\Filament\Admin\Resources\Hardware\HardwareEnergies\HardwareEnergyForm;
use App\Models\Hardware\HardwareEnergy;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Los tres papeles del mismo medidor, desde la ficha de cualquiera de ellos.
 *
 * **El agujero que tapa.** Estando en «Editar Elemento Energético» no había
 * forma de llegar a los otros papeles del mismo aparato, y menos de crear el que
 * falta: había que volver al listado, buscarlo y entrar. Para dar de alta la
 * batería de un controlador, directamente no había camino visible. Las dos
 * pestañas que hay debajo —«Lecturas de consumo» y «Lecturas de generación»—
 * son las **lecturas**, no los papeles, y encima eso hacía pensar que la
 * batería no se podía crear.
 *
 * Aquí están los tres, con un botón por cada uno que falte. Es el mismo
 * planteamiento que en la ficha del dispositivo, para que se busque en el mismo
 * sitio se venga de donde se venga.
 */
class RolesRelationManager extends RelationManager
{
    protected static string $relationship = 'rolesOnSameMeter';

    protected static ?string $title = 'Papeles de este aparato';

    protected static ?string $modelLabel = 'papel';

    protected static ?string $pluralModelLabel = 'papeles';

    public function form(Schema $schema): Schema
    {
        return HardwareEnergyForm::forSameMeter($schema);
    }

    public function table(Table $table): Table
    {
        return $table
            ->description(
                'Un mismo aparato puede producir, gastar y almacenar a la vez, y '
                .'cada cosa es una fila. De generador y de batería hay uno; de '
                .'consumo, tantos como canales tenga el medidor.'
            )
            ->modifyQueryUsing(fn ($query) => $query->with(['monitorized', 'sourceType']))
            ->defaultSort('sensor_position')
            ->columns([
                TextColumn::make('role')
                    ->label('Papel')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => HardwareEnergy::ROLE_LABELS[$state] ?? (string) $state)
                    ->color(fn (?string $state): string => match ($state) {
                        HardwareEnergy::ROLE_GENERATOR => 'success',
                        HardwareEnergy::ROLE_BATTERY => 'warning',
                        default => 'info',
                    }),
                TextColumn::make('monitorized.display_name')
                    ->label('Qué mide')
                    ->placeholder('a sí mismo'),
                TextColumn::make('sensor_position')
                    ->label('Canal')
                    ->badge()
                    ->color('gray'),
                TextColumn::make('nominal_voltage')
                    ->label('V nominal')
                    ->suffix(' V')
                    ->placeholder('la que reporte'),
                IconColumn::make('is_active')
                    ->label('Activo')
                    ->boolean(),
            ])
            ->headerActions($this->creationButtons())
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    /**
     * Un botón por papel, y sólo mientras quepa otro de ese papel.
     *
     * @return list<CreateAction>
     */
    private function creationButtons(): array
    {
        $descriptions = [
            HardwareEnergy::ROLE_GENERATOR => 'Lo que produce: el panel solar, el alternador…',
            HardwareEnergy::ROLE_LOAD => 'Lo que gasta: la salida de carga, un router, una Raspberry…',
            HardwareEnergy::ROLE_BATTERY => 'Lo que almacena: el banco de baterías.',
        ];

        $icons = [
            HardwareEnergy::ROLE_GENERATOR => 'heroicon-o-sun',
            HardwareEnergy::ROLE_LOAD => 'heroicon-o-bolt',
            HardwareEnergy::ROLE_BATTERY => 'heroicon-o-battery-50',
        ];

        $colors = [
            HardwareEnergy::ROLE_GENERATOR => 'success',
            HardwareEnergy::ROLE_LOAD => 'info',
            HardwareEnergy::ROLE_BATTERY => 'warning',
        ];

        $actions = [];

        foreach (HardwareEnergy::ROLES as $role) {
            $actions[] = CreateAction::make('create_'.$role)
                ->label('Añadir '.mb_strtolower(HardwareEnergy::ROLE_LABELS[$role]))
                ->icon($icons[$role])
                ->color($colors[$role])
                ->modalHeading(HardwareEnergy::ROLE_LABELS[$role].' de este aparato')
                ->modalDescription($descriptions[$role])
                // El papel lo dice el botón, y el medidor es el mismo del que
                // se viene: los dos los pone el contexto.
                ->mutateDataUsing(function (array $data) use ($role): array {
                    $data['role'] = $role;
                    $data['hardware_device_id'] = $this->element()->hardware_device_id;

                    return $data;
                })
                ->visible(fn (): bool => $this->hasRoomForAnother($role));
        }

        return $actions;
    }

    /**
     * ¿Queda sitio para otro elemento de este papel en este medidor?
     */
    private function hasRoomForAnother(string $role): bool
    {
        $limit = HardwareEnergy::LIMIT_PER_ROLE[$role] ?? null;

        if ($limit === null) {
            return true;
        }

        return $this->element()->rolesOnSameMeter()->where('role', $role)->count() < $limit;
    }

    /**
     * El elemento desde el que se está mirando.
     *
     * `getOwnerRecord()` devuelve un `Model` genérico, y de ahí no salen ni la
     * relación ni la columna sin que el analizador proteste con razón.
     */
    private function element(): HardwareEnergy
    {
        $record = $this->getOwnerRecord();

        if (! $record instanceof HardwareEnergy) {
            throw new \LogicException('Este panel sólo cuelga de un elemento de energía.');
        }

        return $record;
    }
}
