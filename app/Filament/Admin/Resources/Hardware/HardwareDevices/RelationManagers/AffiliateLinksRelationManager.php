<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Hardware\HardwareDevices\RelationManagers;

use App\Models\Hardware\HardwareDevice;
use App\Models\Referred\ReferredThing;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Gestión de enlaces de compra de afiliados asociados al dispositivo hardware
 * y a sus componentes individuales.
 */
class AffiliateLinksRelationManager extends RelationManager
{
    protected static string $relationship = 'affiliateLinks';

    protected static ?string $title = 'Enlaces de compra / Afiliados';

    protected static ?string $modelLabel = 'enlace de compra';

    protected static ?string $pluralModelLabel = 'enlaces de compra';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('referred_platform_id')
                ->relationship('platform', 'name')
                ->searchable()
                ->preload()
                ->required()
                ->label('Plataforma')
                ->helperText('Plataforma de afiliación (Amazon, AliExpress, etc.)'),

            Select::make('hardware_component_id')
                ->label('Aplica a')
                ->options(function (): array {
                    /** @var HardwareDevice $device */
                    $device = $this->getOwnerRecord();

                    $options = [];
                    foreach ($device->components as $component) {
                        $options[$component->id] = $component->name ?: ('Componente #'.$component->id);
                    }

                    return $options;
                })
                ->placeholder('Dispositivo completo (Hardware principal)')
                ->nullable()
                ->helperText('Selecciona si este enlace es para el dispositivo completo o para uno de sus componentes instalados'),

            TextInput::make('name')
                ->label('Título o nota')
                ->maxLength(255)
                ->placeholder('Ej: Pack recomendado con disipador, o Sensor BME280'),

            TextInput::make('price')
                ->label('Precio orientativo')
                ->numeric()
                ->suffix('€')
                ->placeholder('0.00'),

            TextInput::make('currency')
                ->label('Moneda')
                ->default('EUR')
                ->maxLength(3),

            Toggle::make('is_active')
                ->label('Enlace activo')
                ->default(true)
                ->helperText('Desactiva el enlace si el producto está sin stock o descatalogado'),

            TextInput::make('url')
                ->label('URL de afiliado')
                ->url()
                ->required()
                ->maxLength(2048)
                ->columnSpanFull()
                ->placeholder('https://amzn.to/... o enlace con parámetros de tracking'),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('platform.name')
                    ->label('Plataforma')
                    ->badge()
                    ->sortable(),

                TextColumn::make('target_label')
                    ->label('Destino')
                    ->badge()
                    ->color(fn (ReferredThing $record): string => $record->hardware_component_id !== null ? 'info' : 'success'),

                TextColumn::make('name')
                    ->label('Título / Detalle')
                    ->placeholder('Sin título')
                    ->searchable(),

                TextColumn::make('price')
                    ->label('Precio')
                    ->formatStateUsing(fn ($state, ReferredThing $record): string => $record->formatted_price ?? '—')
                    ->sortable(),

                TextColumn::make('url')
                    ->label('Enlace')
                    ->limit(35)
                    ->url(fn (ReferredThing $record): string => $record->url, true)
                    ->openUrlInNewTab()
                    ->icon('heroicon-m-arrow-top-right-on-square')
                    ->iconPosition('after'),

                IconColumn::make('is_active')
                    ->label('Activo')
                    ->boolean()
                    ->sortable(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Añadir enlace')
                    ->modalHeading('Añadir enlace de compra de afiliado'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
