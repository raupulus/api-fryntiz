<?php

namespace App\Filament\Admin\Resources\Printers;

use App\Filament\Admin\Resources\Printers\Pages\CreatePrinter;
use App\Filament\Admin\Resources\Printers\Pages\EditPrinter;
use App\Filament\Admin\Resources\Printers\Pages\ListPrinters;
use App\Filament\Admin\Resources\Printers\RelationManagers\PrinterStackRelationManager;
use App\Models\Printer;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PrinterResource extends Resource
{
    protected static ?string $model = Printer::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPrinter;

    protected static string|\UnitEnum|null $navigationGroup = 'Hardware';

    protected static ?int $navigationSort = 20;

    protected static ?string $modelLabel = 'Impresora';

    protected static ?string $pluralModelLabel = 'Impresoras';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Datos de la impresora')
                ->columns(2)
                ->schema([
                    TextInput::make('name')
                        ->required()->maxLength(511)->label('Nombre'),
                    TextInput::make('code')
                        ->maxLength(255)->label('Código identificador'),
                    Select::make('printer_type_id')
                        ->relationship('printerType', 'name')
                        ->required()->searchable()->preload()
                        ->label('Tipo de impresora'),
                    Select::make('user_id')
                        ->relationship('user', 'name')
                        ->required()->searchable()->preload()
                        ->default(fn () => auth()->id())
                        ->label('Propietario'),
                    Select::make('hardware_device_id')
                        ->relationship('hardwareDevice', 'name')
                        ->searchable()->preload()->nullable()
                        ->label('Dispositivo asociado'),
                    Textarea::make('description')
                        ->rows(3)->columnSpanFull()
                        ->label('Descripción'),
                ])->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->sortable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('name')->searchable()->sortable()->label('Nombre'),
                TextColumn::make('code')->searchable()->toggleable()->label('Código'),
                TextColumn::make('printerType.name')->badge()->label('Tipo'),
                TextColumn::make('user.name')->label('Propietario')->toggleable(),
                TextColumn::make('hardwareDevice.name')->label('Dispositivo')->toggleable(),
                TextColumn::make('print_stack_count')->counts('printStack')->label('Cola')
                    ->sortable(),
                TextColumn::make('created_at')->dateTime('d/m/Y H:i')->sortable()
                    ->label('Creado'),
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
