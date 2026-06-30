<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Hardware\HardwareDevices\RelationManagers;

use App\Models\Hardware\HardwareDevice;
use App\Services\Hardware\DeviceTokenService;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DateTimePicker;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Gestión de tokens IoT desde la ficha del dispositivo: lista solo los tokens
 * de ESE dispositivo (nombre "device:{id}"), permite emitir uno nuevo ligado al
 * dispositivo y revocar los existentes. El listado global sigue disponible en
 * el recurso "API Tokens" (grupo Sistema).
 */
class TokensRelationManager extends RelationManager
{
    protected static string $relationship = 'apiTokens';

    protected static ?string $title = 'Tokens IoT';

    protected static ?string $modelLabel = 'token';

    protected static ?string $pluralModelLabel = 'tokens';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name')->label('Nombre'),
                TextColumn::make('abilities')
                    ->label('Permisos')
                    ->formatStateUsing(fn ($state) => is_array($state) ? implode(', ', $state) : (string) $state)
                    ->wrap(),
                TextColumn::make('last_used_at')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('Nunca')
                    ->label('Último uso'),
                TextColumn::make('expires_at')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('No expira')
                    ->color(fn ($state) => $state && now()->gt($state) ? 'danger' : 'gray')
                    ->label('Expira'),
                TextColumn::make('created_at')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->label('Creado'),
            ])
            ->headerActions([
                Action::make('issueToken')
                    ->label('Emitir token')
                    ->icon('heroicon-o-key')
                    ->modalHeading('Emitir token IoT para este dispositivo')
                    ->modalSubmitActionLabel('Emitir')
                    ->visible(fn () => $this->getOwnerRecord()->user_id !== null)
                    ->schema([
                        CheckboxList::make('abilities')
                            ->label('Permisos de módulo')
                            ->helperText('Se añade automáticamente el permiso "device:{id}" que liga el token a este dispositivo.')
                            ->options(DeviceTokenService::MODULE_ABILITIES)
                            ->columns(2)
                            ->required(),
                        DateTimePicker::make('expires_at')
                            ->seconds(false)
                            ->label('Expira el (opcional)'),
                    ])
                    ->action(function (array $data): void {
                        /** @var HardwareDevice $device */
                        $device = $this->getOwnerRecord();

                        if (! $device->user) {
                            Notification::make()
                                ->title('El dispositivo no tiene usuario propietario asociado')
                                ->danger()
                                ->send();

                            return;
                        }

                        $expiresAt = ! empty($data['expires_at']) ? Carbon::parse($data['expires_at']) : null;

                        $token = app(DeviceTokenService::class)->issue($device, $data['abilities'], $expiresAt);

                        Notification::make()
                            ->title('Token emitido — cópialo ahora (no se volverá a mostrar)')
                            ->body($token->plainTextToken)
                            ->persistent()
                            ->success()
                            ->send();
                    }),
            ])
            ->recordActions([
                DeleteAction::make()->label('Revocar'),
            ])
            ->toolbarActions([
                DeleteBulkAction::make()->label('Revocar seleccionados'),
            ])
            ->defaultSort('id', 'desc');
    }
}
