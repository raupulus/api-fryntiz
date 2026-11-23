<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Newsletters;

use App\Filament\Admin\Resources\Newsletters\Pages\EditNewsletter;
use App\Filament\Admin\Resources\Newsletters\Pages\ListNewsletters;
use App\Models\Newsletter;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
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

/**
 * Gestión de suscriptores de Newsletter desde el panel Admin (incluyendo bajas
 * y supresión RGPD).
 */
class NewsletterResource extends Resource
{
    protected static ?string $model = Newsletter::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedNewspaper;

    protected static string|\UnitEnum|null $navigationGroup = 'Módulos';

    protected static ?int $navigationSort = 15;

    protected static ?string $modelLabel = 'Suscriptor';

    protected static ?string $pluralModelLabel = 'Newsletter';

    protected static ?string $navigationLabel = 'Newsletter';

    protected static ?string $recordTitleAttribute = 'email';

    protected static ?string $slug = 'newsletters';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Datos del suscriptor')
                ->columns(2)
                ->schema([
                    Select::make('platform_id')
                        ->relationship('platform', 'title')
                        ->searchable()
                        ->preload()
                        ->required()
                        ->label('Plataforma'),
                    TextInput::make('email')
                        ->email()
                        ->required()
                        ->maxLength(255)
                        ->label('Correo electrónico'),
                    TextInput::make('name')
                        ->maxLength(100)
                        ->label('Nombre'),
                    TextInput::make('language')
                        ->maxLength(10)
                        ->default('es')
                        ->label('Idioma'),
                    Select::make('status')
                        ->options([
                            Newsletter::STATUS_ACTIVE => 'Activo',
                            Newsletter::STATUS_INACTIVE => 'Inactivo',
                            Newsletter::STATUS_UNSUBSCRIBED => 'Desuscrito',
                            Newsletter::STATUS_BOUNCED => 'Rebotado',
                        ])
                        ->required()
                        ->native(false)
                        ->label('Estado'),
                    Select::make('subscription_source')
                        ->options([
                            Newsletter::SOURCE_WEB => 'Web',
                            Newsletter::SOURCE_API => 'API',
                            Newsletter::SOURCE_IMPORT => 'Importación',
                            Newsletter::SOURCE_ADMIN => 'Panel Admin',
                        ])
                        ->native(false)
                        ->label('Origen'),
                    Toggle::make('is_verified')
                        ->label('Correo verificado'),
                ])->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('platform'))
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('email')
                    ->label('Correo')
                    ->searchable()
                    ->copyable()
                    ->limit(35)
                    ->tooltip(fn (?string $state): ?string => $state),
                TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->limit(30)
                    ->tooltip(fn (?string $state): ?string => $state)
                    ->placeholder('—'),
                TextColumn::make('platform.title')
                    ->label('Plataforma')
                    ->limit(25)
                    ->tooltip(fn (?string $state): ?string => $state)
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        Newsletter::STATUS_ACTIVE => 'Activo',
                        Newsletter::STATUS_INACTIVE => 'Inactivo',
                        Newsletter::STATUS_UNSUBSCRIBED => 'Desuscrito',
                        Newsletter::STATUS_BOUNCED => 'Rebotado',
                        default => (string) $state,
                    })
                    ->color(fn (?string $state): string => match ($state) {
                        Newsletter::STATUS_ACTIVE => 'success',
                        Newsletter::STATUS_UNSUBSCRIBED => 'warning',
                        Newsletter::STATUS_BOUNCED => 'danger',
                        default => 'gray',
                    }),
                IconColumn::make('is_verified')
                    ->label('Verif.')
                    ->boolean(),
                TextColumn::make('subscription_source')
                    ->label('Origen')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('language')
                    ->label('Idioma')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('verified_at')
                    ->label('Verificado el')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('unsubscribed_at')
                    ->label('Baja el')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label('Alta')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('platform_id')
                    ->relationship('platform', 'title')
                    ->label('Plataforma'),
                SelectFilter::make('status')
                    ->options([
                        Newsletter::STATUS_ACTIVE => 'Activo',
                        Newsletter::STATUS_INACTIVE => 'Inactivo',
                        Newsletter::STATUS_UNSUBSCRIBED => 'Desuscrito',
                        Newsletter::STATUS_BOUNCED => 'Rebotado',
                    ])
                    ->label('Estado'),
                TernaryFilter::make('is_verified')
                    ->label('Verificado'),
            ])
            ->recordActions([
                ActionGroup::make([
                    EditAction::make(),
                    Action::make('unsubscribe')
                        ->label('Dar de baja')
                        ->icon(Heroicon::OutlinedNoSymbol)
                        ->color('warning')
                        ->visible(fn (Newsletter $record): bool => ! $record->isUnsubscribed())
                        ->requiresConfirmation()
                        ->modalHeading('¿Dar de baja a este suscriptor?')
                        ->action(function (Newsletter $record): void {
                            $record->unsubscribe();
                            Notification::make()
                                ->title('Suscriptor dado de baja')
                                ->warning()
                                ->send();
                        }),
                    Action::make('reactivate')
                        ->label('Reactivar')
                        ->icon(Heroicon::OutlinedCheckCircle)
                        ->color('success')
                        ->visible(fn (Newsletter $record): bool => $record->isUnsubscribed())
                        ->requiresConfirmation()
                        ->modalHeading('¿Reactivar suscripción?')
                        ->action(function (Newsletter $record): void {
                            $record->reactivate();
                            Notification::make()
                                ->title('Suscripción reactivada')
                                ->success()
                                ->send();
                        }),
                    DeleteAction::make()
                        ->modalHeading('¿Eliminar suscriptor definitivamente?')
                        ->modalDescription('Se borrará el registro del suscriptor para atender solicitudes de supresión RGPD.'),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListNewsletters::route('/'),
            'edit' => EditNewsletter::route('/{record}/edit'),
        ];
    }
}
