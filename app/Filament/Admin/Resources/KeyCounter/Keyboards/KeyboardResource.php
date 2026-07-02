<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\KeyCounter\Keyboards;

use App\Filament\Admin\Clusters\KeyCounter;
use App\Filament\Admin\Resources\KeyCounter\Keyboards\Pages\CreateKeyboard;
use App\Filament\Admin\Resources\KeyCounter\Keyboards\Pages\EditKeyboard;
use App\Filament\Admin\Resources\KeyCounter\Keyboards\Pages\ListKeyboards;
use App\Models\KeyCounter\Keyboard;
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

class KeyboardResource extends Resource
{
    protected static ?string $model = Keyboard::class;

    protected static ?string $cluster = KeyCounter::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPencilSquare;

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'Racha de teclado';

    protected static ?string $pluralModelLabel = 'Rachas de teclado';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Origen')->columns(2)->schema([
                    Select::make('user_id')
                        ->relationship('user', 'name')->searchable()->preload()->label('Usuario'),
                    Select::make('hardware_device_id')
                        ->relationship('hardwareDevice', 'name_friendly')->searchable()->preload()->label('Dispositivo'),
                ])->columnSpanFull(),
                Section::make('Racha')->columns(2)->schema([
                    DateTimePicker::make('start_at')
                        ->required()->label('Inicio'),
                    DateTimePicker::make('end_at')
                        ->required()->label('Fin')
                        ->rule('after_or_equal:start_at'),
                    TextInput::make('duration')
                        ->required()->numeric()->minValue(0)->suffix('s')->label('Duración'),
                    TextInput::make('weekday')
                        ->required()->numeric()->minValue(0)->maxValue(6)->label('Día semana (0=Dom)'),
                ])->columnSpanFull(),
                Section::make('Pulsaciones')->columns(2)->schema([
                    TextInput::make('pulsations')
                        ->required()->numeric()->minValue(0)->label('Total'),
                    TextInput::make('pulsations_special_keys')
                        ->required()->numeric()->minValue(0)->default(0)->label('Especiales'),
                    TextInput::make('pulsation_average')
                        ->required()->numeric()->step(0.0001)->label('Media (puls/s)'),
                    TextInput::make('score')
                        ->required()->numeric()->minValue(0)->label('Puntuación'),
                ])->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->label('Usuario')
                    ->searchable(),
                TextColumn::make('hardware_device_id')
                    ->label('Dispositivo')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('start_at')
                    ->label('Inicio')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('end_at')
                    ->label('Fin')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('duration')
                    ->label('Duración (s)')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('pulsations')
                    ->label('Total_Puls.')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('pulsations_special_keys')
                    ->label('Puls._Especiales')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('pulsation_average')
                    ->label('Media_Puls.')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('score')
                    ->label('Puntuación')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('weekday')
                    ->label('Día')
                    ->numeric()
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
            ->filters([
                SelectFilter::make('hardware_device_id')
                    ->relationship('hardwareDevice', 'name_friendly')->label('Dispositivo'),
                SelectFilter::make('user_id')
                    ->relationship('user', 'name')->label('Usuario'),
                SelectFilter::make('weekday')->options([
                    0 => 'Dom', 1 => 'Lun', 2 => 'Mar', 3 => 'Mié', 4 => 'Jue', 5 => 'Vie', 6 => 'Sáb',
                ])->label('Día'),
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
            'index' => ListKeyboards::route('/'),
            'create' => CreateKeyboard::route('/create'),
            'edit' => EditKeyboard::route('/{record}/edit'),
        ];
    }
}
