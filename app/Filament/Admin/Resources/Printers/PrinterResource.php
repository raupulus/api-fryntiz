<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Printers;

use App\Enums\PrinterStatusEnum;
use App\Enums\PrinterTypeEnum;
use App\Enums\PrintJobFormatEnum;
use App\Filament\Admin\Resources\Printers\Pages\CreatePrinter;
use App\Filament\Admin\Resources\Printers\Pages\EditPrinter;
use App\Filament\Admin\Resources\Printers\Pages\ListPrinters;
use App\Filament\Admin\Resources\Printers\RelationManagers\PrinterStackRelationManager;
use App\Filament\Concerns\ScopesToOwner;
use App\Models\Printer;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PrinterResource extends Resource
{
    use ScopesToOwner;

    protected static ?string $model = Printer::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPrinter;

    protected static string|\UnitEnum|null $navigationGroup = 'Hardware';

    protected static ?int $navigationSort = 20;

    protected static ?string $modelLabel = 'Impresora';

    protected static ?string $pluralModelLabel = 'Impresoras';

    protected static ?string $recordTitleAttribute = 'name';

    /**
     * Limita la consulta a las impresoras cuyos dispositivos pertenezcan al usuario.
     *
     * @param  Builder<covariant \Illuminate\Database\Eloquent\Model>  $query
     * @return Builder<covariant \Illuminate\Database\Eloquent\Model>
     */
    protected static function scopeOwnerQuery(Builder $query, int $userId): Builder
    {
        return $query->whereHas('hardwareDevice', fn (Builder $q) => $q->where('user_id', $userId));
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Datos de la impresora')
                ->columns(2)
                ->schema([
                    TextInput::make('name')
                        ->required()
                        ->maxLength(511)
                        ->label('Nombre descriptivo'),

                    TextInput::make('code')
                        ->maxLength(255)
                        ->label('Código identificador'),

                    Select::make('hardware_device_id')
                        ->relationship('hardwareDevice', 'name')
                        ->required()
                        ->searchable()
                        ->preload()
                        ->label('Dispositivo Hardware'),

                    Select::make('printer_type')
                        ->options(PrinterTypeEnum::options())
                        ->required()
                        ->default(PrinterTypeEnum::Thermal->value)
                        ->label('Tecnología'),

                    Select::make('status')
                        ->options(PrinterStatusEnum::options())
                        ->required()
                        ->default(PrinterStatusEnum::Offline->value)
                        ->label('Estado en tiempo real'),

                    Toggle::make('is_active')
                        ->default(true)
                        ->label('Habilitada para recibir trabajos'),

                    Textarea::make('description')
                        ->rows(3)
                        ->columnSpanFull()
                        ->label('Descripción'),
                ])->columnSpanFull(),

            Section::make('Configuración de formatos y memoria')
                ->columns(2)
                ->schema([
                    Select::make('supported_formats')
                        ->multiple()
                        ->options(PrintJobFormatEnum::options())
                        ->required()
                        ->default([PrintJobFormatEnum::Text->value, PrintJobFormatEnum::Escpos->value])
                        ->label('Formatos soportados'),

                    Select::make('default_format')
                        ->options(PrintJobFormatEnum::options())
                        ->required()
                        ->default(PrintJobFormatEnum::Text->value)
                        ->label('Formato predeterminado'),

                    TextInput::make('max_payload_kb')
                        ->numeric()
                        ->minValue(1)
                        ->maxValue(4096)
                        ->default(64)
                        ->suffix('KB')
                        ->label('Límite máximo de payload'),

                    TextInput::make('total_prints_count')
                        ->numeric()
                        ->disabled()
                        ->dehydrated(false)
                        ->label('Total impresiones físicas confirmadas (Odómetro)'),
                ])->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->sortable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('name')->searchable()->sortable()->label('Nombre'),
                TextColumn::make('printer_type')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state instanceof PrinterTypeEnum ? $state->label() : (PrinterTypeEnum::tryFrom((string) $state)?->label() ?? $state))
                    ->label('Tecnología'),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn ($state) => $state instanceof PrinterStatusEnum ? $state->color() : (PrinterStatusEnum::tryFrom((string) $state)?->color() ?? 'gray'))
                    ->formatStateUsing(fn ($state) => $state instanceof PrinterStatusEnum ? $state->label() : (PrinterStatusEnum::tryFrom((string) $state)?->label() ?? $state))
                    ->label('Estado'),
                IconColumn::make('is_active')->boolean()->label('Activa'),
                TextColumn::make('hardwareDevice.name')->label('Dispositivo')->searchable()->toggleable(),
                TextColumn::make('total_prints_count')->numeric()->sortable()->label('Odómetro'),
                TextColumn::make('print_stack_count')->counts('printStack')->label('Cola')->sortable(),
                TextColumn::make('last_seen_at')->since()->label('Último contacto')->sortable(),
                TextColumn::make('created_at')->dateTime('d/m/Y H:i')->sortable()->toggleable(isToggledHiddenByDefault: true)->label('Creado'),
            ])
            ->filters([
                SelectFilter::make('printer_type')->options(PrinterTypeEnum::options())->label('Tecnología'),
                SelectFilter::make('status')->options(PrinterStatusEnum::options())->label('Estado'),
                TernaryFilter::make('is_active')->label('Activa'),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])])
            ->defaultSort('id', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            PrinterStackRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPrinters::route('/'),
            'create' => CreatePrinter::route('/create'),
            'edit' => EditPrinter::route('/{record}/edit'),
        ];
    }
}
