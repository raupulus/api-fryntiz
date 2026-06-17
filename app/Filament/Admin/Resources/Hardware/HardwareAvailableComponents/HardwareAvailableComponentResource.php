<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Hardware\HardwareAvailableComponents;

use App\Filament\Admin\Resources\Hardware\HardwareAvailableComponents\Pages\CreateHardwareAvailableComponent;
use App\Filament\Admin\Resources\Hardware\HardwareAvailableComponents\Pages\EditHardwareAvailableComponent;
use App\Filament\Admin\Resources\Hardware\HardwareAvailableComponents\Pages\ListHardwareAvailableComponents;
use App\Models\Hardware\HardwareAvailableComponent;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class HardwareAvailableComponentResource extends Resource
{
    protected static ?string $model = HardwareAvailableComponent::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Hardware';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedWrenchScrewdriver;

    protected static ?int $navigationSort = 3;

    protected static ?string $modelLabel = 'Componente';

    protected static ?string $pluralModelLabel = 'Componentes disponibles';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nombre')
                    ->required(),
                TextInput::make('type')
                    ->label('Tipo'),
                TextInput::make('slug')
                    ->label('Slug')
                    ->required(),
                Textarea::make('description')
                    ->label('Descripción')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable(),
                TextColumn::make('type')
                    ->label('Tipo')
                    ->searchable(),
                TextColumn::make('slug')
                    ->label('Slug')
                    ->searchable(),
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
                TextColumn::make('deleted_at')
                    ->label('Eliminado el')
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
            'index' => ListHardwareAvailableComponents::route('/'),
            'create' => CreateHardwareAvailableComponent::route('/create'),
            'edit' => EditHardwareAvailableComponent::route('/{record}/edit'),
        ];
    }
}
