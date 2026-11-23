<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\KeyCounter\Mice;

use App\Enums\KeyCounterWeekdayEnum;
use App\Filament\Admin\Clusters\KeyCounter;
use App\Filament\Admin\Resources\KeyCounter\Mice\Pages\CreateMouse;
use App\Filament\Admin\Resources\KeyCounter\Mice\Pages\EditMouse;
use App\Filament\Admin\Resources\KeyCounter\Mice\Pages\ListMice;
use App\Filament\Concerns\ScopesToOwner;
use App\Models\Hardware\HardwareDevice;
use App\Models\KeyCounter\Mouse;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class MouseResource extends Resource
{
    use ScopesToOwner;

    protected static ?string $model = Mouse::class;

    protected static ?string $cluster = KeyCounter::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCursorArrowRays;

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'Racha de ratón';

    protected static ?string $pluralModelLabel = 'Rachas de ratón';

    protected static ?string $navigationLabel = 'Rachas de ratón';

    protected static ?string $recordTitleAttribute = 'id';

    protected static ?string $slug = 'mice';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Origen')->columns(2)->schema([
                    Select::make('user_id')
                        ->relationship('user', 'name')->searchable()->preload()->label('Usuario'),
                    Select::make('hardware_device_id')
                        ->relationship('hardwareDevice', 'name_friendly')
                        ->getOptionLabelFromRecordUsing(fn (HardwareDevice $record): string => $record->display_name)
                        ->searchable()->preload()->label('Dispositivo'),
                ])->columnSpanFull(),
                Section::make('Racha')->columns(2)->schema([
                    DateTimePicker::make('start_at')
                        ->required()->label('Inicio'),
                    DateTimePicker::make('end_at')
                        ->required()->label('Fin')
                        ->rule('after_or_equal:start_at'),
                    TextInput::make('duration')
                        ->required()->integer()->minValue(0)->suffix('s')->label('Duración'),
                    // `0` es domingo (convención de Carbon). Un `Select` en
                    // vez de un número suelto para que no haya que acordarse.
                    Select::make('weekday')
                        ->options(KeyCounterWeekdayEnum::options())
                        ->native(false)
                        ->required()
                        ->label('Día de la semana'),
                ])->columnSpanFull(),
                Section::make('Clicks')->columns(2)->schema([
                    TextInput::make('clicks_left')
                        ->required()->integer()->minValue(0)->label('Izquierdos'),
                    TextInput::make('clicks_right')
                        ->required()->integer()->minValue(0)->label('Derechos'),
                    TextInput::make('clicks_middle')
                        ->required()->integer()->minValue(0)->label('Centrales'),
                    TextInput::make('total_clicks')
                        ->required()->integer()->minValue(0)->label('Total'),
                    TextInput::make('clicks_average')
                        ->required()->numeric()->step(0.0001)->label('Media (cps)'),
                ])->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->label('Usuario')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                // El nombre corto, no el id: `display_name` prefiere
                // `name_friendly` y cae en `name` cuando no lo hay. El mismo
                // criterio que ya usaba el filtro de abajo.
                TextColumn::make('hardwareDevice.display_name')
                    ->label('Dispositivo')
                    ->limit(30)
                    ->tooltip(fn (?string $state): ?string => $state)
                    ->sortable()
                    ->searchable(['name_friendly', 'name']),
                TextColumn::make('start_at')
                    ->label('Inicio')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('end_at')
                    ->label('Fin')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('duration')
                    ->label('Duración (s)')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('clicks_left')
                    ->label('Clicks izq.')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('clicks_right')
                    ->label('Clicks der.')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('clicks_middle')
                    ->label('Clicks cen.')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('total_clicks')
                    ->label('Total clicks')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('clicks_average')
                    ->label('Media (cps)')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('weekday')
                    ->label('Día')
                    ->formatStateUsing(fn ($state): ?string => KeyCounterWeekdayEnum::labelFor($state))
                    ->sortable(),
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
            ])
            // Mismo criterio que en teclados: lo último subido primero.
            ->defaultSort('start_at', 'desc')
            ->filters([
                SelectFilter::make('hardware_device_id')
                    ->relationship('hardwareDevice', 'name_friendly')
                    ->getOptionLabelFromRecordUsing(fn (HardwareDevice $record): string => $record->display_name)
                    ->label('Dispositivo'),
                SelectFilter::make('user_id')
                    ->relationship('user', 'name')->label('Usuario'),
                SelectFilter::make('weekday')
                    ->options(KeyCounterWeekdayEnum::options())
                    ->label('Día'),
                Filter::make('today')
                    ->query(fn ($q) => $q->whereDate('start_at', today()))
                    ->label('Hoy'),
            ])
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
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMice::route('/'),
            'create' => CreateMouse::route('/create'),
            'edit' => EditMouse::route('/{record}/edit'),
        ];
    }
}
