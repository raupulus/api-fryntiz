<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Referred\ReferredPlatforms;

use App\Filament\Admin\Resources\Referred\ReferredPlatforms\Pages\CreateReferredPlatform;
use App\Filament\Admin\Resources\Referred\ReferredPlatforms\Pages\EditReferredPlatform;
use App\Filament\Admin\Resources\Referred\ReferredPlatforms\Pages\ListReferredPlatforms;
use App\Models\Referred\ReferredPlatform;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ReferredPlatformResource extends Resource
{
    protected static ?string $model = ReferredPlatform::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingStorefront;

    protected static string|\UnitEnum|null $navigationGroup = 'Configuración';

    protected static ?int $navigationSort = 15;

    protected static ?string $modelLabel = 'Plataforma de afiliados';

    protected static ?string $pluralModelLabel = 'Plataformas de afiliados';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nombre de la plataforma')
                    ->required()
                    ->maxLength(255)
                    ->placeholder('Ej: Amazon, AliExpress'),

                TextInput::make('slug')
                    ->label('Slug')
                    ->maxLength(255)
                    ->placeholder('amazon')
                    ->helperText('Identificador amigable. Se genera automáticamente si se deja vacío'),

                Textarea::make('description')
                    ->label('Descripción')
                    ->columnSpanFull()
                    ->placeholder('Descripción del programa de afiliados'),

                TextInput::make('url')
                    ->label('Página principal')
                    ->url()
                    ->maxLength(255)
                    ->placeholder('https://afiliados.amazon.es'),

                TextInput::make('url_panel')
                    ->label('Enlace al panel de control')
                    ->url()
                    ->maxLength(255)
                    ->placeholder('https://afiliados.amazon.es/home'),

                TextInput::make('url_register')
                    ->label('Enlace de registro')
                    ->url()
                    ->maxLength(255)
                    ->placeholder('https://afiliados.amazon.es/signup'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('slug')
                    ->label('Slug')
                    ->badge()
                    ->color('gray')
                    ->sortable(),

                TextColumn::make('url')
                    ->label('Enlace web')
                    ->url(fn (ReferredPlatform $record): ?string => $record->url, true)
                    ->openUrlInNewTab()
                    ->icon('heroicon-m-arrow-top-right-on-square')
                    ->iconPosition('after')
                    ->placeholder('—'),

                TextColumn::make('created_at')
                    ->label('Creado el')
                    ->dateTime('d/m/Y')
                    ->sortable(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListReferredPlatforms::route('/'),
            'create' => CreateReferredPlatform::route('/create'),
            'edit' => EditReferredPlatform::route('/{record}/edit'),
        ];
    }
}
